<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItems;
use App\Models\Products;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

        $this->putJson("/api/v1/cart/items/{$item->id}", ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('data.items.0.quantity', 3)
            ->assertJsonPath('data.total', 120);

        $this->putJson("/api/v1/cart/items/{$item->id}", ['quantity' => 9])
            ->assertStatus(422);
    }

    public function test_paymob_checkout_creates_order_items_and_clears_cart(): void
    {
        config([
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
                'id' => 'int_cart_checkout',
                'client_secret' => 'cs_cart_checkout',
            ]),
        ]);

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

        $this->postJson('/api/v1/pay', [
            'payment_method' => 'paymob',
            'payment_channel' => 'card',
            'phone' => '01000000000',
            'address' => '12 Market Street',
            'idempotency_key' => 'cart-checkout-api-001',
            'city' => 'Cairo',
        ])
            ->assertOk()
            ->assertJsonPath('data.gateway', 'paymob')
            ->assertJsonPath('data.total', 110);

        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
        $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'quantity' => 3]);
    }
}
