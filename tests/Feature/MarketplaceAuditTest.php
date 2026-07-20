<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\brands;
use App\Models\Cart;
use App\Models\CartItems;
use App\Models\Categories;
use App\Models\Coupon;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\Products;
use App\Models\Reviews;
use App\Models\Stock;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\PayPalServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class MarketplaceAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_key_is_required_for_public_api_routes(): void
    {
        $this->getJson('/api/v1/categories')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Must provide valid api key');
    }

    public function test_public_catalog_routes_return_consistent_payloads(): void
    {
        $category = Categories::factory()->create(['name' => 'Shirts']);
        $brand = brands::factory()->create(['name' => 'Urban Loom']);
        $matching = Products::factory()->create([
            'name' => 'Cotton Tee',
            'categories_id' => $category->id,
            'brands_id' => $brand->id,
            'price' => 25,
            'is_active' => true,
        ]);
        $other = Products::factory()->create([
            'name' => 'Denim Jacket',
            'categories_id' => $category->id,
            'brands_id' => $brand->id,
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

        $this->api()->getJson('/api/v1/products?category_id=' . $category->id . '&brand_id=' . $brand->id . '&sort=price_asc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Cotton Tee');

        $this->api()->getJson('/api/v1/products/' . $matching->id)
            ->assertOk()
            ->assertJsonPath('data.id', $matching->id)
            ->assertJsonPath('data.stock', 10);

        $this->api()->getJson('/api/v1/products/' . $matching->id . '/related')
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

        $otp = Cache::get('otp_' . $email);
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

        $token = (string) $registration->json('data.token');
        $userId = (int) $registration->json('data.user.id');
        $this->assertNotEmpty($token);

        $login = $this->api()->postJson('/api/v1/users/login', [
            'email' => $email,
            'password' => $password,
        ])->assertOk();
        $loginToken = (string) $login->json('data.token');
        $this->assertNotEmpty($loginToken);

        $headers = [
            'Authorization' => 'Bearer ' . $loginToken,
        ];

        $this->api()->withHeaders($headers)->getJson('/api/v1/users/profile')
            ->assertOk()
            ->assertJsonPath('data.user.email', $email);

        $this->api()->withHeaders($headers)->postJson('/api/v1/users/update-profile', [
            'name' => 'Buyer QA Updated',
            'email' => $email,
            'phone' => '01111111111',
        ])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $userId, 'phone' => '01111111111']);

        $this->api()->withHeaders($headers)->postJson('/api/v1/users/password', [
            'current_password' => $password,
            'new_password' => 'Q9!zL7@pR2',
            'confirm_password' => 'Q9!zL7@pR2',
        ])->assertOk();

        $walletResponse = $this->api()->withHeaders($headers)->getJson('/api/v1/users/wallet')
            ->assertOk();
        $this->assertNotNull($walletResponse->json('data.wallet'));
        $this->assertDatabaseHas('wallets', ['user_id' => $userId]);

        $walletResponseAgain = $this->api()->withHeaders($headers)->getJson('/api/v1/users/wallet')
            ->assertOk();
        $this->assertNotNull($walletResponseAgain->json('data.wallet'));
        $this->assertDatabaseCount('wallets', 1);

        $this->api()->withHeaders($headers)->postJson('/api/v1/users/logout')
            ->assertOk();

        $this->api()->withHeaders($headers)->getJson('/api/v1/users/profile')
            ->assertStatus(401);

        $this->api()->withHeaders(['Authorization' => 'Bearer ' . $token])->getJson('/api/v1/users/profile')
            ->assertStatus(401);

        $reLogin = $this->api()->postJson('/api/v1/users/login', [
            'email' => $email,
            'password' => 'Q9!zL7@pR2',
        ])->assertOk();
        $reLoginToken = (string) $reLogin->json('data.token');

        $this->api()->withHeaders(['Authorization' => 'Bearer ' . $reLoginToken])->deleteJson('/api/v1/users/delete-account')
            ->assertOk();

    }

    public function test_deleted_user_cannot_log_in_after_account_removal(): void
    {
        $user = User::factory()->create([
            'email' => 'deleted.qa@gmail.com',
            'password' => Hash::make('Password123!'),
        ]);

        $token = $user->createToken('deleted-user')->plainTextToken;

        $this->api()->withHeaders(['Authorization' => 'Bearer ' . $token])->deleteJson('/api/v1/users/delete-account')
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

        $googleUser = new class () {
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

        $this->assertStringContainsString('/auth/google-success?token=', (string) $response->headers->get('Location'));
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
        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->api()->withHeaders($headers)->postJson('/api/v1/cart/addToCart/' . $product->id, [
            'quantity' => 1,
            'size' => 'L',
            'color' => 'black',
        ])->assertOk()
            ->assertJsonPath('data.items.0.quantity', 1)
            ->assertJsonPath('data.total', 50);

        $cartItem = CartItems::firstOrFail();

        $this->api()->withHeaders($headers)->postJson('/api/v1/cart/addToCart/' . $product->id, [
            'quantity' => 1,
            'size' => 'L',
            'color' => 'black',
        ])->assertOk()
            ->assertJsonPath('data.items.0.quantity', 2);

        $this->api()->withHeaders($headers)->putJson('/api/v1/cart/items/' . $cartItem->id, [
            'quantity' => 3,
        ])->assertOk()
            ->assertJsonPath('data.items.0.quantity', 3)
            ->assertJsonPath('data.total', 150);

        $this->api()->withHeaders($headers)->putJson('/api/v1/cart/items/' . $cartItem->id, [
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

        $this->api()->withHeaders($headers)->deleteJson('/api/v1/cart/delete/' . $cartItem->id)
            ->assertOk();

        $this->api()->withHeaders($headers)->deleteJson('/api/v1/cart/clearCart')
            ->assertOk();

        $this->api()->withHeaders($headers)->postJson('/api/v1/wishlist/' . $product->id)
            ->assertOk()
            ->assertJsonPath('data.wishlisted', true);

        $this->api()->withHeaders($headers)->postJson('/api/v1/wishlist/' . $product->id)
            ->assertOk()
            ->assertJsonPath('data.wishlisted', false);

        $this->api()->withHeaders($headers)->deleteJson('/api/v1/wishlist/' . $product->id)
            ->assertOk();

        $this->api()->withHeaders($headers)->postJson('/api/v1/products/' . $product->id . '/reviews', [
            'rating' => 5,
            'comment' => 'Excellent product.',
        ])->assertOk()
            ->assertJsonPath('data.rating', 5);

        $this->api()->withHeaders($headers)->postJson('/api/v1/products/' . $product->id . '/reviews', [
            'rating' => 4,
            'comment' => 'Still good after retest.',
        ])->assertOk();

        $this->assertDatabaseCount('reviews', 1);

        $this->api()->withHeaders($headers)->postJson('/api/v1/products/' . $product->id . '/reviews', [
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

    public function test_payment_flow_handles_cash_on_delivery_and_mocked_paypal_behaviour(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('checkout-token')->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token];

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

        $cashOnDelivery = $this->api()->withHeaders($headers)->postJson('/api/v1/pay', [
            'payment_method' => 'cash_on_delivery',
            'phone' => '01000000000',
            'address' => '12 Market Street',
            'city' => 'Cairo',
            'country' => 'Egypt',
        ])->assertOk();

        $codOrderId = (int) $cashOnDelivery->json('data.order_id');
        $this->assertDatabaseHas('orders', [
            'id' => $codOrderId,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $paypalUser = User::factory()->create();
        $paypalToken = $paypalUser->createToken('paypal-token')->plainTextToken;
        $paypalHeaders = ['Authorization' => 'Bearer ' . $paypalToken];

        $paypalProduct = Products::factory()->create([
            'price' => 30,
            'is_active' => true,
        ]);
        Stock::create([
            'product_id' => $paypalProduct->id,
            'quantity' => 6,
        ]);

        $paypalCart = Cart::create(['user_id' => $paypalUser->id]);
        CartItems::create([
            'cart_id' => $paypalCart->id,
            'product_id' => $paypalProduct->id,
            'quantity' => 1,
            'price' => 30,
            'size' => 'M',
            'color' => 'navy',
        ]);

        $fakePaypal = Mockery::mock(PayPalServices::class);
        $fakePaypal->shouldReceive('pay')->andReturnUsing(function (array $data): array {
            $item = CartItems::query()->latest('id')->firstOrFail();
            $order = Orders::create([
                'user_id' => $data['user_id'],
                'order_number' => 'ORD-FAKE-' . str()->upper(str()->random(6)),
                'status' => 'pending',
                'payment_method' => 'paypal',
                'payment_status' => 'pending',
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'subtotal' => 30,
                'tax' => 0,
                'shipping_cost' => 0,
                'discount' => 0,
                'total' => 30,
                'phone' => $data['phone'],
                'address' => $data['address'],
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? 'Egypt',
                'notes' => $data['notes'] ?? null,
                'paypal_order_id' => 'PAYPAL-QA-001',
            ]);

            OrderItems::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => 30,
            ]);

            return [
                'order' => $order->fresh(),
                'approval_url' => 'https://paypal.test/approve',
            ];
        });
        $fakePaypal->shouldReceive('success')->andReturnUsing(function (string $token): array {
            $order = Orders::where('paypal_order_id', $token)->firstOrFail();
            $order->update([
                'status' => 'paid',
                'payment_status' => 'paid',
                'transaction_id' => 'CAPTURE-001',
                'paid_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Payment captured.',
                'order' => $order->fresh(),
            ];
        });
        $fakePaypal->shouldReceive('cancelByToken')->andReturnUsing(function (?string $token): array {
            if ($token) {
                Orders::where('paypal_order_id', $token)->update(['payment_status' => 'cancelled']);
            }

            return [
                'success' => false,
                'message' => 'Payment was cancelled by the user.',
            ];
        });
        $fakePaypal->shouldReceive('handleWebhook')->andReturnNull();

        app()->instance(PayPalServices::class, $fakePaypal);

        $paypalCheckout = $this->api()->withHeaders($paypalHeaders)->postJson('/api/v1/pay', [
            'payment_method' => 'paypal',
            'phone' => '01000000000',
            'address' => '12 Market Street',
            'city' => 'Cairo',
            'country' => 'Egypt',
            'idempotency_key' => 'paypal-qa-1',
        ])->assertOk();

        $paypalOrderId = (int) $paypalCheckout->json('data.order_id');
        $this->assertSame('https://paypal.test/approve', $paypalCheckout->json('data.approval_url'));
        $this->assertDatabaseHas('orders', [
            'id' => $paypalOrderId,
            'paypal_order_id' => 'PAYPAL-QA-001',
            'payment_method' => 'paypal',
        ]);

        $this->api()->withHeaders($paypalHeaders)->get('/api/v1/paypal/success?token=PAYPAL-QA-001')
            ->assertRedirect('/en/orders/' . $paypalOrderId);

        $this->api()->withHeaders($paypalHeaders)->getJson('/api/v1/order/status/' . $paypalOrderId)
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.payment_status', 'paid');

        $this->api()->withHeaders($paypalHeaders)->getJson('/api/v1/paypal/cancel?token=PAYPAL-QA-002')
            ->assertOk()
            ->assertJsonPath('success', false);

        $this->call('POST', '/api/v1/paypal/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{invalid')
            ->assertStatus(400);

        $this->api()->withHeaders($headers)->postJson('/api/v1/paypal/webhook', [
            'event_type' => 'CHECKOUT.ORDER.APPROVED',
            'resource' => [
                'id' => 'PAYPAL-QA-001',
            ],
        ])->assertStatus(500);
    }

    public function test_public_products_and_wishlist_return_404_for_missing_products(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('public-token')->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token];

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
            'services.stripe.webhook_secret' => 'whsec_test',
            'services.paymob.hmac_secret' => 'paymob-secret',
            'services.myfatoorah.webhook_secret' => 'myfatoorah-secret',
        ]);

        $this->postJson('/api/v1/webhooks/stripe', ['type' => 'payment_intent.succeeded'])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Invalid Stripe webhook signature.');

        $this->postJson('/api/v1/webhooks/paymob', ['obj' => ['id' => 123]])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Invalid Paymob webhook signature.');

        $this->withHeader('X-MyFatoorah-Signature', 'invalid')
            ->postJson('/api/v1/webhooks/myfatoorah', ['Event' => 'PaymentStatusChanged'])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Invalid MyFatoorah webhook signature.');

        $this->postJson('/api/v1/webhooks/bioneer', ['event' => 'payment'])
            ->assertStatus(501)
            ->assertJsonPath('message', 'Bioneer/Payoneer gateway is not implemented.');
    }
}
