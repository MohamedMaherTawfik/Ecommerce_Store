<?php

namespace Tests\Feature;

use App\Models\Addresses;
use App\Models\Cart;
use App\Models\CartItems;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\Permission;
use App\Models\Products;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_resources_cannot_be_read_or_mutated_by_another_customer(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $product = Products::factory()->create();
        $address = Addresses::create([
            'user_id' => $owner->id,
            'type' => 'shipping',
            'name' => 'Owner address',
            'phone' => '01000000000',
            'country' => 'Egypt',
            'city' => 'Cairo',
            'street' => 'Private street',
        ]);
        $cart = Cart::create(['user_id' => $owner->id]);
        $item = CartItems::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 10,
        ]);
        $order = Orders::create([
            'user_id' => $owner->id,
            'order_number' => 'IDOR-1001',
            'status' => 'delivered',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'subtotal' => 10,
            'total' => 10,
            'phone' => '01000000000',
            'address' => 'Private street',
        ]);
        $orderItem = OrderItems::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 10,
        ]);
        $return = ReturnRequest::create([
            'order_id' => $order->id,
            'user_id' => $owner->id,
            'reason' => 'Private return',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($attacker);

        $this->getJson("/api/v1/addresses/{$address->id}")->assertNotFound();
        $this->putJson("/api/v1/addresses/{$address->id}", [
            'type' => 'shipping',
            'name' => 'Hijacked',
            'phone' => '01111111111',
            'country' => 'Egypt',
            'city' => 'Cairo',
            'street' => 'Changed',
        ])->assertNotFound();
        $this->deleteJson("/api/v1/addresses/{$address->id}")->assertNotFound();
        $this->putJson("/api/v1/cart/items/{$item->id}", ['quantity' => 2])->assertNotFound();
        $this->deleteJson("/api/v1/cart/delete/{$item->id}")->assertNotFound();
        $this->getJson("/api/v1/orders/{$order->id}")->assertNotFound();
        $this->getJson("/api/v1/order/status/{$order->id}")->assertNotFound();
        $this->getJson("/api/v1/returns/{$return->id}")->assertNotFound();
        $this->postJson("/api/v1/returns/{$return->id}/cancel")->assertNotFound();
        $this->postJson("/api/v1/orders/{$order->id}/returns", [
            'reason' => 'Attempted cross-account return',
            'items' => [[
                'order_item_id' => $orderItem->id,
                'quantity' => 1,
            ]],
        ])->assertNotFound();

        $this->assertDatabaseHas('addresses', ['id' => $address->id, 'name' => 'Owner address']);
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 1]);
        $this->assertDatabaseHas('return_requests', ['id' => $return->id, 'status' => 'pending']);
    }

    public function test_representative_admin_modules_enforce_auth_role_and_module_permission(): void
    {
        $customer = User::factory()->create(['role' => 'user']);
        $staff = User::factory()->create(['role' => 'limited_auditor']);
        $admin = User::factory()->create(['role' => 'admin']);
        $permission = Permission::firstOrCreate(
            ['name' => 'dashboard.view'],
            ['module' => 'dashboard', 'label' => 'View dashboard']
        );
        DB::table('role_permissions')->insert([
            'role' => $staff->role,
            'permission_id' => $permission->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $paths = [
            '/api/admin/orders',
            '/api/admin/returns',
            '/api/admin/users',
            '/api/admin/products',
            '/api/admin/email-templates',
            '/api/admin/settings/database',
            '/api/admin/settings/application',
        ];

        foreach ($paths as $path) {
            $this->app['auth']->forgetGuards();
            $this->getJson($path)->assertUnauthorized();
        }

        foreach ([$customer, $staff] as $actor) {
            Sanctum::actingAs($actor);

            foreach ($paths as $path) {
                $this->getJson($path)->assertForbidden();
            }
        }

        Sanctum::actingAs($admin);

        foreach ($paths as $path) {
            $this->getJson($path)->assertOk();
        }
    }
}
