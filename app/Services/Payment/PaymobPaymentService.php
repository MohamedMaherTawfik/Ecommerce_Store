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

    public function pay(array $data): array
    {
        $this->ensureConfigured(['secret_key', 'public_key', 'hmac_secret']);

        $order = $this->order($data);
        $channel = (string) ($data['payment_channel'] ?? 'card');
        $integrationIds = $this->integrationIds($channel);
        $currency = strtoupper((string) ($order->currency ?: config('payment.gateways.paymob.currency', 'EGP')));
        $amountCents = (int) round((float) $order->total * 100);

        $payload = [
            'amount' => $amountCents,
            'currency' => $currency,
            'payment_methods' => $integrationIds,
            'items' => [[
                'name' => 'Order '.$order->order_number,
                'amount' => $amountCents,
                'description' => 'Ecommerce order '.$order->order_number,
                'quantity' => 1,
            ]],
            'billing_data' => $this->billingData($order),
            'customer' => $this->customerData($order),
            'special_reference' => (string) $order->id,
            'notification_url' => $this->webhookUrl(),
            'redirection_url' => $this->paymobCallbackUrl(),
            'extras' => [
                'order_id' => (string) $order->id,
                'order_number' => (string) $order->order_number,
                'payment_channel' => $channel,
            ],
        ];

        Log::info('Creating Paymob payment intention.', [
            'order_id' => $order->id,
            'amount_cents' => $amountCents,
            'currency' => $currency,
            'payment_channel' => $channel,
            'integration_ids' => $integrationIds,
        ]);

        $response = $this->http()
            ->withHeaders(['Authorization' => 'Token '.config('payment.gateways.paymob.secret_key')])
            ->post('/v1/intention/', $payload)
            ->throw()
            ->json();

        $clientSecret = (string) data_get($response, 'client_secret', '');
        $intentionId = (string) data_get($response, 'id', '');
        $paymobOrderId = (string) data_get($response, 'intention_order_id', '');

        if ($clientSecret === '' || $intentionId === '') {
            throw new RuntimeException('Paymob did not return a complete payment intention.');
        }

        $paymentUrl = rtrim((string) config('payment.gateways.paymob.base_url'), '/')
            .'/unifiedcheckout/?publicKey='.urlencode((string) config('payment.gateways.paymob.public_key'))
            .'&clientSecret='.urlencode($clientSecret);

        $raw = ['intention' => $response];
        $metadata = [
            'order_id' => (string) $order->id,
            'intention_id' => $intentionId,
            'paymob_order_id' => $paymobOrderId,
            'integration_ids' => $integrationIds,
            'payment_channel' => $channel,
        ];

        $this->storePendingPayment($order, [
            'gateway_order_id' => $intentionId,
            'gateway_reference' => (string) $order->id,
            'payment_url' => $paymentUrl,
            'gateway_response' => $raw,
            'metadata' => $metadata,
        ]);

        return [
            ...$this->successResult($paymentUrl, $intentionId, (string) $order->id, $raw),
            'payment_channel' => $channel,
        ];
    }

    public function success(string $token): array
    {
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
            ->latest()
            ->first();

        return [
            'success' => true,
            'gateway' => 'paymob',
            'status' => $payment?->status ?? 'pending',
            'order_id' => $payment?->order_id,
            'message' => 'Paymob return received. The signed transaction callback is the payment source of truth.',
        ];
    }

    public function handleWebhook(Request $request): array
    {
        $this->ensureConfigured(['hmac_secret'], false);

        $object = (array) (
            $request->input('obj')
            ?: ($request->isMethod('get') ? $request->query() : $request->except('hmac'))
        );
        unset($object['hmac']);
        $this->verifyHmac($request, $object);

        $pending = $this->boolean(data_get($object, 'pending'));
        $success = $this->boolean(data_get($object, 'success'));
        $status = 'pending';

        if ($this->boolean(data_get($object, 'is_refunded'))) {
            $status = 'refunded';
        } elseif ($this->boolean(data_get($object, 'is_voided'))) {
            $status = 'cancelled';
        } elseif ($success && ! $pending) {
            $status = 'paid';
        } elseif (! $success && ! $pending) {
            $status = 'failed';
        }

        $transactionId = (string) data_get($object, 'id', '');
        $intentionId = $this->firstCallbackValue($object, [
            'intention.id',
            'intention_id',
        ]);
        $merchantOrderId = $this->firstCallbackValue($object, [
            'order.merchant_order_id',
            'order.special_reference',
            'intention.special_reference',
            'special_reference',
            'merchant_order_id',
            'extras.order_id',
        ]);
        $eventAmountCents = $status === 'refunded'
            ? (int) (data_get($object, 'refunded_amount_cents') ?: data_get($object, 'amount_cents', 0))
            : (int) data_get($object, 'amount_cents', 0);
        $paymobOrderId = is_array($object['order'] ?? null)
            ? (string) data_get($object, 'order.id', '')
            : (string) ($object['order'] ?? '');

        return [
            'success' => true,
            'gateway' => 'paymob',
            'event_id' => $transactionId === '' ? '' : "transaction.{$transactionId}.{$status}",
            'event_type' => 'TRANSACTION_PROCESSED',
            'order_id' => $merchantOrderId,
            'transaction_id' => $transactionId,
            'gateway_payment_id' => $transactionId,
            'gateway_order_id' => $intentionId,
            'paymob_order_id' => $paymobOrderId,
            'status' => $status,
            'amount' => $eventAmountCents / 100,
            'currency' => strtoupper((string) data_get($object, 'currency', '')),
            'raw' => $object,
            'handled' => true,
        ];
    }

    private function integrationIds(string $channel): array
    {
        if (! array_key_exists($channel, config('payment.channels', []))) {
            throw new RuntimeException("Unsupported Paymob payment channel [{$channel}].");
        }

        $integrationId = config("payment.gateways.paymob.integration_ids.{$channel}");

        if (blank($integrationId)) {
            throw new RuntimeException("Paymob integration ID for [{$channel}] is missing.");
        }

        return [(int) $integrationId];
    }

    private function verifyHmac(Request $request, array $object): void
    {
        $providedHmac = strtolower((string) (
            $request->header('hmac')
            ?: $request->query('hmac')
            ?: $request->input('hmac', '')
        ));
        $concatenated = collect(self::HMAC_FIELDS)
            ->map(fn (string $field) => $this->callbackValue($object, $field))
            ->implode('');
        $expectedHmac = hash_hmac(
            'sha512',
            $concatenated,
            (string) config('payment.gateways.paymob.hmac_secret')
        );

        if ($providedHmac === '' || ! hash_equals($expectedHmac, $providedHmac)) {
            throw new InvalidWebhookSignatureException('Invalid Paymob webhook signature.');
        }
    }

    private function callbackValue(array $object, string $field): string
    {
        $value = data_get($object, $field);

        if ($field === 'order.id' && $value === null && ! is_array($object['order'] ?? null)) {
            $value = $object['order'] ?? null;
        }

        if ($value === null) {
            $value = $object[str_replace('.', '_', $field)] ?? $object[$field] ?? null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) ($value ?? '');
    }

    private function firstCallbackValue(array $object, array $fields): string
    {
        foreach ($fields as $field) {
            $value = $this->callbackValue($object, $field);

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function paymobCallbackUrl(): string
    {
        return (string) (config('payment.urls.callback') ?: route('payment.paymob.callback'));
    }

    private function webhookUrl(): string
    {
        return (string) (config('payment.urls.webhook') ?: route('webhook.paymob'));
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('payment.gateways.paymob.base_url'), '/'))
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

        return [$parts[0] ?? 'Customer', $parts[1] ?? 'Customer'];
    }

    private function boolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
