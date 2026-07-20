<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItems;
use App\Models\Products;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartAndCheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_quantity_update_is_validated_and_returns_totals(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Products::factory()->create(['price' => 40, 'is_active' => true]);
        Stock::create(['product_id' => $product->id, 'quantity' => 5]);
        $cart = Cart::create(['user_id' => $user->id]);
        $item = CartItems::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 40,
        ]);

        $this->withHeader('X-API-KEY', env('API_KEY'))->putJson("/api/v1/cart/items/{$item->id}", ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('data.items.0.quantity', 3)
            ->assertJsonPath('data.total', 120);

        $this->withHeader('X-API-KEY', env('API_KEY'))->putJson("/api/v1/cart/items/{$item->id}", ['quantity' => 9])
            ->assertStatus(422);
    }

    public function test_cash_on_delivery_checkout_creates_order_items_and_clears_cart(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Products::factory()->create(['price' => 55, 'is_active' => true]);
        Stock::create(['product_id' => $product->id, 'quantity' => 5]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItems::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 110,
        ]);

        $this->withHeader('X-API-KEY', env('API_KEY'))->postJson('/api/v1/pay', [
            'payment_method' => 'cash_on_delivery',
            'phone' => '01000000000',
            'address' => '12 Market Street',
            'city' => 'Cairo',
        ])
            ->assertOk()
            ->assertJsonPath('data.payment_method', 'cash_on_delivery')
            ->assertJsonPath('data.total', 110);

        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
        $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'quantity' => 3]);
    }
}
