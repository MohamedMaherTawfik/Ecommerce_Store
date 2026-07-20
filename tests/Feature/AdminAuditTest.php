<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_and_route_protection_work(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin.marketplace@gmail.com',
            'password' => Hash::make('Admin123!'),
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'email' => 'user.marketplace@gmail.com',
            'password' => Hash::make('User123!'),
            'role' => 'user',
        ]);

        $this->api()->postJson('/api/admin/login', [
            'email' => $user->email,
            'password' => 'User123!',
        ])->assertStatus(401);

        $adminLogin = $this->api()->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'Admin123!',
        ])->assertOk();

        $adminToken = (string) $adminLogin->json('data.token');
        $headers = ['Authorization' => 'Bearer '.$adminToken];

        Sanctum::actingAs($user);
        $this->api()->getJson('/api/admin/products')
            ->assertStatus(401);

        Sanctum::actingAs($admin);
        $this->api()->getJson('/api/admin/products')
            ->assertOk();

        $this->api()->getJson('/api/admin/brands')
            ->assertOk();
    }

    public function test_admin_crud_works_for_brands_categories_products_and_coupons(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'email' => 'crudadmin.marketplace@gmail.com',
            'password' => Hash::make('Admin123!'),
            'role' => 'admin',
        ]);

        $token = $admin->createToken('admin-audit')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        $categoryCreate = $this->api()->withHeaders($headers)->postJson('/api/admin/categories/create', [
            'name' => 'Audit Category',
        ])->assertOk();
        $categoryId = (int) $categoryCreate->json('data.id');
        $this->assertDatabaseHas('categories', ['id' => $categoryId, 'name' => 'Audit Category']);

        $this->api()->withHeaders($headers)->getJson('/api/admin/categories')
            ->assertOk();
        $this->api()->withHeaders($headers)->getJson('/api/admin/categories/'.$categoryId)
            ->assertOk();

        $this->api()->withHeaders($headers)->postJson('/api/admin/categories/'.$categoryId, [
            'name' => 'Audit Category Updated',
        ])->assertOk();
        $this->assertDatabaseHas('categories', ['id' => $categoryId, 'name' => 'Audit Category Updated']);

        $brandCreate = $this->api()->withHeaders($headers)->postJson('/api/admin/brands/create', [
            'name' => 'Audit Brand',
        ])->assertOk();
        $brandId = (int) $brandCreate->json('data.id');
        $this->assertDatabaseHas('brands', ['id' => $brandId, 'name' => 'Audit Brand']);

        $productCreate = $this->api()->withHeaders($headers)->postJson('/api/admin/products/create', [
            'name' => 'Audit Tee',
            'description' => 'Marketplace audit product',
            'price' => 49.99,
            'categories_id' => $categoryId,
            'brands_id' => $brandId,
            'quantity' => 7,
            'is_active' => true,
            'is_featured' => false,
            'return_policy' => '7 days',
            'meta_title' => 'Audit Tee',
            'meta_description' => 'Audit product meta',
            'sizes' => json_encode(['M', 'L']),
            'colors' => json_encode(['Black', 'White']),
        ])->assertOk();

        $productId = (int) $productCreate->json('data.id');
        $this->assertDatabaseHas('products', ['id' => $productId, 'name' => 'Audit Tee']);
        $this->assertDatabaseHas('stocks', ['product_id' => $productId, 'quantity' => 7]);

        $this->api()->withHeaders($headers)->postJson('/api/admin/products/'.$productId, [
            'name' => 'Audit Tee Updated',
            'description' => 'Updated product',
            'price' => 59.99,
            'categories_id' => $categoryId,
            'brands_id' => $brandId,
            'quantity' => 9,
            'is_active' => true,
            'is_featured' => true,
            'return_policy' => '14 days',
            'meta_title' => 'Audit Tee Updated',
            'meta_description' => 'Audit product meta updated',
            'sizes' => json_encode(['XL']),
            'colors' => json_encode(['Navy']),
        ])->assertOk();
        $this->assertDatabaseHas('products', ['id' => $productId, 'name' => 'Audit Tee Updated']);
        $this->assertDatabaseHas('stocks', ['product_id' => $productId, 'quantity' => 9]);

        $couponCreate = $this->api()->withHeaders($headers)->postJson('/api/admin/coupons/create', [
            'code' => 'AUDIT10',
            'type' => 'percentage',
            'value' => 10,
            'expires_at' => now()->addDays(7)->toDateString(),
            'usage_limit' => 3,
            'is_active' => true,
        ])->assertOk();
        $couponId = (int) $couponCreate->json('data.id');
        $this->assertDatabaseHas('coupons', ['id' => $couponId, 'code' => 'AUDIT10']);

        $this->api()->withHeaders($headers)->putJson('/api/admin/coupons/'.$couponId, [
            'code' => 'AUDIT20',
            'type' => 'fixed',
            'value' => 20,
            'expires_at' => now()->addDays(14)->toDateString(),
            'usage_limit' => 5,
            'is_active' => true,
        ])->assertOk();
        $this->assertDatabaseHas('coupons', ['id' => $couponId, 'code' => 'AUDIT20']);

        $this->api()->withHeaders($headers)->deleteJson('/api/admin/coupons/'.$couponId)
            ->assertOk();
        $this->assertDatabaseMissing('coupons', ['id' => $couponId]);

        $this->api()->withHeaders($headers)->deleteJson('/api/admin/products/'.$productId)
            ->assertOk();
        $this->assertSoftDeleted('products', ['id' => $productId]);

        $this->api()->withHeaders($headers)->deleteJson('/api/admin/categories/'.$categoryId)
            ->assertOk();
        $this->assertSoftDeleted('categories', ['id' => $categoryId]);

        $this->api()->withHeaders($headers)->deleteJson('/api/admin/brands/'.$brandId)
            ->assertOk();
        $this->assertSoftDeleted('brands', ['id' => $brandId]);
    }
}
