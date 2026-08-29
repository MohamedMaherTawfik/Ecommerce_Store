<?php

namespace Tests\Feature;

use App\Models\Addresses;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\Products;
use App\Models\Refund;
use App\Models\User;
use App\Services\Checkout\CheckoutService;
use App\Services\Checkout\ReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PostRemediationVerificationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_oauth_cannot_reactivate_a_disabled_account(): void
    {
        $user = User::factory()->create([
            'email' => 'disabled-google@example.com',
            'role' => 'admin',
            'is_active' => false,
        ]);
        $password = $user->password;

        $googleUser = new class
        {
            public function getEmail(): string
            {
                return 'disabled-google@example.com';
            }

            public function getName(): string
            {
                return 'Disabled Google User';
            }

            public function getId(): string
            {
                return 'google-disabled-123';
            }

            public function getAvatar(): ?string
            {
                return null;
            }
        };

        $driver = Mockery::mock();
        $driver->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($driver);

        $this->get('/api/v1/users/google-callback')
            ->assertRedirect(config('app.frontend_url').'/auth/google-error?message=This+account+is+currently+disabled.');

        $user->refresh();
        $this->assertFalse((bool) $user->is_active);
        $this->assertSame('admin', $user->role);
        $this->assertSame($password, $user->password);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_distinct_returns_cannot_request_refunds_above_the_order_total(): void
    {
        $user = User::factory()->create();
        $product = Products::factory()->create(['price' => 100]);
        $order = Orders::create([
            'user_id' => $user->id,
            'order_number' => 'POST-AUDIT-REFUND-001',
            'status' => 'delivered',
            'order_status' => 'delivered',
            'payment_method' => 'paymob',
            'payment_status' => 'paid',
            'subtotal' => 100,
            'total' => 100,
            'currency' => 'EGP',
            'phone' => '01000000000',
            'address' => 'Audit address',
        ]);
        $orderItem = OrderItems::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
        ]);

        $returns = app(ReturnService::class);
        $payload = [
            'reason' => 'Duplicate item return',
            'items' => [[
                'order_item_id' => $orderItem->id,
                'quantity' => 1,
            ]],
        ];
        $firstReturn = $returns->create($user->id, $order, $payload);
        try {
            $returns->create($user->id, $order->fresh(), $payload);
            $this->fail('A second return must not reserve an already returned item quantity.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        config(['checkout.restore_stock_on_return' => false]);
        $returns->updateStatus($firstReturn, 'approved');
        $returns->updateStatus($firstReturn->fresh(), 'received');
        $returns->refund($firstReturn, ['amount' => 100]);

        $this->assertLessThanOrEqual(
            (float) $order->total,
            (float) Refund::where('order_id', $order->id)->sum('amount'),
            'Pending and completed refunds for one order must not exceed the server-owned order total.'
        );
    }

    public function test_checkout_internal_failures_use_a_safe_server_error_contract(): void
    {
        $user = User::factory()->create();
        $address = Addresses::create([
            'user_id' => $user->id,
            'type' => 'shipping',
            'name' => 'Audit User',
            'phone' => '01000000000',
            'country' => 'Egypt',
            'country_code' => 'EG',
            'city' => 'Cairo',
            'street' => 'Audit street',
        ]);
        Sanctum::actingAs($user);

        $checkout = Mockery::mock(CheckoutService::class);
        $checkout->shouldReceive('placeOrder')
            ->once()
            ->andThrow(new RuntimeException('SENTINEL_INTERNAL_CHECKOUT_EXCEPTION'));
        $this->app->instance(CheckoutService::class, $checkout);

        $response = $this->postJson('/api/v1/checkout/place-order', [
            'shipping_address_id' => $address->id,
            'payment_method' => 'paymob',
            'payment_channel' => 'card',
            'idempotency_key' => 'post-audit-error-contract-001',
        ]);

        $this->assertStringNotContainsString('SENTINEL_INTERNAL_CHECKOUT_EXCEPTION', $response->getContent());
        $response->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Server error.')
            ->assertJsonStructure(['success', 'message', 'data', 'errors', 'request_id']);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            (string) $response->json('request_id')
        );
    }
}
