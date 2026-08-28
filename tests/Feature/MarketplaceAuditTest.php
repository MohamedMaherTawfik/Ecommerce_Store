<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\brands;
use App\Models\Cart;
use App\Models\CartItems;
use App\Models\Categories;
use App\Models\Coupon;
use App\Models\Products;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class MarketplaceAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_routes_are_accessible_without_api_key(): void
    {
        $this->getJson('/api/v1/categories')
            ->assertOk();
    }

    public function test_public_catalog_routes_return_consistent_payloads(): void
    {
        $category = Categories::factory()->create(['name' => 'Shirts']);
        $brand = brands::factory()->create(['name' => 'Urban Loom']);
        $matching = Products::factory()->create([
            'name' => 'Cotton Tee',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'price' => 25,
            'is_active' => true,
        ]);
        $other = Products::factory()->create([
            'name' => 'Denim Jacket',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'price' => 120,
            'is_active' => true,
        ]);
        Stock::create(['product_id' => $matching->id, 'quantity' => 10]);
        Stock::create(['product_id' => $other->id, 'quantity' => 4]);

        $this->api()->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Shirts');

        $this->api()->getJson('/api/v1/brands')
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Urban Loom');

        $this->api()->getJson('/api/v1/products?category_id='.$category->id.'&brand_id='.$brand->id.'&sort=price_asc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Cotton Tee');

        $this->api()->getJson('/api/v1/products/'.$matching->id)
            ->assertOk()
            ->assertJsonPath('data.id', $matching->id)
            ->assertJsonPath('data.stock', 10);

        $this->api()->getJson('/api/v1/products/'.$matching->id.'/related')
            ->assertOk()
            ->assertJsonPath('data.0.id', $other->id);

        $this->api()->getJson('/api/v1/home-content')->assertOk();
        $this->api()->getJson('/api/v1/layout')->assertOk();

        $this->api()->getJson('/api/v1/products/999999')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Product not found');
    }

    public function test_user_auth_flow_covers_otp_registration_profile_password_and_wallet(): void
    {
        Mail::fake();
        Storage::fake('public');

        $email = 'buyer.qa@gmail.com';
        $password = 'R8&kT4!mN7';

        $this->api()->postJson('/api/v1/users/send-otp', [
            'email' => $email,
        ])->assertOk();

        $otp = Cache::get('otp_'.$email);
        $this->assertNotNull($otp);
        Mail::assertQueued(OtpMail::class, fn (OtpMail $mail) => $mail->otp === $otp);

        $this->api()->postJson('/api/v1/users/verify-otp', [
            'email' => $email,
            'otp' => $otp,
        ])->assertOk();

        $registration = $this->api()->postJson('/api/v1/users/register', [
            'name' => 'Buyer QA',
            'email' => $email,
            'phone' => '01000000000',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertOk();

        $userId = (int) $registration->json('data.user.id');
        $registration->assertJsonMissingPath('data.token');
        $this->assertTrue($registration->getCookie(config('auth_cookie.name'), false)->isHttpOnly());

        $login = $this->api()->postJson('/api/v1/users/login', [
            'email' => $email,
            'password' => $password,
        ])->assertOk();
        $login->assertJsonMissingPath('data.token');
        $cookie = $login->getCookie(config('auth_cookie.name'), false);
        $this->assertTrue($cookie->isHttpOnly());

        $this->api()->withCredentials()->withUnencryptedCookie(config('auth_cookie.name'), $cookie->getValue())->getJson('/api/v1/users/profile')
            ->assertOk()
            ->assertJsonPath('data.user.email', $email);

        $this->api()->withUnencryptedCookie(config('auth_cookie.name'), $cookie->getValue())->postJson('/api/v1/users/update-profile', [
            'name' => 'Buyer QA Updated',
            'email' => $email,
            'phone' => '01111111111',
        ])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $userId, 'phone' => '01111111111']);

        $walletResponse = $this->api()->withUnencryptedCookie(config('auth_cookie.name'), $cookie->getValue())->getJson('/api/v1/users/wallet')
            ->assertOk();
        $this->assertNotNull($walletResponse->json('data.wallet'));
        $this->assertDatabaseHas('wallets', ['user_id' => $userId]);

        $walletResponseAgain = $this->api()->withUnencryptedCookie(config('auth_cookie.name'), $cookie->getValue())->getJson('/api/v1/users/wallet')
            ->assertOk();
        $this->assertNotNull($walletResponseAgain->json('data.wallet'));
        $this->assertDatabaseCount('wallets', 1);

        $this->api()->withUnencryptedCookie(config('auth_cookie.name'), $cookie->getValue())->postJson('/api/v1/users/logout')
            ->assertOk();

        $this->api()->withUnencryptedCookie(config('auth_cookie.name'), $cookie->getValue())->getJson('/api/v1/users/profile')
            ->assertStatus(401);

        $reLogin = $this->api()->postJson('/api/v1/users/login', [
            'email' => $email,
            'password' => $password,
        ])->assertOk();
        $reLoginCookie = $reLogin->getCookie(config('auth_cookie.name'), false);

        $this->api()->withUnencryptedCookie(config('auth_cookie.name'), $reLoginCookie->getValue())->postJson('/api/v1/users/password', [
            'current_password' => $password,
            'new_password' => 'Q9!zL7@pR2',
            'confirm_password' => 'Q9!zL7@pR2',
        ])->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->app['auth']->forgetGuards();
        $this->api()->withUnencryptedCookie(config('auth_cookie.name'), $reLoginCookie->getValue())->getJson('/api/v1/users/profile')
            ->assertStatus(401);

        $finalLogin = $this->api()->postJson('/api/v1/users/login', [
            'email' => $email,
            'password' => 'Q9!zL7@pR2',
        ])->assertOk();
        $finalCookie = $finalLogin->getCookie(config('auth_cookie.name'), false);

        $this->api()->withUnencryptedCookie(config('auth_cookie.name'), $finalCookie->getValue())->deleteJson('/api/v1/users/delete-account')
            ->assertOk();

    }

    public function test_deleted_user_cannot_log_in_after_account_removal(): void
    {
        $user = User::factory()->create([
            'email' => 'deleted.qa@gmail.com',
            'password' => Hash::make('Password123!'),
        ]);

        $token = $user->createToken('deleted-user')->plainTextToken;

        $this->api()->withHeaders(['Authorization' => 'Bearer '.$token])->deleteJson('/api/v1/users/delete-account')
            ->assertOk();

        $this->api()->postJson('/api/v1/users/login', [
            'email' => 'deleted.qa@gmail.com',
            'password' => 'Password123!',
        ])->assertStatus(401);
    }

    public function test_google_login_and_callback_create_or_link_accounts(): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('redirect')->andReturn(redirect()->away('https://accounts.google.com/mock'));

        Socialite::shouldReceive('driver')
            ->with('google')
            ->twice()
            ->andReturn($driver);

        $this->api()->get('/api/v1/users/google-login')
            ->assertRedirect('https://accounts.google.com/mock');

        $googleUser = new class
        {
            public function getEmail(): string
            {
                return 'google.qa@example.com';
            }

            public function getName(): string
            {
                return 'Google QA';
            }

            public function getId(): string
            {
                return 'google-123';
            }

            public function getAvatar(): string
            {
                return 'https://example.com/avatar.png';
            }
        };

        $driver->shouldReceive('user')->andReturn($googleUser);

        $response = $this->api()->get('/api/v1/users/google-callback');
        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'google.qa@example.com',
            'google_id' => 'google-123',
        ]);

        $this->assertStringEndsWith('/auth/google-success', (string) $response->headers->get('Location'));
        $this->assertStringNotContainsString('token=', (string) $response->headers->get('Location'));
        $this->assertTrue($response->getCookie(config('auth_cookie.name'), false)->isHttpOnly());
    }

    public function test_cart_wishlist_and_review_flows_handle_common_edge_cases(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
        ]);
        $product = Products::factory()->create([
            'price' => 50,
            'is_active' => true,
        ]);
        Stock::create([
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
        $coupon = Coupon::create([
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 10,
            'expires_at' => now()->addDay(),
            'usage_limit' => 5,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $token = $user->createToken('qa-token')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->api()->withHeaders($headers)->postJson('/api/v1/cart/addToCart/'.$product->id, [
            'quantity' => 1,
            'size' => 'L',
            'color' => 'black',
        ])->assertOk()
            ->assertJsonPath('data.items.0.quantity', 1)
            ->assertJsonPath('data.total', 50);

        $cartItem = CartItems::firstOrFail();

        $this->api()->withHeaders($headers)->postJson('/api/v1/cart/addToCart/'.$product->id, [
            'quantity' => 1,
            'size' => 'L',
            'color' => 'black',
        ])->assertOk()
            ->assertJsonPath('data.items.0.quantity', 2);

        $this->api()->withHeaders($headers)->putJson('/api/v1/cart/items/'.$cartItem->id, [
            'quantity' => 3,
        ])->assertOk()
            ->assertJsonPath('data.items.0.quantity', 3)
            ->assertJsonPath('data.total', 150);

        $this->api()->withHeaders($headers)->putJson('/api/v1/cart/items/'.$cartItem->id, [
            'quantity' => 99,
        ])->assertStatus(422);

        $this->api()->withHeaders($headers)->postJson('/api/v1/cart/coupon', [
            'code' => 'save10',
        ])->assertOk()
            ->assertJsonPath('data.coupon.code', 'SAVE10')
            ->assertJsonPath('data.discount', 15);

        $expiredCoupon = Coupon::create([
            'code' => 'OLD10',
            'type' => 'percentage',
            'value' => 10,
            'expires_at' => now()->subDay(),
            'usage_limit' => 1,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $this->api()->withHeaders($headers)->postJson('/api/v1/cart/coupon', [
            'code' => $expiredCoupon->code,
        ])->assertStatus(422);

        $this->api()->withHeaders($headers)->deleteJson('/api/v1/cart/coupon')
            ->assertOk()
            ->assertJsonPath('data.coupon', null);

        $this->api()->withHeaders($headers)->deleteJson('/api/v1/cart/delete/'.$cartItem->id)
            ->assertOk();

        $this->api()->withHeaders($headers)->deleteJson('/api/v1/cart/clearCart')
            ->assertOk();

        $this->api()->withHeaders($headers)->postJson('/api/v1/wishlist/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.wishlisted', true);

        $this->api()->withHeaders($headers)->postJson('/api/v1/wishlist/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.wishlisted', false);

        $this->api()->withHeaders($headers)->deleteJson('/api/v1/wishlist/'.$product->id)
            ->assertOk();

        $this->api()->withHeaders($headers)->postJson('/api/v1/products/'.$product->id.'/reviews', [
            'rating' => 5,
            'comment' => 'Excellent product.',
        ])->assertOk()
            ->assertJsonPath('data.rating', 5);

        $this->api()->withHeaders($headers)->postJson('/api/v1/products/'.$product->id.'/reviews', [
            'rating' => 4,
            'comment' => 'Still good after retest.',
        ])->assertOk();

        $this->assertDatabaseCount('reviews', 1);

        $this->api()->withHeaders($headers)->postJson('/api/v1/products/'.$product->id.'/reviews', [
            'rating' => 6,
            'comment' => 'Invalid rating should fail.',
        ])->assertStatus(422);

        $this->api()->withHeaders($headers)->postJson('/api/v1/products/999999/reviews', [
            'rating' => 5,
            'comment' => 'Missing product should fail.',
        ])->assertStatus(404);

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_payment_flow_initializes_paymob_unified_checkout(): void
    {
        config([
            'payment.gateways.paymob.enabled' => true,
            'payment.gateways.paymob.base_url' => 'https://accept.paymob.com',
            'payment.gateways.paymob.secret_key' => 'paymob-secret',
            'payment.gateways.paymob.public_key' => 'paymob-public',
            'payment.gateways.paymob.hmac_secret' => 'paymob-hmac',
            'payment.gateways.paymob.currency' => 'EGP',
            'payment.gateways.paymob.integration_ids.card' => 12345,
        ]);
        Http::fake([
            'accept.paymob.com/v1/intention/' => Http::response([
                'id' => 'int_marketplace_1',
                'client_secret' => 'cs_marketplace_1',
            ]),
        ]);

        $user = User::factory()->create();
        $token = $user->createToken('checkout-token')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        $product = Products::factory()->create([
            'price' => 80,
            'is_active' => true,
        ]);
        Stock::create([
            'product_id' => $product->id,
            'quantity' => 4,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItems::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 160,
            'size' => 'L',
            'color' => 'black',
        ]);

        $checkout = $this->api()->withHeaders($headers)->postJson('/api/v1/pay', [
            'payment_method' => 'paymob',
            'payment_channel' => 'card',
            'phone' => '01000000000',
            'address' => '12 Market Street',
            'idempotency_key' => 'marketplace-checkout-001',
            'city' => 'Cairo',
            'country' => 'Egypt',
        ])->assertOk();

        $orderId = (int) $checkout->json('data.order_id');
        $this->assertStringContainsString(
            '/unifiedcheckout/?publicKey=paymob-public&clientSecret=cs_marketplace_1',
            (string) $checkout->json('data.payment_url')
        );
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'payment_method' => 'paymob',
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $orderId,
            'gateway' => 'paymob',
            'gateway_order_id' => 'int_marketplace_1',
        ]);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->api()->withHeaders($headers)->getJson('/api/v1/order/status/'.$orderId)
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_status', 'pending');
    }

    public function test_public_products_and_wishlist_return_404_for_missing_products(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('public-token')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->api()->getJson('/api/v1/products/999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Product not found');

        $this->api()->getJson('/api/v1/products/999999/related')
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Product not found');

        $this->api()->withHeaders($headers)->postJson('/api/v1/wishlist/999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false);

        $this->api()->withHeaders($headers)->deleteJson('/api/v1/wishlist/999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_unverified_payment_webhooks_are_rejected(): void
    {
        config([
            'payment.gateways.paymob.hmac_secret' => 'paymob-secret',
        ]);

        $this->postJson('/api/v1/webhooks/paymob', ['obj' => ['id' => 123]])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Invalid Paymob webhook signature.');

        $this->postJson('/api/v1/webhooks/paymob?hmac=invalid', ['obj' => ['id' => 124]])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Invalid Paymob webhook signature.');
    }
}
