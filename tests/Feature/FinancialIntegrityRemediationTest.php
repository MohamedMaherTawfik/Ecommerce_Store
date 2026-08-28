<?php

namespace Tests\Feature;

use App\Models\Addresses;
use App\Models\Cart;
use App\Models\CartItems;
use App\Models\Coupon;
use App\Models\Orders;
use App\Models\Products;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\Stock;
use App\Models\User;
use App\Services\Admin\OrderService;
use App\Services\Checkout\ReturnService;
use Database\Seeders\PaymentMethodsSeeder;
use Database\Seeders\ShippingMethodsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinancialIntegrityRemediationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_status_graph_rejects_skipped_transitions(): void
    {
        $order = $this->returnOrder();
        $order->update(['order_status' => 'pending']);

        $this->expectException(ValidationException::class);

        app(OrderService::class)->updateDedicatedStatus($order->id, 'order_status', 'completed');
    }

    public function test_checkout_replay_creates_one_order_payment_stock_deduction_and_coupon_redemption(): void
    {
        config([
            'checkout.currency' => 'EGP',
            'payment.gateways.paymob.enabled' => true,
            'payment.gateways.paymob.base_url' => 'https://accept.paymob.com',
            'payment.gateways.paymob.secret_key' => 'test-only-secret',
            'payment.gateways.paymob.public_key' => 'test-only-public',
            'payment.gateways.paymob.hmac_secret' => 'test-only-hmac',
            'payment.gateways.paymob.integration_ids.card' => 12345,
            'payment.gateways.paymob.currency' => 'EGP',
        ]);
        Http::fake([
            'accept.paymob.com/v1/intention/' => Http::response([
                'id' => 'int_idempotent_checkout',
                'client_secret' => 'cs_idempotent_checkout',
            ]),
        ]);
        $this->seed([PaymentMethodsSeeder::class, ShippingMethodsSeeder::class]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $address = Addresses::create([
            'user_id' => $user->id,
            'type' => 'both',
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '01000000000',
            'country' => 'Egypt',
            'country_code' => 'EG',
            'city' => 'Cairo',
            'street' => 'Market Street',
        ]);
        $product = Products::factory()->create(['price' => 50, 'stock_quantity' => 5, 'is_active' => true]);
        Stock::create(['product_id' => $product->id, 'quantity' => 5]);
        $coupon = Coupon::create([
            'code' => 'ONCE10',
            'type' => 'fixed',
            'value' => 10,
            'usage_limit' => 1,
            'used_count' => 0,
            'is_active' => true,
        ]);
        $cart = Cart::create(['user_id' => $user->id, 'coupon_id' => $coupon->id]);
        CartItems::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100,
        ]);
        $payload = [
            'shipping_address_id' => $address->id,
            'payment_method' => 'paymob',
            'payment_channel' => 'card',
            'idempotency_key' => 'checkout-idempotency-regression-001',
        ];

        $first = $this->postJson('/api/v1/checkout/place-order', $payload)->assertOk();
        $second = $this->postJson('/api/v1/checkout/place-order', $payload)
            ->assertOk()
            ->assertJsonPath('data.payment.replayed', true);

        $this->assertSame($first->json('data.order.id'), $second->json('data.order.id'));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('coupon_redemptions', 1);
        $this->assertSame(1, (int) $coupon->fresh()->used_count);
        $this->assertSame(3, (int) Stock::where('product_id', $product->id)->value('quantity'));
        Http::assertSentCount(1);
    }

    public function test_return_transition_graph_rejects_invalid_transitions(): void
    {
        $return = ReturnRequest::create([
            'order_id' => $this->returnOrder()->id,
            'user_id' => User::factory()->create()->id,
            'reason' => 'Invalid transition regression',
            'status' => 'pending',
        ]);

        $this->expectException(ValidationException::class);
        app(ReturnService::class)->updateStatus($return, 'received');
    }

    public function test_variant_stock_is_restored_exactly_once(): void
    {
        $user = User::factory()->create();
        $product = Products::factory()->create(['stock_quantity' => 10]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VARIANT-RETURN-1',
            'stock_quantity' => 3,
            'is_active' => true,
        ]);
        $order = $this->returnOrder($user, $product);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'price' => 20,
        ]);
        $return = ReturnRequest::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => 'Variant return',
            'status' => 'approved',
        ]);
        $return->items()->create([
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'status' => 'approved',
        ]);

        $service = app(ReturnService::class);
        $service->updateStatus($return, 'received');
        $service->updateStatus($return->fresh('items'), 'received');

        $this->assertSame(5, (int) $variant->fresh()->stock_quantity);
        $this->assertNotNull($return->fresh()->stock_restored_at);
    }

    private function returnOrder(?User $user = null, ?Products $product = null)
    {
        $user ??= User::factory()->create();

        return Orders::create([
            'user_id' => $user->id,
            'order_number' => 'FIN-'.fake()->unique()->numerify('######'),
            'status' => 'delivered',
            'payment_method' => 'paymob',
            'payment_status' => 'paid',
            'subtotal' => $product ? 20 : 1,
            'total' => $product ? 20 : 1,
            'phone' => '01000000000',
            'address' => 'Test address',
        ]);
    }
}
