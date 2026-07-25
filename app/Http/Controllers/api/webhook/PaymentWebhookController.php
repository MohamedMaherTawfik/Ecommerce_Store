<?php

namespace App\Http\Controllers\api\webhook;

use App\Exceptions\InvalidWebhookSignatureException;
use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Interfaces\HandlesPaymentWebhooks;
use App\Models\Orders;
use App\Models\Payment;
use App\Models\PaymentWebhookLog;
use App\Services\Payment\PaymentStatusService;
use App\Services\Payment\PaymobPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PaymentWebhookController extends Controller
{
    use ApiResponse;

    public function paymob(
        Request $request,
        PaymobPaymentService $gateway,
        PaymentStatusService $payments
    ): JsonResponse {
        return $this->process($request, $gateway, $payments);
    }

    private function process(
        Request $request,
        HandlesPaymentWebhooks $gateway,
        PaymentStatusService $payments
    ): JsonResponse {
        $gatewayName = $this->gatewayName($gateway);
        $log = PaymentWebhookLog::create([
            'gateway' => $gatewayName,
            'signature_valid' => false,
            'payload' => $request->all(),
            'headers' => collect($request->headers->all())
                ->except(['authorization', 'cookie'])
                ->all(),
            'status' => 'received',
        ]);

        try {
            $event = $gateway->handleWebhook($request);
            $eventId = (string) ($event['event_id'] ?? '');
            $eventType = (string) ($event['event_type'] ?? '');

            $log->update([
                'signature_valid' => true,
                'event_type' => $eventType ?: null,
                'status' => 'verified',
            ]);

            if ($eventId !== '') {
                $duplicate = PaymentWebhookLog::where('gateway', $gatewayName)
                    ->where('event_id', $eventId)
                    ->whereKeyNot($log->id)
                    ->whereIn('status', ['processed', 'ignored', 'duplicate'])
                    ->exists();

                if ($duplicate) {
                    $log->update([
                        'status' => 'duplicate',
                        'processed_at' => now(),
                    ]);

                    return $this->success(['duplicate' => true], ucfirst($gatewayName).' webhook already processed.');
                }

                $log->update(['event_id' => $eventId]);
            }

            if (! ($event['handled'] ?? true)) {
                $log->update([
                    'status' => 'ignored',
                    'processed_at' => now(),
                ]);

                return $this->success([], ucfirst($gatewayName).' webhook ignored.');
            }

            $order = $this->findOrder($event);
            $status = (string) ($event['status'] ?? 'pending');

            if (! $order && $status !== 'pending') {
                throw new RuntimeException("Unable to match {$gatewayName} webhook to a local order.");
            }

            if ($order) {
                $references = [
                    'transaction_id' => (string) ($event['transaction_id'] ?? ''),
                    'gateway_payment_id' => (string) ($event['gateway_payment_id'] ?? ''),
                    'gateway_order_id' => (string) ($event['gateway_order_id'] ?? ''),
                    'gateway_reference' => (string) ($event['gateway_reference'] ?? ''),
                ];
                $references = array_filter($references, fn (string $value) => $value !== '');

                match ($status) {
                    'paid' => $payments->markPaid(
                        $order,
                        $gatewayName,
                        (string) ($event['transaction_id'] ?? ''),
                        (float) ($event['amount'] ?? 0),
                        (string) ($event['currency'] ?? ''),
                        (array) ($event['raw'] ?? []),
                        $references
                    ),
                    'failed' => $payments->markFailed(
                        $order,
                        $gatewayName,
                        (array) ($event['raw'] ?? []),
                        $references
                    ),
                    'cancelled' => $payments->markCancelled(
                        $order,
                        $gatewayName,
                        (array) ($event['raw'] ?? []),
                        $references
                    ),
                    'refunded' => $payments->markRefunded(
                        $order,
                        $gatewayName,
                        (string) ($event['transaction_id'] ?? ''),
                        (float) ($event['amount'] ?? 0),
                        (string) ($event['currency'] ?? ''),
                        (array) ($event['raw'] ?? []),
                        $references
                    ),
                    default => $this->recordPending($order, $gatewayName, $event, $references),
                };
            }

            $log->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            return $this->success([
                'event_id' => $eventId,
                'status' => $status,
            ], ucfirst($gatewayName).' webhook processed.');
        } catch (InvalidWebhookSignatureException $exception) {
            $log->update([
                'status' => 'rejected',
                'error_message' => $exception->getMessage(),
            ]);
            Log::warning($exception->getMessage(), ['gateway' => $gatewayName]);

            return $this->error($exception->getMessage(), 400);
        } catch (Throwable $exception) {
            $log->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
            Log::error('Payment webhook processing failed', [
                'gateway' => $gatewayName,
                'webhook_log_id' => $log->id,
                'message' => $exception->getMessage(),
            ]);

            return $this->error('Webhook processing failed.', 500);
        }
    }

    private function findOrder(array $event): ?Orders
    {
        $orderId = (string) ($event['order_id'] ?? '');
        if ($orderId !== '' && ctype_digit($orderId)) {
            $order = Orders::find((int) $orderId);
            if ($order) {
                return $order;
            }
        }

        $paymobOrderId = (string) ($event['paymob_order_id'] ?? '');
        if ($paymobOrderId !== '') {
            $payment = Payment::where('gateway', 'paymob')
                ->where('metadata->paymob_order_id', $paymobOrderId)
                ->first();

            if ($payment?->order) {
                return $payment->order;
            }
        }

        $references = array_filter([
            'transaction_id' => (string) ($event['transaction_id'] ?? ''),
            'gateway_payment_id' => (string) ($event['gateway_payment_id'] ?? ''),
            'gateway_order_id' => (string) ($event['gateway_order_id'] ?? ''),
            'gateway_reference' => (string) ($event['gateway_reference'] ?? ''),
        ], fn (string $value) => $value !== '');

        if ($references === []) {
            return null;
        }

        $payment = Payment::where('gateway', (string) $event['gateway'])
            ->where(function ($query) use ($references) {
                foreach ($references as $column => $value) {
                    $query->orWhere($column, $value);
                }
            })
            ->first();

        return $payment?->order;
    }

    private function recordPending(Orders $order, string $gateway, array $event, array $references): void
    {
        Payment::updateOrCreate(
            ['order_id' => $order->id, 'gateway' => $gateway],
            [
                'user_id' => $order->user_id,
                'transaction_id' => $references['transaction_id'] ?? null,
                'gateway_payment_id' => $references['gateway_payment_id'] ?? null,
                'gateway_order_id' => $references['gateway_order_id'] ?? null,
                'gateway_reference' => $references['gateway_reference'] ?? null,
                'amount' => $order->total,
                'currency' => $order->currency ?: config('checkout.currency'),
                'status' => 'pending',
                'gateway_response' => (array) ($event['raw'] ?? []),
            ]
        );
    }

    private function gatewayName(HandlesPaymentWebhooks $gateway): string
    {
        if (! $gateway instanceof PaymobPaymentService) {
            throw new RuntimeException('Unknown payment webhook gateway.');
        }

        return 'paymob';
    }
}
