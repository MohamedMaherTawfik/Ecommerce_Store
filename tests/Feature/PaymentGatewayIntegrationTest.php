<?php

namespace Tests\Feature;

use App\Models\Orders;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\MyFatoorahPaymentService;
use App\Services\Payment\PaymobPaymentService;
use App\Services\Payment\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Tests\TestCase;

class PaymentGatewayIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config([
            'checkout.stock_deduction_mode' => 'order_placement',
            'services.stripe.enabled' => true,
            'services.stripe.secret_key' => 'sk_test_example',
            'services.stripe.webhook_secret' => 'whsec_test',
            'services.stripe.currency' => 'usd',
            'services.paymob.enabled' => true,
            'services.paymob.base_url' => 'https://accept.paymob.com',
            'services.paymob.secret_key' => 'paymob-secret',
            'services.paymob.public_key' => 'paymob-pub',
            'services.paymob.integration_id' => 12345,
            'services.paymob.hmac_secret' => 'paymob-hmac',
            'services.paymob.currency' => 'EGP',
            'services.myfatoorah.enabled' => true,
            'services.myfatoorah.api_key' => 'myfatoorah-key',
            'services.myfatoorah.base_url' => 'https://apitest.myfatoorah.com',
            'services.myfatoorah.webhook_secret' => 'myfatoorah-secret',
            'services.myfatoorah.currency' => 'KWD',
        ]);
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(CurlClient::instance());
        parent::tearDown();
    }

    public function test_stripe_creates_a_checkout_session_and_persists_the_redirect_url(): void
    {
        ApiRequestor::setHttpClient(new class implements ClientInterface
        {
            public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
            {
                return [
                    json_encode([
                        'id' => 'cs_test_123',
                        'object' => 'checkout.session',
                        'payment_intent' => null,
                        'url' => 'https://checkout.stripe.com/c/pay/cs_test_123',
                    ], JSON_THROW_ON_ERROR),
                    200,
                    [],
                ];
            }
        });

        $order = $this->order('stripe', 25, 'USD');
        $result = app(StripePaymentService::class)->pay(['order' => $order]);

        $this->assertTrue($result['success']);
        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_123', $result['payment_url']);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'gateway_payment_id' => 'cs_test_123',
            'status' => 'pending',
        ]);
    }

    public function test_paymob_creates_intention_and_redirects_to_unified_checkout(): void
    {
        Http::fake([
            'accept.paymob.com/v1/intention/' => Http::response([
                'id' => 'int_100200',
                'client_secret' => 'cs_test_abc123',
            ]),
        ]);

        $order = $this->order('paymob', 150, 'EGP');
        $result = app(PaymobPaymentService::class)->pay(['order' => $order]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('/unifiedcheckout/?publicKey=paymob-pub&clientSecret=cs_test_abc123', $result['payment_url']);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway' => 'paymob',
            'gateway_order_id' => 'int_100200',
        ]);
    }

    public function test_myfatoorah_creates_a_real_invoice_link_request(): void
    {
        Http::fake([
            'apitest.myfatoorah.com/v2/SendPayment' => Http::response([
                'IsSuccess' => true,
                'Data' => [
                    'InvoiceId' => 927972,
                    'InvoiceURL' => 'https://demo.myfatoorah.com/pay/invoice-927972',
                ],
            ]),
        ]);

        $order = $this->order('myfatoorah', 10, 'KWD');
        $result = app(MyFatoorahPaymentService::class)->pay(['order' => $order]);

        $this->assertTrue($result['success']);
        $this->assertSame('https://demo.myfatoorah.com/pay/invoice-927972', $result['payment_url']);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway' => 'myfatoorah',
            'gateway_order_id' => '927972',
        ]);
    }

    public function test_webhooks_reject_invalid_signatures_for_all_three_gateways(): void
    {
        $this->postJson('/api/v1/webhooks/stripe', [], ['Stripe-Signature' => 'invalid'])
            ->assertStatus(400);
        $this->postJson('/api/v1/webhooks/paymob?hmac=invalid', ['obj' => ['id' => 1]])
            ->assertStatus(400);
        $this->postJson('/api/v1/webhooks/myfatoorah', [], ['myfatoorah-signature' => 'invalid'])
            ->assertStatus(400);

        $this->assertDatabaseCount('payment_webhook_logs', 3);
        $this->assertDatabaseHas('payment_webhook_logs', [
            'gateway' => 'stripe',
            'signature_valid' => false,
            'status' => 'rejected',
        ]);
    }

    public function test_valid_paymob_webhook_marks_paid_once_and_rejects_amount_mismatch(): void
    {
        $order = $this->order('paymob', 150, 'EGP');
        Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => 'paymob',
            'gateway_order_id' => 'int_100200',
            'gateway_reference' => (string) $order->id,
            'amount' => 150,
            'currency' => 'EGP',
            'status' => 'pending',
        ]);
        $object = $this->paymobObject($order->id, 15000, 445566, 'int_100200');
        $payload = ['obj' => $object];
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $hmac = hash_hmac('sha512', $json, 'paymob-hmac');

        $this->call(
            'POST',
            '/api/v1/webhooks/paymob',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_HMAC' => $hmac,
            ],
            $json
        )->assertOk();
        
        $this->call(
            'POST',
            '/api/v1/webhooks/paymob',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_HMAC' => $hmac,
            ],
            $json
        )->assertOk()->assertJsonPath('data.duplicate', true);

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertDatabaseCount('payments', 1);

        $mismatch = $this->order('paymob', 200, 'EGP');
        $badObject = $this->paymobObject($mismatch->id, 19900, 778899, 'int_mismatch');
        $badPayload = ['obj' => $badObject];
        $badJson = json_encode($badPayload, JSON_THROW_ON_ERROR);
        $badHmac = hash_hmac('sha512', $badJson, 'paymob-hmac');

        $this->call(
            'POST',
            '/api/v1/webhooks/paymob',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_HMAC' => $badHmac,
            ],
            $badJson
        )->assertStatus(500);
        $this->assertSame('pending', $mismatch->fresh()->payment_status);
    }

    public function test_valid_stripe_webhook_marks_the_order_paid(): void
    {
        $order = $this->order('stripe', 25, 'USD');
        Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => 'stripe',
            'gateway_payment_id' => 'cs_test_paid',
            'gateway_reference' => 'cs_test_paid',
            'amount' => 25,
            'currency' => 'USD',
            'status' => 'pending',
        ]);
        $payload = [
            'id' => 'evt_checkout_paid',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_paid',
                    'object' => 'checkout.session',
                    'payment_intent' => 'pi_test_paid',
                    'payment_status' => 'paid',
                    'amount_total' => 2500,
                    'currency' => 'usd',
                    'metadata' => ['order_id' => (string) $order->id],
                ],
            ],
        ];
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$json, 'whsec_test');

        $this->call(
            'POST',
            '/api/v1/webhooks/stripe',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            ],
            $json
        )->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertDatabaseHas('payment_webhook_logs', [
            'gateway' => 'stripe',
            'event_id' => 'evt_checkout_paid',
            'status' => 'processed',
        ]);
    }

    public function test_valid_myfatoorah_v2_webhook_marks_the_order_paid(): void
    {
        $order = $this->order('myfatoorah', 10, 'KWD');
        Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'gateway' => 'myfatoorah',
            'gateway_order_id' => '6409988',
            'gateway_reference' => (string) $order->id,
            'amount' => 10,
            'currency' => 'KWD',
            'status' => 'pending',
        ]);
        $payload = [
            'Event' => [
                'Code' => 1,
                'Name' => 'PAYMENT_STATUS_CHANGED',
                'Reference' => 'WH-626519',
            ],
            'Data' => [
                'Invoice' => [
                    'Id' => '6409988',
                    'Status' => 'PAID',
                    'ExternalIdentifier' => '',
                ],
                'Transaction' => [
                    'Status' => 'SUCCESS',
                    'PaymentId' => '07076409988323998875',
                ],
                'Amount' => [
                    'BaseCurrency' => 'KWD',
                    'ValueInBaseCurrency' => '10',
                ],
            ],
        ];
        $source = 'Invoice.Id=6409988,Invoice.Status=PAID,Transaction.Status=SUCCESS,'
            .'Transaction.PaymentId=07076409988323998875,Invoice.ExternalIdentifier=';
        $signature = base64_encode(hash_hmac('sha256', $source, 'myfatoorah-secret', true));

        $this->postJson(
            '/api/v1/webhooks/myfatoorah',
            $payload,
            ['myfatoorah-signature' => $signature]
        )->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertDatabaseHas('payment_webhook_logs', [
            'gateway' => 'myfatoorah',
            'event_id' => 'WH-626519',
            'status' => 'processed',
        ]);
    }

    private function order(string $gateway, float $amount, string $currency): Orders
    {
        $user = User::factory()->create();

        return Orders::create([
            'user_id' => $user->id,
            'order_number' => strtoupper($gateway).'-'.uniqid(),
            'status' => 'pending',
            'order_status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => $gateway,
            'subtotal' => $amount,
            'total' => $amount,
            'currency' => $currency,
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

    private function paymobObject(int $orderId, int $amountCents, int $transactionId, string $intentionId): array
    {
        return [
            'amount_cents' => $amountCents,
            'created_at' => '2026-06-15T12:00:00.000000Z',
            'currency' => 'EGP',
            'error_occured' => false,
            'has_parent_transaction' => false,
            'id' => $transactionId,
            'intention' => ['id' => $intentionId],
            'integration_id' => 12345,
            'is_3d_secure' => true,
            'is_auth' => false,
            'is_capture' => false,
            'is_refunded' => false,
            'is_standalone_payment' => true,
            'is_voided' => false,
            'extras' => ['order_id' => (string) $orderId],
            'owner' => 42,
            'pending' => false,
            'source_data' => ['pan' => '2346', 'sub_type' => 'MasterCard', 'type' => 'card'],
            'success' => true,
        ];
    }
}
