<?php

namespace Tests\Feature;

use App\Models\Categories;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\Products;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_are_accurate_and_ignore_unpaid_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($admin);

        $category = Categories::create(['name' => 'Shoes', 'slug' => 'shoes', 'is_active' => true]);
        $product = Products::create([
            'name' => 'Runner',
            'slug' => 'runner',
            'sku' => 'RUN-1',
            'price' => 25,
            'category_id' => $category->id,
        ]);

        $paid = $this->order($customer, 'PAID-1', 100, 'paid', 'delivered');
        $unpaid = $this->order($customer, 'UNPAID-1', 500, 'pending', 'pending');
        OrderItems::create(['order_id' => $paid->id, 'product_id' => $product->id, 'quantity' => 4, 'price' => 25, 'unit_price' => 25, 'total_price' => 100]);
        OrderItems::create(['order_id' => $unpaid->id, 'product_id' => $product->id, 'quantity' => 20, 'price' => 25, 'unit_price' => 25, 'total_price' => 500]);

        $params = '?period=yearly&date_from='.now()->subDay()->toDateString().'&date_to='.now()->addDay()->toDateString();

        $this->api()->getJson('/api/admin/analytics/sales'.$params)
            ->assertOk()
            ->assertJsonPath('data.total_orders', 2)
            ->assertJsonPath('data.paid_orders', 1)
            ->assertJsonPath('data.total_revenue', 100)
            ->assertJsonPath('data.items_sold', 4);

        $this->api()->getJson('/api/admin/analytics/top-products'.$params)
            ->assertOk()->assertJsonPath('data.0.total_sold', 4);
        $this->api()->getJson('/api/admin/analytics/top-categories'.$params)
            ->assertOk()->assertJsonPath('data.0.name', 'Shoes');
        $this->api()->getJson('/api/admin/analytics/top-customers'.$params)
            ->assertOk()->assertJsonPath('data.0.total_spent', 100);
        $this->api()->getJson('/api/admin/analytics/revenue'.$params)
            ->assertOk()->assertJsonPath('data.0.value', 100);
        $this->api()->getJson('/api/admin/dashboard/statistics'.$params)
            ->assertOk()->assertJsonPath('data.total_sales', 100);
    }

    public function test_reports_return_empty_arrays_on_an_empty_database(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        foreach (['revenue', 'top-products', 'top-categories', 'top-customers'] as $report) {
            $this->api()->getJson("/api/admin/analytics/{$report}")->assertOk()->assertJsonPath('data', []);
        }
    }

    private function order(User $user, string $number, float $total, string $paymentStatus, string $status): Orders
    {
        return Orders::create([
            'user_id' => $user->id,
            'order_number' => $number,
            'status' => $status,
            'order_status' => $status === 'delivered' ? 'completed' : 'pending',
            'payment_status' => $paymentStatus,
            'payment_method' => 'test',
            'subtotal' => $total,
            'total' => $total,
            'phone' => '01000000000',
            'address' => 'Test address',
            'country' => 'Egypt',
        ]);
    }
}
