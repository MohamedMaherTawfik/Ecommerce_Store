<?php

namespace Tests\Feature;

use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\Payment;
use App\Models\Products;
use App\Models\Refund;
use App\Models\User;
use App\Services\Checkout\ReturnService;
use App\Services\Payment\PaymentStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Mockery;
use Tests\TestCase;

class FinalSecurityRemediationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_login_creates_a_session_state_nonce(): void
    {
        config([
            'services.google.client_id' => 'test-client',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://localhost/api/v1/users/google-callback',
        ]);

        $response = $this->get('/api/v1/users/google-login')->assertRedirect();

        $this->assertNotEmpty(session('state'));
        $this->assertStringContainsString('state=', (string) $response->headers->get('Location'));
    }

    public function test_google_callback_preserves_existing_security_fields_and_rejects_disabled_accounts(): void
    {
        $password = Hash::make('Existing-Password-123!');
        $active = User::factory()->create([
            'email' => 'active-google@example.com',
            'name' => 'Existing Name',
            'password' => $password,
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->mockGoogleUser('active-google@example.com', 'Changed Name', 'google-active');
        $this->get('/api/v1/users/google-callback')->assertRedirect(config('app.frontend_url').'/auth/google-success');

        $active->refresh();
        $this->assertSame('Existing Name', $active->name);
        $this->assertSame('admin', $active->role);
        $this->assertSame($password, $active->password);
        $this->assertTrue((bool) $active->is_active);
        $this->assertSame('google-active', $active->google_id);

        Mockery::close();
        $disabled = User::factory()->create([
            'email' => 'disabled-matrix@example.com',
            'role' => 'admin',
            'is_active' => false,
        ]);
        $disabledPassword = $disabled->password;
        $this->mockGoogleUser('disabled-matrix@example.com', 'Changed Disabled', 'google-disabled');
        $this->get('/api/v1/users/google-callback')->assertRedirectContains('/auth/google-error');

        $disabled->refresh();
        $this->assertFalse((bool) $disabled->is_active);
        $this->assertSame('admin', $disabled->role);
        $this->assertSame($disabledPassword, $disabled->password);
        $this->assertNull($disabled->google_id);
    }

    public function test_google_callback_creates_only_customer_defaults_and_rejects_invalid_state(): void
    {
        $this->mockGoogleUser('new-google@example.com', 'New Google User', 'google-new');
        $this->get('/api/v1/users/google-callback')->assertRedirect(config('app.frontend_url').'/auth/google-success');
        $this->assertDatabaseHas('users', [
            'email' => 'new-google@example.com',
            'name' => 'New Google User',
            'role' => 'user',
            'is_active' => true,
            'google_id' => 'google-new',
        ]);

        Mockery::close();
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->once()->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($driver);
        $this->get('/api/v1/users/google-callback')
            ->assertRedirect(config('app.frontend_url').'/auth/google-error?message=Google+login+could+not+be+completed.');
    }

    public function test_google_callback_rejects_missing_email_and_repeated_callbacks_do_not_overwrite_profile_fields(): void
    {
        $missingEmailUser = new class
        {
            public function getEmail(): ?string
            {
                return null;
            }
        };
        $missingEmailDriver = Mockery::mock();
        $missingEmailDriver->shouldReceive('user')->once()->andReturn($missingEmailUser);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($missingEmailDriver);
        $this->get('/api/v1/users/google-callback')->assertRedirectContains('/auth/google-error');
        $this->assertDatabaseCount('users', 0);

        Mockery::close();
        $this->mockGoogleUser('repeat-google@example.com', 'Original Google Name', 'google-repeat-1');
        $this->get('/api/v1/users/google-callback')->assertRedirectContains('/auth/google-success');
        Mockery::close();
        $this->mockGoogleUser('repeat-google@example.com', 'Overwritten Google Name', 'google-repeat-2');
        $this->get('/api/v1/users/google-callback')->assertRedirectContains('/auth/google-success');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'email' => 'repeat-google@example.com',
            'name' => 'Original Google Name',
            'role' => 'user',
            'google_id' => 'google-repeat-2',
        ]);
    }

    public function test_overlapping_return_quantities_are_reserved_across_requests(): void
    {
        [$user, $order, $item] = $this->returnOrderFixture(2, '20.00');
        $service = app(ReturnService::class);
        $payload = fn (int $quantity) => [
            'reason' => 'Quantity reservation test',
            'items' => [['order_item_id' => $item->id, 'quantity' => $quantity]],
        ];

        $service->create($user->id, $order, $payload(1));
        $service->create($user->id, $order->fresh(), $payload(1));

        $this->expectException(ValidationException::class);
        $service->create($user->id, $order->fresh(), $payload(1));
    }

    public function test_cancelled_and_rejected_returns_release_their_quantity_reservations(): void
    {
        [$user, $order, $item] = $this->returnOrderFixture(2, '20.00');
        $service = app(ReturnService::class);
        $payload = fn (int $quantity) => [
            'reason' => 'Released reservation test',
            'items' => [['order_item_id' => $item->id, 'quantity' => $quantity]],
        ];

        $cancelled = $service->create($user->id, $order, $payload(1));
        $service->updateStatus($cancelled, 'cancelled');
        $rejected = $service->create($user->id, $order->fresh(), $payload(1));
        $service->updateStatus($rejected, 'rejected');
        $active = $service->create($user->id, $order->fresh(), $payload(2));

        $this->assertSame(2, (int) $active->items->sum('quantity'));
    }

    public function test_partial_refunds_never_exceed_return_value_or_paid_total(): void
    {
        [$user, $order, $item] = $this->returnOrderFixture(2, '20.00');
        Payment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => 'paymob',
            'amount' => '20.00',
            'currency' => 'EGP',
            'status' => 'paid',
        ]);
        $service = app(ReturnService::class);
        $returns = [];

        foreach ([1, 1] as $index => $quantity) {
            $return = $service->create($user->id, $order->fresh(), [
                'reason' => "Partial return {$index}",
                'items' => [['order_item_id' => $item->id, 'quantity' => $quantity]],
            ]);
            $service->updateStatus($return, 'approved');
            $service->updateStatus($return->fresh(), 'received');
            $returns[] = $return;
        }

        try {
            $service->refund($returns[0], ['amount' => '10.01']);
            $this->fail('A refund cannot exceed its returned item value.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $service->refund($returns[0], ['amount' => '10.00']);
        $service->refund($returns[1], ['amount' => '10.00']);
        $this->assertSame('20', (string) Refund::where('order_id', $order->id)->sum('amount'));
    }

    public function test_refund_webhook_settles_only_one_correlated_operation(): void
    {
        [$user, $order] = $this->returnOrderFixture(1, '50.00');
        Payment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => 'paymob',
            'amount' => '50.00',
            'currency' => 'EGP',
            'status' => 'paid',
        ]);
        $first = Refund::create(['order_id' => $order->id, 'user_id' => $user->id, 'amount' => '30.00', 'currency' => 'EGP', 'gateway' => 'paymob', 'status' => 'pending']);
        $second = Refund::create(['order_id' => $order->id, 'user_id' => $user->id, 'amount' => '20.00', 'currency' => 'EGP', 'gateway' => 'paymob', 'status' => 'pending']);
        $payments = app(PaymentStatusService::class);

        $payments->markRefunded($order, 'paymob', 'provider-refund-1', 30.00, 'EGP', []);
        $this->assertSame('refunded', $first->fresh()->status);
        $this->assertSame('pending', $second->fresh()->status);
        $this->assertSame('partially_refunded', $order->fresh()->payment_status);

        $payments->markRefunded($order->fresh(), 'paymob', 'provider-refund-2', 50.00, 'EGP', []);
        $this->assertSame('refunded', $second->fresh()->status);
        $this->assertSame('refunded', $order->fresh()->payment_status);
    }

    public function test_ambiguous_refund_webhook_is_rejected_without_settling_operations(): void
    {
        [$user, $order] = $this->returnOrderFixture(1, '50.00');
        Payment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => 'paymob',
            'amount' => '50.00',
            'currency' => 'EGP',
            'status' => 'paid',
        ]);
        Refund::create(['order_id' => $order->id, 'user_id' => $user->id, 'amount' => '25.00', 'currency' => 'EGP', 'gateway' => 'paymob', 'status' => 'pending']);
        Refund::create(['order_id' => $order->id, 'user_id' => $user->id, 'amount' => '25.00', 'currency' => 'EGP', 'gateway' => 'paymob', 'status' => 'pending']);

        try {
            app(PaymentStatusService::class)->markRefunded($order, 'paymob', 'ambiguous-provider-ref', 25.00, 'EGP', []);
            $this->fail('An ambiguous provider event must not settle multiple internal refunds.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(2, Refund::where('order_id', $order->id)->where('status', 'pending')->count());
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_password_reset_code_locks_after_bounded_wrong_attempts(): void
    {
        $user = User::factory()->create(['email' => 'reset-budget@example.com']);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('123456'),
            'attempts' => 0,
            'created_at' => now(),
        ]);
        $payload = [
            'email' => ' RESET-BUDGET@EXAMPLE.COM ',
            'otp' => '654321',
            'password' => 'New-Password-123!',
            'password_confirmation' => 'New-Password-123!',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/users/reset-password', $payload)->assertStatus(400);
        }

        $this->assertNotNull(DB::table('password_reset_tokens')->where('email', $user->email)->value('locked_at'));
        $this->travel(61)->seconds();
        $payload['otp'] = '123456';
        $this->postJson('/api/v1/users/reset-password', $payload)->assertStatus(400);
    }

    public function test_password_reset_request_limiter_normalizes_account_identity(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'normalized-reset@example.com']);

        foreach (['NORMALIZED-RESET@example.com', ' normalized-reset@example.com ', 'Normalized-Reset@Example.Com', 'NORMALIZED-RESET@EXAMPLE.COM', 'normalized-reset@example.com'] as $email) {
            $this->postJson('/api/v1/users/forgot-password', ['email' => $email])->assertOk();
        }

        $this->postJson('/api/v1/users/forgot-password', ['email' => ' NORMALIZED-RESET@EXAMPLE.COM '])
            ->assertTooManyRequests();
    }

    public function test_valid_password_reset_is_single_use_and_revokes_existing_tokens(): void
    {
        $user = User::factory()->create(['email' => 'single-use-reset@example.com']);
        $user->createToken('existing-session');
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('123456'),
            'attempts' => 0,
            'created_at' => now(),
        ]);
        $payload = [
            'email' => ' SINGLE-USE-RESET@EXAMPLE.COM ',
            'otp' => '123456',
            'password' => 'New-Password-123!',
            'password_confirmation' => 'New-Password-123!',
        ];

        $this->postJson('/api/v1/users/reset-password', $payload)->assertOk();
        $this->assertTrue(Hash::check('New-Password-123!', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->postJson('/api/v1/users/reset-password', $payload)->assertStatus(400);
    }

    private function mockGoogleUser(string $email, string $name, string $id): void
    {
        $googleUser = new class($email, $name, $id)
        {
            public function __construct(private string $email, private string $name, private string $id) {}

            public function getEmail(): string
            {
                return $this->email;
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getId(): string
            {
                return $this->id;
            }

            public function getAvatar(): ?string
            {
                return null;
            }
        };
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($driver);
    }

    private function returnOrderFixture(int $quantity, string $total): array
    {
        $user = User::factory()->create();
        $product = Products::factory()->create(['price' => $total]);
        $order = Orders::create([
            'user_id' => $user->id,
            'order_number' => 'FINAL-'.fake()->unique()->numerify('######'),
            'status' => 'delivered',
            'order_status' => 'delivered',
            'payment_method' => 'paymob',
            'payment_status' => 'paid',
            'subtotal' => $total,
            'total' => $total,
            'currency' => 'EGP',
            'phone' => '01000000000',
            'address' => 'Remediation fixture',
        ]);
        $item = OrderItems::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $total,
            'total_price' => $total,
            'unit_price' => number_format(((int) $total) / $quantity, 2, '.', ''),
        ]);

        return [$user, $order, $item];
    }
}
