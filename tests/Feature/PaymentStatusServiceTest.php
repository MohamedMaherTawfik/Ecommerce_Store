<?php

namespace Tests\Feature;

use App\Models\Orders;
use App\Models\User;
use App\Mail\PaymentFailMail;
use App\Services\Payment\PaymentStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_status_updates_are_idempotent_and_validate_gateway_totals(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $order = Orders::create([
            'user_id' => $user->id,
            'order_number' => 'PAY-1',
            'status' => 'pending',
            'order_status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'stripe',
            'subtotal' => 100,
            'total' => 100,
            'currency' => 'USD',
            'phone' => '01000000000',
            'address' => 'Test address',
        ]);

        $service = app(PaymentStatusService::class);
        $paid = $service->markPaid($order, 'stripe', 'pi_123', 100, 'usd', ['id' => 'pi_123']);
        $again = $service->markPaid($paid, 'stripe', 'pi_123', 100, 'usd', ['id' => 'pi_123']);

        $this->assertSame('paid', $again->payment_status);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', ['transaction_id' => 'pi_123', 'status' => 'paid']);
    }

    public function test_payment_status_rejects_amount_mismatch(): void
    {
        $user = User::factory()->create();
        $order = Orders::create([
            'user_id' => $user->id,
            'order_number' => 'PAY-2',
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 100,
            'total' => 100,
            'currency' => 'USD',
            'phone' => '01000000000',
            'address' => 'Test address',
        ]);

        $this->expectException(ValidationException::class);
        app(PaymentStatusService::class)->markPaid($order, 'stripe', 'pi_bad', 99, 'USD', []);
    }

    public function test_failed_payment_is_idempotent_and_does_not_block_success_email(): void
    {
        Mail::fake();
        Notification::fake();
        $user = User::factory()->create();
        $order = Orders::create([
            'user_id' => $user->id,
            'order_number' => 'PAY-3',
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 50,
            'total' => 50,
            'currency' => 'USD',
            'phone' => '01000000000',
            'address' => 'Test address',
        ]);

        $service = app(PaymentStatusService::class);
        $failed = $service->markFailed($order, 'stripe', ['id' => 'pi_retry']);
        $service->markFailed($failed, 'stripe', ['id' => 'pi_retry']);

        Mail::assertQueued(PaymentFailMail::class, 1);
        $this->assertFalse((bool) $failed->fresh()->mail_sent);
        $this->assertDatabaseCount('payments', 1);

        $paid = $service->markPaid($failed, 'stripe', 'pi_retry', 50, 'USD', ['id' => 'pi_retry']);

        $this->assertSame('paid', $paid->payment_status);
        $this->assertTrue((bool) $paid->mail_sent);
        $this->assertDatabaseHas('payments', ['transaction_id' => 'pi_retry', 'status' => 'paid']);
    }
}
