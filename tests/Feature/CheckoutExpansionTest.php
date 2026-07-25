<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItems;
use App\Models\Products;
use App\Models\Stock;
use App\Models\User;
use Database\Seeders\PaymentMethodsSeeder;
use Database\Seeders\ShippingMethodsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutExpansionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_address_book(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->api()->postJson('/api/v1/addresses', [
            'type' => 'both',
            'name' => 'Home',
            'phone' => '01000000000',
            'country' => 'Egypt',
            'city' => 'Cairo',
            'street' => 'Market Street',
            'is_default_shipping' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Home')
            ->assertJsonPath('data.is_default_shipping', true);

        $id = $response->json('data.id');

        $this->api()->getJson("/api/v1/addresses/{$id}")
            ->assertOk()
            ->assertJsonPath('data.street', 'Market Street');
    }

    public function test_checkout_place_order_creates_paymob_order_and_deducts_stock(): void
    {
        config([
            'checkout.currency' => 'EGP',
            'payment.gateways.paymob.enabled' => true,
            'payment.gateways.paymob.base_url' => 'https://accept.paymob.com',
            'payment.gateways.paymob.secret_key' => 'paymob-secret',
            'payment.gateways.paymob.public_key' => 'paymob-public',
            'payment.gateways.paymob.hmac_secret' => 'paymob-hmac',
            'payment.gateways.paymob.integration_ids.card' => 12345,
            'payment.gateways.paymob.currency' => 'EGP',
        ]);
        Http::fake([
            'accept.paymob.com/v1/intention/' => Http::response([
                'id' => 'int_expanded_checkout',
                'client_secret' => 'cs_expanded_checkout',
            ]),
        ]);

        $this->seed(PaymentMethodsSeeder::class);
        $this->seed(ShippingMethodsSeeder::class);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $addressId = $this->api()->postJson('/api/v1/addresses', [
            'type' => 'both',
            'name' => 'Home',
            'phone' => '01000000000',
            'country' => 'Egypt',
            'city' => 'Cairo',
            'street' => 'Market Street',
            'is_default_shipping' => true,
        ])->json('data.id');

        $product = Products::factory()->create([
            'price' => 50,
            'is_active' => true,
            'stock_quantity' => 5,
        ]);
        Stock::create(['product_id' => $product->id, 'quantity' => 5]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItems::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100,
        ]);

        $this->api()->postJson('/api/v1/checkout/place-order', [
            'shipping_address_id' => $addressId,
            'payment_method' => 'paymob',
            'payment_channel' => 'card',
        ])
            ->assertOk()
            ->assertJsonPath('data.order.payment_status', 'pending')
            ->assertJsonPath('data.order.total', 110)
            ->assertJsonPath('data.payment.gateway', 'paymob');

        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'quantity' => 3]);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
    }
}
