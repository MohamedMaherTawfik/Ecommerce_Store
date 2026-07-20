<?php

namespace App\Services\Payment;

use App\Interfaces\PaymentInterface;
use App\Models\Orders;
use App\Models\Payment;
use App\Models\PaymentMethod;
use RuntimeException;

abstract class AbstractPaymentService implements PaymentInterface
{
    protected string $gateway;

    protected function order(array $data): Orders
    {
        $order = $data['order'] ?? null;

        if (! $order instanceof Orders) {
            throw new RuntimeException('A persisted order is required to initialize payment.');
        }

        return $order;
    }

    protected function ensureConfigured(array $required, bool $requireEnabled = true): void
    {
        if ($requireEnabled && ! config("services.{$this->gateway}.enabled", false)) {
            throw new RuntimeException(ucfirst($this->gateway).' payments are disabled.');
        }

        foreach ($required as $key) {
            if (blank(config("services.{$this->gateway}.{$key}"))) {
                throw new RuntimeException(ucfirst($this->gateway)." configuration [{$key}] is missing.");
            }
        }
    }

    protected function storePendingPayment(Orders $order, array $attributes): Payment
    {
        $method = PaymentMethod::where('code', $this->gateway)->first();

        return Payment::updateOrCreate(
            ['order_id' => $order->id, 'gateway' => $this->gateway],
            [
                'user_id' => $order->user_id,
                'payment_method_id' => $method?->id,
                'amount' => $order->total,
                'currency' => strtoupper((string) ($order->currency ?: config("services.{$this->gateway}.currency"))),
                'status' => 'pending',
                ...$attributes,
            ]
        );
    }

    protected function successResult(
        string $paymentUrl,
        string $transactionId,
        string $gatewayReference,
        array $raw
    ): array {
        return [
            'success' => true,
            'gateway' => $this->gateway,
            'payment_url' => $paymentUrl,
            'approval_url' => $paymentUrl,
            'transaction_id' => $transactionId,
            'gateway_reference' => $gatewayReference,
            'raw' => $raw,
        ];
    }

    protected function callbackUrl(string $type): string
    {
        $configured = config("services.payment_urls.{$type}");

        if (filled($configured)) {
            return (string) $configured;
        }

        $route = $type === 'cancel' ? 'payment.cancel' : 'payment.success';

        return route($route, ['gateway' => $this->gateway]);
    }

    public function cancel(): array
    {
        return [
            'success' => false,
            'gateway' => $this->gateway,
            'status' => 'cancelled',
            'message' => ucfirst($this->gateway).' payment was cancelled.',
        ];
    }
}
