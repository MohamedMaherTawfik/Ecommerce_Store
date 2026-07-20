<?php

namespace App\Services\Payment;

use App\Exceptions\InvalidWebhookSignatureException;
use App\Interfaces\HandlesPaymentWebhooks;
use App\Models\Payment;
use Illuminate\Http\Request;
use RuntimeException;
use Stripe\StripeClient;
use Stripe\StripeClientInterface;
use Stripe\Webhook;
use Throwable;

class StripePaymentService extends AbstractPaymentService implements HandlesPaymentWebhooks
{
    protected string $gateway = 'stripe';

    public function __construct(private ?StripeClientInterface $stripe = null) {}

    public function pay(array $data): array
    {
        $this->ensureConfigured(['secret_key', 'webhook_secret']);
        $order = $this->order($data);
        $currency = strtolower((string) ($order->currency ?: config('services.stripe.currency', 'usd')));
        $metadata = [
            'order_id' => (string) $order->id,
            'order_number' => (string) $order->order_number,
            'user_id' => (string) $order->user_id,
            'gateway' => 'stripe',
        ];
        $successUrl = $this->appendQuery(
            $this->callbackUrl('success'),
            'session_id={CHECKOUT_SESSION_ID}'
        );

        $sessionPayload = [
            'mode' => 'payment',
            'client_reference_id' => (string) $order->id,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $this->toMinorUnits((float) $order->total, $currency),
                    'product_data' => [
                        'name' => 'Order #'.$order->order_number,
                        'description' => 'Payment for ecommerce order '.$order->order_number,
                    ],
                ],
            ]],
            'metadata' => $metadata,
            'payment_intent_data' => ['metadata' => $metadata],
            'success_url' => $successUrl,
            'cancel_url' => $this->callbackUrl('cancel'),
        ];

        if (filled($order->user?->email)) {
            $sessionPayload['customer_email'] = $order->user->email;
        }

        $session = $this->client()->checkout->sessions->create($sessionPayload, [
            'idempotency_key' => 'checkout-order-'.$order->id,
        ]);

        $raw = $session->toArray();
        $paymentUrl = (string) ($session->url ?? '');

        if ($paymentUrl === '') {
            throw new RuntimeException('Stripe did not return a Checkout payment URL.');
        }

        $this->storePendingPayment($order, [
            'gateway_payment_id' => (string) $session->id,
            'gateway_order_id' => is_string($session->payment_intent) ? $session->payment_intent : null,
            'gateway_reference' => (string) $session->id,
            'payment_url' => $paymentUrl,
            'gateway_response' => $raw,
            'metadata' => $metadata,
        ]);

        return $this->successResult($paymentUrl, (string) $session->id, (string) $session->id, $raw);
    }

    public function success(string $token): array
    {
        $payment = Payment::where('gateway', 'stripe')
            ->where(function ($query) use ($token) {
                $query->where('gateway_payment_id', $token)
                    ->orWhere('gateway_reference', $token);
            })
            ->with('order')
            ->first();

        return [
            'success' => true,
            'gateway' => 'stripe',
            'status' => $payment?->status ?? 'pending',
            'order_id' => $payment?->order_id,
            'message' => 'Stripe return received. Final status is confirmed by webhook.',
        ];
    }

    public function handleWebhook(Request $request): array
    {
        $this->ensureConfigured(['webhook_secret'], false);

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                (string) config('services.stripe.webhook_secret')
            );
        } catch (Throwable $exception) {
            throw new InvalidWebhookSignatureException('Invalid Stripe webhook signature.', 0, $exception);
        }

        $object = $event->data->object;
        $raw = $object->toArray();
        $orderId = (string) ($object->metadata->order_id ?? $object->client_reference_id ?? '');
        $status = 'pending';
        $amount = 0.0;
        $currency = (string) ($object->currency ?? '');
        $transactionId = (string) ($object->payment_intent ?? $object->id ?? '');
        $gatewayPaymentId = (string) ($object->id ?? '');
        $gatewayOrderId = is_string($object->payment_intent ?? null)
            ? (string) $object->payment_intent
            : '';

        if ($event->type === 'checkout.session.completed') {
            $status = in_array($object->payment_status, ['paid', 'no_payment_required'], true)
                ? 'paid'
                : 'pending';
            $amount = $this->fromMinorUnits((int) ($object->amount_total ?? 0), $currency);
        } elseif ($event->type === 'payment_intent.succeeded') {
            $status = 'paid';
            $amount = $this->fromMinorUnits((int) ($object->amount_received ?? $object->amount ?? 0), $currency);
            $gatewayOrderId = (string) $object->id;
        } elseif ($event->type === 'payment_intent.payment_failed') {
            $status = 'failed';
            $amount = $this->fromMinorUnits((int) ($object->amount ?? 0), $currency);
            $gatewayOrderId = (string) $object->id;
        } elseif ($event->type === 'charge.refunded') {
            $status = 'refunded';
            $amount = $this->fromMinorUnits((int) ($object->amount_refunded ?? 0), $currency);
            $gatewayOrderId = (string) ($object->payment_intent ?? '');
        }

        return [
            'success' => true,
            'gateway' => 'stripe',
            'event_id' => (string) $event->id,
            'event_type' => (string) $event->type,
            'order_id' => $orderId,
            'transaction_id' => $transactionId,
            'gateway_payment_id' => $gatewayPaymentId,
            'gateway_order_id' => $gatewayOrderId,
            'status' => $status,
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'raw' => $raw,
            'handled' => in_array($event->type, [
                'checkout.session.completed',
                'payment_intent.succeeded',
                'payment_intent.payment_failed',
                'charge.refunded',
            ], true),
        ];
    }

    protected function client(): StripeClientInterface
    {
        return $this->stripe ??= new StripeClient((string) config('services.stripe.secret_key'));
    }

    private function appendQuery(string $url, string $query): string
    {
        return $url.(str_contains($url, '?') ? '&' : '?').$query;
    }

    private function toMinorUnits(float $amount, string $currency): int
    {
        return (int) round($amount * (10 ** $this->currencyExponent($currency)));
    }

    private function fromMinorUnits(int $amount, string $currency): float
    {
        return $amount / (10 ** $this->currencyExponent($currency));
    }

    private function currencyExponent(string $currency): int
    {
        $currency = strtolower($currency);

        if (in_array($currency, ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf'], true)) {
            return 0;
        }

        if (in_array($currency, ['bhd', 'jod', 'kwd', 'omr', 'tnd'], true)) {
            return 3;
        }

        return 2;
    }
}
