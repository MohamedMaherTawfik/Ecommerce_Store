<?php

namespace App\Services\Payment;

use App\Exceptions\InvalidWebhookSignatureException;
use App\Interfaces\HandlesPaymentWebhooks;
use App\Models\Orders;
use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MyFatoorahPaymentService extends AbstractPaymentService implements HandlesPaymentWebhooks
{
    protected string $gateway = 'myfatoorah';

    public function __construct(private readonly PaymentStatusService $paymentStatuses) {}

    public function pay(array $data): array
    {
        $this->ensureConfigured(['api_key', 'base_url', 'webhook_secret']);
        $order = $this->order($data);
        $currency = strtoupper((string) ($order->currency ?: config('services.myfatoorah.currency', 'KWD')));
        $address = $order->billing_address_snapshot ?: $order->shipping_address_snapshot ?: [];
        [$countryCode, $phone] = $this->phoneParts(
            (string) (data_get($address, 'phone') ?: $order->phone),
            (string) data_get($address, 'country_code')
        );

        $payload = array_filter([
            'CustomerName' => (string) (data_get($address, 'name') ?: $order->user?->name ?: 'Customer'),
            'NotificationOption' => 'LNK',
            'InvoiceValue' => (float) $order->total,
            'DisplayCurrencyIso' => $currency,
            'CustomerEmail' => (string) (data_get($address, 'email') ?: $order->user?->email),
            'MobileCountryCode' => $countryCode,
            'CustomerMobile' => $phone,
            'CallBackUrl' => $this->callbackUrl('success'),
            'ErrorUrl' => $this->callbackUrl('failed'),
            'Language' => 'EN',
            'CustomerReference' => (string) $order->id,
            'UserDefinedField' => json_encode([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'gateway' => 'myfatoorah',
            ], JSON_THROW_ON_ERROR),
            'WebhookUrl' => route('webhook.myfatoorah'),
        ], fn (mixed $value) => $value !== null && $value !== '');

        $response = $this->http()
            ->post('/v2/SendPayment', $payload)
            ->throw()
            ->json();

        if (! data_get($response, 'IsSuccess')) {
            throw new RuntimeException((string) (data_get($response, 'Message') ?: 'MyFatoorah payment creation failed.'));
        }

        $invoiceId = (string) data_get($response, 'Data.InvoiceId', '');
        $paymentUrl = (string) (
            data_get($response, 'Data.InvoiceURL')
            ?: data_get($response, 'Data.PaymentURL')
        );

        if ($invoiceId === '' || $paymentUrl === '') {
            throw new RuntimeException('MyFatoorah did not return an invoice ID and payment URL.');
        }

        $this->storePendingPayment($order, [
            'gateway_order_id' => $invoiceId,
            'gateway_reference' => (string) $order->id,
            'payment_url' => $paymentUrl,
            'gateway_response' => $response,
            'metadata' => [
                'order_id' => (string) $order->id,
                'invoice_id' => $invoiceId,
            ],
        ]);

        return $this->successResult($paymentUrl, $invoiceId, $invoiceId, $response);
    }

    public function success(string $token): array
    {
        if ($token === '') {
            return [
                'success' => true,
                'gateway' => 'myfatoorah',
                'status' => 'pending',
                'message' => 'MyFatoorah return received without a PaymentId.',
            ];
        }

        $response = $this->paymentStatus($token, 'PaymentId');
        $data = (array) data_get($response, 'Data', []);
        $invoiceId = (string) data_get($data, 'InvoiceId', '');
        $customerReference = (string) data_get($data, 'CustomerReference', '');
        $payment = Payment::where('gateway', 'myfatoorah')
            ->where(function ($query) use ($invoiceId, $customerReference) {
                $query->when($invoiceId !== '', fn ($query) => $query->orWhere('gateway_order_id', $invoiceId))
                    ->when($customerReference !== '', fn ($query) => $query->orWhere('gateway_reference', $customerReference));
            })
            ->first();
        $order = $payment?->order ?: ($customerReference !== '' ? Orders::find($customerReference) : null);
        $invoiceStatus = strtolower((string) data_get($data, 'InvoiceStatus', 'pending'));
        $status = $invoiceStatus === 'paid' ? 'paid' : 'pending';

        if ($order && $status === 'paid') {
            $transaction = collect((array) data_get($data, 'InvoiceTransactions', []))
                ->first(fn ($transaction) => in_array(
                    strtolower((string) data_get($transaction, 'TransactionStatus')),
                    ['success', 'succss'],
                    true
                )) ?? [];
            $transactionId = (string) (
                data_get($transaction, 'PaymentId')
                ?: data_get($transaction, 'TransactionId')
                ?: $token
            );

            $this->paymentStatuses->markPaid(
                $order,
                'myfatoorah',
                $transactionId,
                (float) data_get($data, 'InvoiceValue', $order->total),
                (string) ($order->currency ?: config('services.myfatoorah.currency')),
                $response,
                [
                    'gateway_payment_id' => $token,
                    'gateway_order_id' => $invoiceId,
                ]
            );
        }

        return [
            'success' => true,
            'gateway' => 'myfatoorah',
            'status' => $status,
            'order_id' => $order?->id,
            'transaction_id' => $token,
            'raw' => $response,
            'message' => 'MyFatoorah payment status was verified server-to-server.',
        ];
    }

    public function handleWebhook(Request $request): array
    {
        $this->ensureConfigured(['webhook_secret'], false);
        $payload = $request->all();
        $providedSignature = (string) $request->header('myfatoorah-signature', '');
        $signatureSource = implode(',', [
            'Invoice.Id='.$this->signatureValue(data_get($payload, 'Data.Invoice.Id')),
            'Invoice.Status='.$this->signatureValue(data_get($payload, 'Data.Invoice.Status')),
            'Transaction.Status='.$this->signatureValue(data_get($payload, 'Data.Transaction.Status')),
            'Transaction.PaymentId='.$this->signatureValue(data_get($payload, 'Data.Transaction.PaymentId')),
            'Invoice.ExternalIdentifier='.$this->signatureValue(data_get($payload, 'Data.Invoice.ExternalIdentifier')),
        ]);
        $expectedSignature = base64_encode(hash_hmac(
            'sha256',
            $signatureSource,
            (string) config('services.myfatoorah.webhook_secret'),
            true
        ));

        if ($providedSignature === '' || ! hash_equals($expectedSignature, $providedSignature)) {
            throw new InvalidWebhookSignatureException('Invalid MyFatoorah webhook signature.');
        }

        $transactionStatus = strtoupper((string) data_get($payload, 'Data.Transaction.Status', ''));
        $status = match ($transactionStatus) {
            'SUCCESS' => 'paid',
            'FAILED' => 'failed',
            'CANCELED', 'CANCELLED', 'RELEASED' => 'cancelled',
            default => 'pending',
        };
        $invoiceId = (string) data_get($payload, 'Data.Invoice.Id', '');
        $externalIdentifier = (string) data_get($payload, 'Data.Invoice.ExternalIdentifier', '');
        $payment = $invoiceId === ''
            ? null
            : Payment::where('gateway', 'myfatoorah')->where('gateway_order_id', $invoiceId)->first();

        return [
            'success' => true,
            'gateway' => 'myfatoorah',
            'event_id' => (string) data_get($payload, 'Event.Reference', ''),
            'event_type' => (string) data_get($payload, 'Event.Name', 'PAYMENT_STATUS_CHANGED'),
            'order_id' => (string) ($payment?->order_id ?: $externalIdentifier),
            'transaction_id' => (string) data_get($payload, 'Data.Transaction.PaymentId', ''),
            'gateway_payment_id' => (string) data_get($payload, 'Data.Transaction.PaymentId', ''),
            'gateway_order_id' => $invoiceId,
            'status' => $status,
            'amount' => (float) data_get($payload, 'Data.Amount.ValueInBaseCurrency', 0),
            'currency' => strtoupper((string) data_get($payload, 'Data.Amount.BaseCurrency', '')),
            'raw' => $payload,
            'handled' => (int) data_get($payload, 'Event.Code') === 1,
        ];
    }

    private function paymentStatus(string $key, string $keyType): array
    {
        $response = $this->http()
            ->post('/v2/GetPaymentStatus', [
                'Key' => $key,
                'KeyType' => $keyType,
            ])
            ->throw()
            ->json();

        if (! data_get($response, 'IsSuccess')) {
            throw new RuntimeException((string) (data_get($response, 'Message') ?: 'Unable to verify MyFatoorah payment.'));
        }

        return $response;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.myfatoorah.base_url'), '/'))
            ->withToken((string) config('services.myfatoorah.api_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->connectTimeout(10);
    }

    private function phoneParts(string $phone, string $countryCode): array
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        $configuredCountry = preg_replace('/\D+/', '', $countryCode) ?: '';

        if ($configuredCountry !== '' && str_starts_with($digits, $configuredCountry)) {
            $digits = substr($digits, strlen($configuredCountry));
        }

        return [$configuredCountry, ltrim($digits, '0')];
    }

    private function signatureValue(mixed $value): string
    {
        return $value === null ? '' : (string) $value;
    }
}
