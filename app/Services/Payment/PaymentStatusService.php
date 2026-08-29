<?php

namespace App\Services\Payment;

use App\Mail\PaymentFailMail;
use App\Models\Orders;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Notifications\PaymentSuccessNotification;
use App\Services\Admin\AnalyticsService;
use App\Services\Checkout\InventoryService;
use App\Services\Home\OrderTimelineService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PaymentStatusService
{
    public function __construct(
        private readonly OrderTimelineService $timeline,
        private readonly InventoryService $inventory,
        private readonly AnalyticsService $analytics
    ) {}

    public function markPaid(
        Orders $order,
        string $gateway,
        string $transactionId,
        float $amount,
        string $currency,
        array $response,
        array $references = []
    ): Orders {
        return DB::transaction(function () use ($order, $gateway, $transactionId, $amount, $currency, $response, $references) {
            $locked = Orders::with(['items', 'user'])->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->payment_status, ['paid', 'refunded', 'partially_refunded'], true)) {
                return $locked;
            }

            if (abs((float) $locked->total - $amount) > 0.009) {
                throw ValidationException::withMessages(['amount' => ['Gateway amount does not match the order total.']]);
            }

            $expectedCurrency = strtoupper((string) ($locked->currency ?: config('checkout.currency')));
            if (strtoupper($currency) !== $expectedCurrency) {
                throw ValidationException::withMessages(['currency' => ['Gateway currency does not match the order currency.']]);
            }

            $from = $locked->status;
            $existingPayment = Payment::where('order_id', $locked->id)
                ->where('gateway', $gateway)
                ->first();
            $locked->update([
                'status' => $locked->status === 'pending' ? 'paid' : $locked->status,
                'payment_status' => 'paid',
                'transaction_id' => $transactionId,
                'gateway_response' => $response,
                'paid_at' => now(),
            ]);

            Payment::updateOrCreate(
                ['order_id' => $locked->id, 'gateway' => $gateway],
                [
                    'user_id' => $locked->user_id,
                    'transaction_id' => $transactionId,
                    'gateway_payment_id' => $references['gateway_payment_id'] ?? $existingPayment?->gateway_payment_id ?? $transactionId,
                    'gateway_order_id' => $references['gateway_order_id'] ?? $existingPayment?->gateway_order_id,
                    'gateway_reference' => $references['gateway_reference'] ?? $existingPayment?->gateway_reference ?? $transactionId,
                    'amount' => $amount,
                    'currency' => strtoupper($currency),
                    'status' => 'paid',
                    'gateway_response' => $response,
                    'metadata' => $references['metadata'] ?? $existingPayment?->metadata,
                    'paid_at' => now(),
                    'failed_at' => null,
                    'cancelled_at' => null,
                ]
            );

            if (config('checkout.stock_deduction_mode') === 'payment_success') {
                $this->inventory->ensureAvailable($locked->items);
                $this->inventory->deduct($locked->items);
            }

            $this->timeline->log($locked, 'paid', $from, $locked->user_id, "{$gateway} payment confirmed.");

            if (! $locked->mail_sent && $locked->user?->email) {
                $locked->user->notify(new PaymentSuccessNotification($locked));
                $locked->update(['mail_sent' => true]);
            }

            $this->analytics->clearCache();

            return $locked->fresh(['items', 'user', 'latestPayment']);
        });
    }

    public function markFailed(Orders $order, string $gateway, array $response, array $references = []): Orders
    {
        return DB::transaction(function () use ($order, $gateway, $response, $references) {
            $locked = Orders::with('user')->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (in_array($locked->payment_status, ['paid', 'cancelled', 'refunded', 'partially_refunded'], true)) {
                return $locked;
            }

            $payment = Payment::where('order_id', $locked->id)
                ->where('gateway', $gateway)
                ->first();
            $alreadyFailed = $locked->payment_status === 'failed' && $payment?->status === 'failed';
            $previousStatus = $locked->payment_status;

            $locked->update(['payment_status' => 'failed', 'gateway_response' => $response]);
            Payment::updateOrCreate(
                ['order_id' => $locked->id, 'gateway' => $gateway],
                [
                    'user_id' => $locked->user_id,
                    'transaction_id' => $references['transaction_id'] ?? data_get($response, 'id'),
                    'gateway_payment_id' => $references['gateway_payment_id'] ?? $payment?->gateway_payment_id ?? data_get($response, 'id'),
                    'gateway_order_id' => $references['gateway_order_id'] ?? $payment?->gateway_order_id,
                    'gateway_reference' => $references['gateway_reference'] ?? $payment?->gateway_reference ?? data_get($response, 'id'),
                    'amount' => $locked->total,
                    'currency' => $locked->currency ?: config('checkout.currency'),
                    'status' => 'failed',
                    'gateway_response' => $response,
                    'failed_at' => now(),
                ]
            );

            if (! $alreadyFailed) {
                $this->timeline->log(
                    $locked,
                    'payment_failed',
                    $previousStatus,
                    $locked->user_id,
                    "{$gateway} payment failed."
                );
            }

            if (! $alreadyFailed && $locked->user?->email) {
                Mail::to($locked->user->email)->queue(new PaymentFailMail($locked->total, $locked->user->name));
            }

            $this->analytics->clearCache();

            return $locked;
        });
    }

    public function markCancelled(Orders $order, string $gateway, array $response, array $references = []): Orders
    {
        return DB::transaction(function () use ($order, $gateway, $response, $references) {
            $locked = Orders::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $isGatewayVoid = filter_var(data_get($response, 'is_voided'), FILTER_VALIDATE_BOOLEAN);
            if (in_array($locked->payment_status, ['refunded', 'partially_refunded'], true)) {
                return $locked;
            }

            if ($locked->payment_status === 'paid' && ! $isGatewayVoid) {
                return $locked;
            }

            $locked->update([
                'payment_status' => 'cancelled',
                'gateway_response' => $response,
            ]);

            Payment::updateOrCreate(
                ['order_id' => $locked->id, 'gateway' => $gateway],
                [
                    'user_id' => $locked->user_id,
                    'transaction_id' => $references['transaction_id'] ?? null,
                    'gateway_payment_id' => $references['gateway_payment_id'] ?? null,
                    'gateway_order_id' => $references['gateway_order_id'] ?? null,
                    'gateway_reference' => $references['gateway_reference'] ?? null,
                    'amount' => $locked->total,
                    'currency' => $locked->currency ?: config('checkout.currency'),
                    'status' => 'cancelled',
                    'gateway_response' => $response,
                    'cancelled_at' => now(),
                ]
            );

            return $locked->fresh('latestPayment');
        });
    }

    public function markRefunded(
        Orders $order,
        string $gateway,
        string $transactionId,
        float $amount,
        string $currency,
        array $response,
        array $references = []
    ): Orders {
        return DB::transaction(function () use ($order, $gateway, $transactionId, $amount, $currency, $response, $references) {
            $locked = Orders::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $expectedCurrency = strtoupper((string) ($locked->currency ?: config('checkout.currency')));
            $payment = Payment::query()
                ->where('order_id', $locked->id)
                ->where('gateway', $gateway)
                ->lockForUpdate()
                ->first();
            $paidTotalMinor = min(
                Money::toMinorUnits($locked->total),
                $payment ? Money::toMinorUnits($payment->amount) : Money::toMinorUnits($locked->total)
            );
            $eventAmountMinor = Money::toMinorUnits($amount);

            if (! in_array($locked->payment_status, ['paid', 'partially_refunded', 'refunded'], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Only a paid order can transition to refunded.'],
                ]);
            }

            if (strtoupper($currency) !== $expectedCurrency) {
                throw ValidationException::withMessages(['currency' => ['Gateway refund currency does not match the order currency.']]);
            }

            if ($eventAmountMinor <= 0 || $eventAmountMinor > $paidTotalMinor) {
                throw ValidationException::withMessages(['amount' => ['Gateway refund amount is invalid for this order.']]);
            }

            $providerReference = (string) ($references['gateway_refund_id'] ?? $transactionId);

            if ($providerReference === '') {
                throw ValidationException::withMessages(['refund' => ['Gateway refund reference is required.']]);
            }

            $alreadyCorrelated = Refund::query()
                ->where('gateway', $gateway)
                ->where('gateway_refund_id', $providerReference)
                ->lockForUpdate()
                ->first();

            if ($alreadyCorrelated !== null) {
                if ((int) $alreadyCorrelated->order_id !== (int) $locked->id) {
                    throw ValidationException::withMessages(['refund' => ['Provider refund reference is already assigned to another order.']]);
                }

                if ($alreadyCorrelated->status === 'refunded') {
                    return $locked;
                }
            }

            $refunds = Refund::query()
                ->where('order_id', $locked->id)
                ->where('gateway', $gateway)
                ->whereIn('status', ['pending', 'processing', 'refunded'])
                ->lockForUpdate()
                ->get();
            $settledBeforeMinor = $refunds
                ->where('status', 'refunded')
                ->sum(fn (Refund $refund): int => Money::toMinorUnits($refund->amount));
            $eventDeltaMinor = $eventAmountMinor - $settledBeforeMinor;
            $candidates = $alreadyCorrelated !== null
                ? collect([$alreadyCorrelated])
                : $refunds->whereIn('status', ['pending', 'processing'])
                    ->filter(function (Refund $refund) use ($eventAmountMinor, $eventDeltaMinor): bool {
                        $refundMinor = Money::toMinorUnits($refund->amount);

                        return $refundMinor === $eventAmountMinor || $refundMinor === $eventDeltaMinor;
                    })->values();

            if ($candidates->count() !== 1) {
                throw ValidationException::withMessages([
                    'refund' => ['Gateway refund event could not be correlated to exactly one pending refund operation.'],
                ]);
            }

            /** @var Refund $correlatedRefund */
            $correlatedRefund = $candidates->first();
            $settledMinor = $settledBeforeMinor + Money::toMinorUnits($correlatedRefund->amount);

            if ($settledMinor > $paidTotalMinor) {
                throw ValidationException::withMessages(['amount' => ['Settled refunds would exceed the paid order total.']]);
            }

            $paymentStatus = $settledMinor === $paidTotalMinor ? 'refunded' : 'partially_refunded';

            $locked->update([
                'payment_status' => $paymentStatus,
                'refund_status' => $paymentStatus === 'refunded' ? 'refunded' : 'partial',
                'gateway_response' => $response,
            ]);

            Payment::updateOrCreate(['order_id' => $locked->id, 'gateway' => $gateway], [
                'user_id' => $locked->user_id,
                'transaction_id' => $transactionId,
                'gateway_payment_id' => $references['gateway_payment_id'] ?? $transactionId,
                'gateway_order_id' => $references['gateway_order_id'] ?? null,
                'gateway_reference' => $references['gateway_reference'] ?? $transactionId,
                'amount' => $locked->total,
                'currency' => $expectedCurrency,
                'status' => $paymentStatus,
                'gateway_response' => $response,
                'refunded_at' => now(),
            ]);

            $correlatedRefund->update([
                'gateway_refund_id' => $providerReference,
                'status' => 'refunded',
                'gateway_response' => $response,
                'processed_at' => now(),
            ]);

            if ($correlatedRefund->return_request_id !== null) {
                ReturnRequest::whereKey($correlatedRefund->return_request_id)
                    ->where('status', 'processing')
                    ->update(['status' => 'refunded']);
            }

            return $locked->fresh('latestPayment');
        });
    }
}
