<?php

namespace App\Services\Payment;

use App\Exceptions\InvalidWebhookSignatureException;
use App\Interfaces\HandlesPaymentWebhooks;
use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymobPaymentService extends AbstractPaymentService implements HandlesPaymentWebhooks
{
    protected string $gateway = 'paymob';

    public function pay(array $data): array
    {
        $this->ensureConfigured(['secret_key', 'public_key', 'integration_id', 'hmac_secret']);
        $order = $this->order($data);
        $currency = strtoupper((string) ($order->currency ?: config('services.paymob.currency', 'EGP')));
        $amountCents = (int) round((float) $order->total * 100);

        $payload = [
            'amount' => $amountCents,
            'currency' => $currency,
            'payment_methods' => [(int) config('services.paymob.integration_id')],
            'items' => [],
            'billing_data' => $this->billingData($order),
            'customer' => $this->customerData($order),
            'extras' => [
                'order_id' => (string) $order->id,
            ],
        ];

        Log::info('Paymob Unified API Request', [
            'url' => rtrim((string) config('services.paymob.base_url'), '/') . '/v1/intention/',
            'headers' => [
                'Authorization' => 'Token ' . substr(config('services.paymob.secret_key'), 0, 15) . '***',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'payload' => $payload,
            'integration_id_used' => config('services.paymob.integration_id'),
            'currency_used' => $currency,
            'amount_used' => $amountCents,
        ]);

        $response = $this->http()->withHeaders([
            'Authorization' => 'Token ' . config('services.paymob.secret_key'),
        ])->post('/v1/intention/', $payload)->throw()->json();

        $clientSecret = (string) data_get($response, 'client_secret', '');
        $intentionId = (string) data_get($response, 'id', '');

        if ($clientSecret === '') {
            throw new RuntimeException('Paymob did not return a client_secret.');
        }

        $paymentUrl = rtrim((string) config('services.paymob.base_url'), '/')
            . '/unifiedcheckout/?publicKey=' . config('services.paymob.public_key')
            . '&clientSecret=' . $clientSecret;

        $raw = [
            'intention' => $response,
        ];

        $metadata = [
            'order_id' => (string) $order->id,
            'intention_id' => $intentionId,
            'integration_id' => (string) config('services.paymob.integration_id'),
        ];

        $this->storePendingPayment($order, [
            'gateway_order_id' => $intentionId,
            'gateway_reference' => (string) $order->id,
            'payment_url' => $paymentUrl,
            'gateway_response' => $raw,
            'metadata' => $metadata,
        ]);

        return $this->successResult($paymentUrl, $intentionId, (string) $order->id, $raw);
    }

    public function success(string $token): array
    {
        // Paymob Unified Checkout might pass 'id' or 'clientSecret' or 'transaction_id' in success URL
        // PaymentCallbackController resolves it dynamically
        $payment = Payment::where('gateway', 'paymob')
            ->when($token !== '', function ($query) use ($token) {
                $query->where(function ($query) use ($token) {
                    $query->where('transaction_id', $token)
                        ->orWhere('gateway_payment_id', $token)
                        ->orWhere('gateway_order_id', $token)
                        ->orWhere('gateway_reference', $token)
                        ->orWhere('metadata->intention_id', $token);
                });
            })
            ->first();

        return [
            'success' => true,
            'gateway' => 'paymob',
            'status' => $payment?->status ?? 'pending',
            'order_id' => $payment?->order_id,
            'message' => 'Paymob return received. Final status is confirmed by the signed callback.',
        ];
    }

    public function handleWebhook(Request $request): array
    {
        $this->ensureConfigured(['hmac_secret'], false);
        
        $providedHmac = $request->header('hmac') ?: $request->query('hmac') ?: $request->input('hmac', '');
        $expectedHmac = hash_hmac('sha512', $request->getContent(), config('services.paymob.hmac_secret'));

        if ($providedHmac === '' || !hash_equals($expectedHmac, strtolower($providedHmac))) {
            throw new InvalidWebhookSignatureException('Invalid Paymob webhook signature.');
        }

        $object = $request->input('obj') ?: $request->except('hmac');

        $pending = $this->boolean(data_get($object, 'pending'));
        $success = $this->boolean(data_get($object, 'success'));
        $status = 'pending';

        if ($this->boolean(data_get($object, 'is_refunded'))) {
            $status = 'refunded';
        } elseif ($this->boolean(data_get($object, 'is_voided'))) {
            $status = 'cancelled';
        } elseif ($success && !$pending) {
            $status = 'paid';
        } elseif (!$success && !$pending) {
            $status = 'failed';
        }

        $transactionId = (string) data_get($object, 'id', '');
        $intentionId = (string) data_get($object, 'intention.id', '');
        $merchantOrderId = (string) (data_get($object, 'order.merchant_order_id') ?: data_get($object, 'payment_key_claims.billing_data.extra_description') ?: data_get($object, 'extras.order_id') ?: '');

        return [
            'success' => true,
            'gateway' => 'paymob',
            'event_id' => $transactionId === '' ? '' : 'transaction.' . $transactionId,
            'event_type' => 'TRANSACTION_PROCESSED',
            'order_id' => $merchantOrderId,
            'transaction_id' => $transactionId,
            'gateway_payment_id' => $transactionId,
            'gateway_order_id' => $intentionId,
            'status' => $status,
            'amount' => ((float) data_get($object, 'amount_cents', 0)) / 100,
            'currency' => strtoupper((string) data_get($object, 'currency', '')),
            'raw' => $object,
            'handled' => true,
        ];
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.paymob.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->connectTimeout(10);
    }

    private function customerData($order): array
    {
        $address = $order->billing_address_snapshot ?: $order->shipping_address_snapshot ?: [];
        $name = trim((string) (data_get($address, 'name') ?: $order->user?->name ?: 'Customer'));
        [$firstName, $lastName] = $this->splitName($name);

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => (string) (data_get($address, 'email') ?: $order->user?->email ?: 'customer@example.com'),
        ];
    }

    private function billingData($order): array
    {
        $address = $order->billing_address_snapshot ?: $order->shipping_address_snapshot ?: [];
        $name = trim((string) (data_get($address, 'name') ?: $order->user?->name ?: 'Customer'));
        [$firstName, $lastName] = $this->splitName($name);

        return [
            'apartment' => (string) (data_get($address, 'apartment_no') ?: 'NA'),
            'email' => (string) (data_get($address, 'email') ?: $order->user?->email ?: 'customer@example.com'),
            'floor' => (string) (data_get($address, 'floor') ?: 'NA'),
            'first_name' => $firstName,
            'street' => (string) (data_get($address, 'street') ?: $order->address ?: 'NA'),
            'building' => (string) (data_get($address, 'building_no') ?: 'NA'),
            'phone_number' => (string) (data_get($address, 'phone') ?: $order->phone ?: 'NA'),
            'shipping_method' => 'NA',
            'postal_code' => (string) (data_get($address, 'postal_code') ?: 'NA'),
            'city' => (string) (data_get($address, 'city') ?: $order->city ?: 'NA'),
            'country' => strtoupper((string) (data_get($address, 'country_code') ?: 'EG')),
            'last_name' => $lastName,
            'state' => (string) (data_get($address, 'state') ?: 'NA'),
        ];
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [
            $parts[0] ?? 'Customer',
            $parts[1] ?? 'Customer',
        ];
    }

    private function boolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}