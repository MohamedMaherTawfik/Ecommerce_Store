<?php

namespace Tests\Feature;

use App\Models\Orders;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymobPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentGatewayIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const HMAC_FIELDS = [
        'amount_cents',
        'created_at',
        'currency',
        'error_occured',
        'has_parent_transaction',
        'id',
        'integration_id',
        'is_3d_secure',
        'is_auth',
        'is_capture',
        'is_refunded',
        'is_standalone_payment',
        'is_voided',
        'order.id',
        'owner',
        'pending',
        'source_data.pan',
        'source_data.sub_type',
        'source_data.type',
        'success',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config([
            'checkout.stock_deduction_mode' => 'order_placement',
            'payment.gateways.paymob.enabled' => true,
            'payment.gateways.paymob.base_url' => 'https://accept.paymob.com',
            'payment.gateways.paymob.secret_key' => 'paymob-secret',
            'payment.gateways.paymob.public_key' => 'paymob-pub',
            'payment.gateways.paymob.hmac_secret' => 'paymob-hmac',
            'payment.gateways.paymob.currency' => 'EGP',
            'payment.gateways.paymob.integration_ids.card' => 11111,
            'payment.gateways.paymob.integration_ids.mobile_wallet' => 22222,
            'payment.gateways.paymob.integration_ids.apple_pay' => 33333,
            'payment.urls.callback' => 'https://store.test/api/v1/payment/paymob/callback',
            'payment.urls.webhook' => 'https://store.test/api/v1/webhooks/paymob',
            'payment.urls.success' => '/checkout/success',
            'payment.urls.failed' => '/checkout/failed',
            'payment.urls.cancel' => '/checkout/cancel',
        ]);
    }

    public function test_manager_resolves_only_paymob(): void
    {
        $this->assertInstanceOf(
            PaymobPaymentService::class,
            app(PaymentGatewayManager::class)->resolve('paymob')
        );

        $this->expectException(InvalidArgumentException::class);
        app(PaymentGatewayManager::class)->resolve('unsupported');
    }

    public function test_cards_apple_pay_and_wallets_use_their_paymob_integration_ids(): void
    {
        $expected = [
            'card' => 11111,
            'mobile_wallet' => 22222,
            'apple_pay' => 33333,
        ];

        Http::fake(function ($request) {
            $channel = match ($request['payment_methods'][0]) {
                22222 => 'mobile_wallet',
                33333 => 'apple_pay',
                default => 'card',
            };

            return Http::response([
                'id' => "int_{$channel}",
                'client_secret' => "cs_{$channel}",
            ]);
        });

        foreach ($expected as $channel => $integrationId) {
            $order = $this->order(150);
            $result = app(PaymobPaymentService::class)->pay([
                'order' => $order,
                'payment_channel' => $channel,
            ]);

            $this->assertTrue($result['success']);
            $this->assertSame($channel, $result['payment_channel']);
            $this->assertStringContainsString("clientSecret=cs_{$channel}", $result['payment_url']);
            $this->assertDatabaseHas('payments', [
                'order_id' => $order->id,
                'gateway' => 'paymob',
                'gateway_order_id' => "int_{$channel}",
                'status' => 'pending',
            ]);

            Http::assertSent(fn ($request) => $request['payment_methods'] === [$integrationId]
                && $request['special_reference'] === (string) $order->id
                && $request['notification_url'] === 'https://store.test/api/v1/webhooks/paymob'
                && $request['redirection_url'] === 'https://store.test/api/v1/payment/paymob/callback');
        }
    }

    public function test_webhook_rejects_an_invalid_paymob_signature_and_logs_it(): void
    {
        $this->postJson('/api/v1/webhooks/paymob?hmac=invalid', ['obj' => ['id' => 1]])
            ->assertStatus(400);

        $this->assertDatabaseHas('payment_webhook_logs', [
            'gateway' => 'paymob',
            'signature_valid' => false,
            'status' => 'rejected',
        ]);
    }

    public function test_signed_paymob_webhooks_handle_paid_failed_cancelled_and_refunded_states(): void
    {
        $paid = $this->order(150);
        $this->pendingPayment($paid, 'int_paid');
        $paidObject = $this->paymobObject($paid, 15000, 445566, 'int_paid');

        $this->postSignedWebhook($paidObject)->assertOk();
        $this->postSignedWebhook($paidObject)->assertOk()->assertJsonPath('data.duplicate', true);
        $this->assertSame('paid', $paid->fresh()->payment_status);

        $failed = $this->order(75);
        $this->pendingPayment($failed, 'int_failed');
        $failedObject = $this->paymobObject($failed, 7500, 445567, 'int_failed', success: false);
        $this->postSignedWebhook($failedObject)->assertOk();
        $this->assertSame('failed', $failed->fresh()->payment_status);

        $cancelled = $this->order(80);
        $this->pendingPayment($cancelled, 'int_voided');
        $capturedObject = $this->paymobObject($cancelled, 8000, 445568, 'int_voided');
        $this->postSignedWebhook($capturedObject)->assertOk();
        $this->assertSame('paid', $cancelled->fresh()->payment_status);

        $voidedObject = $this->paymobObject($cancelled, 8000, 445569, 'int_voided');
        $voidedObject['is_voided'] = true;
        $this->postSignedWebhook($voidedObject)->assertOk();
        $this->assertSame('cancelled', $cancelled->fresh()->payment_status);

        $refund = Refund::create([
            'order_id' => $paid->id,
            'payment_id' => $paid->fresh()->latestPayment->id,
            'user_id' => $paid->user_id,
            'amount' => 150,
            'currency' => 'EGP',
            'gateway' => 'paymob',
            'status' => 'pending',
        ]);
        $refundedObject = $this->paymobObject($paid, 15000, 445566, 'int_paid');
        $refundedObject['is_refunded'] = true;
        $this->postSignedWebhook($refundedObject)->assertOk();
        $this->assertSame('refunded', $paid->fresh()->payment_status);
        $this->assertSame('refunded', $paid->fresh()->latestPayment->status);
        $this->assertSame('refunded', $refund->fresh()->status);
        $this->assertSame('445566', $refund->fresh()->gateway_refund_id);
    }

    public function test_signed_transaction_response_callback_updates_order_and_redirects(): void
    {
        $order = $this->order(35);
        $this->pendingPayment($order, 'int_callback');
        $object = $this->paymobObject($order, 3500, 445570, 'int_callback');

        $this->postSignedWebhook($object)->assertOk();
        $this->postJson(
            '/api/v1/payment/paymob/callback?hmac='.$this->hmac($object),
            $object
        )->assertRedirect('/checkout/success?status=paid&transaction_id=445570&order_id='.$order->id);

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_signed_get_callback_supports_paymob_flattened_query_fields(): void
    {
        $order = $this->order(45);
        $this->pendingPayment($order, 'int_get_callback');
        $object = $this->paymobObject($order, 4500, 445572, 'int_get_callback');
        $query = collect($object)
            ->except(['extras', 'intention', 'order', 'source_data'])
            ->map(fn ($value) => is_bool($value) ? ($value ? 'true' : 'false') : $value)
            ->all();
        $query += [
            'order' => $object['order']['id'],
            'merchant_order_id' => (string) $order->id,
            'source_data_pan' => $object['source_data']['pan'],
            'source_data_sub_type' => $object['source_data']['sub_type'],
            'source_data_type' => $object['source_data']['type'],
            'hmac' => $this->hmac($object),
        ];

        $this->get('/api/v1/payment/paymob/callback?'.http_build_query($query))
            ->assertRedirect('/checkout/success?status=paid&transaction_id=445572&order_id='.$order->id);

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_paid_webhook_rejects_an_amount_mismatch(): void
    {
        $order = $this->order(200);
        $this->pendingPayment($order, 'int_mismatch');
        $object = $this->paymobObject($order, 19900, 778899, 'int_mismatch');

        $this->postSignedWebhook($object)->assertStatus(500);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    private function pendingPayment(Orders $order, string $intentionId): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => 'paymob',
            'gateway_order_id' => $intentionId,
            'gateway_reference' => (string) $order->id,
            'amount' => $order->total,
            'currency' => 'EGP',
            'status' => 'pending',
        ]);
    }

    private function postSignedWebhook(array $object)
    {
        return $this->postJson(
            '/api/v1/webhooks/paymob?hmac='.$this->hmac($object),
            ['obj' => $object]
        );
    }

    private function order(float $amount): Orders
    {
        $user = User::factory()->create();

        return Orders::create([
            'user_id' => $user->id,
            'order_number' => 'PAYMOB-'.uniqid(),
            'status' => 'pending',
            'order_status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'paymob',
            'subtotal' => $amount,
            'total' => $amount,
            'currency' => 'EGP',
            'phone' => '01000000000',
            'address' => 'Test address',
            'billing_address_snapshot' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '01000000000',
                'street' => 'Test address',
                'city' => 'Cairo',
                'country_code' => 'EG',
            ],
        ]);
    }

    private function paymobObject(
        Orders $order,
        int $amountCents,
        int $transactionId,
        string $intentionId,
        bool $success = true
    ): array {
        return [
            'amount_cents' => $amountCents,
            'created_at' => '2026-06-15T12:00:00.000000Z',
            'currency' => 'EGP',
            'error_occured' => false,
            'has_parent_transaction' => false,
            'id' => $transactionId,
            'intention' => ['id' => $intentionId],
            'integration_id' => 11111,
            'is_3d_secure' => true,
            'is_auth' => false,
            'is_capture' => false,
            'is_refunded' => false,
            'is_standalone_payment' => true,
            'is_voided' => false,
            'order' => [
                'id' => 900000 + $order->id,
                'merchant_order_id' => (string) $order->id,
            ],
            'extras' => ['order_id' => (string) $order->id],
            'owner' => 42,
            'pending' => false,
            'source_data' => ['pan' => '2346', 'sub_type' => 'MasterCard', 'type' => 'card'],
            'success' => $success,
        ];
    }

    private function hmac(array $object): string
    {
        $concatenated = collect(self::HMAC_FIELDS)
            ->map(function (string $field) use ($object) {
                $value = data_get($object, $field);

                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }

                return (string) ($value ?? '');
            })
            ->implode('');

        return hash_hmac('sha512', $concatenated, 'paymob-hmac');
    }
}
