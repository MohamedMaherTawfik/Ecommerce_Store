<?php

namespace Tests\Feature;

use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\Products;
use App\Models\ReturnRequest;
use App\Models\Stock;
use App\Models\User;
use App\Services\Checkout\ReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductionReadinessAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_frontend_api_key_matches_the_backend_configuration(): void
    {
        $source = file_get_contents(resource_path('js/services/ApiClient.js'));

        $this->assertIsString($source);
        $this->assertSame(1, preg_match("/const apiKey\\s*=\\s*['\"]([^'\"]+)['\"]/", $source, $matches));
        $this->assertTrue(
            hash_equals((string) config('services.api.key'), $matches[1]),
            'The browser API key does not match the server configuration.'
        );
    }

    public function test_admin_login_is_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 8; $attempt++) {
            $response = $this->postJson('/api/admin/login', [
                'email' => 'missing-admin@example.com',
                'password' => 'WrongPassword1',
            ]);
        }

        $response->assertTooManyRequests();
    }

    public function test_user_login_is_reachable_by_the_shipped_browser_client(): void
    {
        User::factory()->create([
            'email' => 'browser-login@example.com',
            'password' => Hash::make('ValidPassword1!'),
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/users/login', [
            'email' => 'browser-login@example.com',
            'password' => 'ValidPassword1!',
        ])->assertOk();
    }

    public function test_spa_fallback_does_not_swallow_unknown_api_routes(): void
    {
        $this->get('/en/cart')
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8');

        $this->api()->getJson('/api/v1/route-that-does-not-exist')
            ->assertNotFound()
            ->assertHeader('content-type', 'application/json');
    }

    public function test_repeated_received_transition_restores_inventory_only_once(): void
    {
        [$return, $stock] = $this->returnFixture();
        $service = app(ReturnService::class);

        $service->updateStatus($return, 'received');
        $service->updateStatus($return->fresh('items'), 'received');

        $this->assertSame(7, (int) $stock->fresh()->quantity);
    }

    public function test_duplicate_refund_requests_are_idempotent(): void
    {
        [$return] = $this->returnFixture();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $service = app(ReturnService::class);

        $service->refund($return, ['amount' => 20]);
        $service->refund($return->fresh('order'), ['amount' => 20]);

        $this->assertDatabaseCount('refunds', 1);
    }

    private function returnFixture(): array
    {
        $user = User::factory()->create();
        $product = Products::factory()->create(['stock_quantity' => 5]);
        $stock = Stock::create(['product_id' => $product->id, 'quantity' => 5]);
        $order = Orders::create([
            'user_id' => $user->id,
            'order_number' => 'AUDIT-'.fake()->unique()->numerify('######'),
            'status' => 'delivered',
            'payment_method' => 'paymob',
            'payment_status' => 'paid',
            'subtotal' => 20,
            'total' => 20,
            'phone' => '01000000000',
            'address' => 'Audit address',
        ]);
        $orderItem = OrderItems::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 20,
        ]);
        $return = ReturnRequest::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => 'Audit return',
            'status' => 'approved',
        ]);
        $return->items()->create([
            'order_item_id' => $orderItem->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'status' => 'approved',
        ]);

        return [$return->fresh(['items', 'order']), $stock];
    }
}
