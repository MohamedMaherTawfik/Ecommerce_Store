<?php

namespace App\Services;

use App\Interfaces\PaymentInterface;
use App\Mail\PaymentFailMail;
use App\Models\Cart;
use App\Models\CartItems;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\User;
use App\Notifications\PaymentSuccessNotification;
use App\Services\Home\CartPricingService;
use App\Services\Home\OrderTimelineService;
use App\Services\Home\StockService;
use App\Support\TaggedCache;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalServices implements PaymentInterface
{
    private PayPalClient $provider;

    public function __construct(
        private readonly CartPricingService $pricing,
        private readonly StockService $stock,
        private readonly OrderTimelineService $timeline,
    ) {
        $this->provider = new PayPalClient;
        $this->provider->setApiCredentials(config('paypal'));
        $this->provider->getAccessToken();
    }

    public function pay(array $data): array
    {
        $currency = config('paypal.currency', 'USD');

        if (($data['order'] ?? null) instanceof Orders) {
            return $this->createGatewayOrder($data['order'], $currency);
        }

        $user = User::findOrFail($data['user_id']);

        $order = DB::transaction(function () use ($user, $data) {
            $cart = Cart::where('user_id', $user->id)
                ->with('items.product')
                ->lockForUpdate()
                ->first();

            if (! $cart || $cart->items->isEmpty()) {
                throw new Exception('Cart is empty.');
            }

            $items = $cart->items->filter(fn (CartItems $item) => $item->product && $item->quantity > 0);

            if ($items->isEmpty()) {
                throw new Exception('Cart has no valid items.');
            }

            $this->stock->ensureAvailable($items);
            $totals = $this->pricing->totals($cart);
            $subtotal = $totals['subtotal'];
            $idempotencyKey = $data['idempotency_key'] ?? $this->buildIdempotencyKey($user->id, $items, $subtotal);

            $existing = Orders::where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->where('payment_method', 'paypal')
                ->whereIn('payment_status', ['pending', 'approved'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $order = Orders::create([
                'user_id' => $user->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'payment_method' => 'paypal',
                'payment_status' => 'pending',
                'idempotency_key' => $idempotencyKey,
                'subtotal' => $subtotal,
                'tax' => 0,
                'shipping_cost' => 0,
                'discount' => $totals['discount'],
                'total' => $totals['total'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? 'Egypt',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                OrderItems::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $this->cartItemTotal($item),
                ]);
            }

            $this->timeline->log($order, 'pending', null, $user->id, 'Order created.');

            return $order;
        });

        return $this->createGatewayOrder($order, $currency);
    }

    private function createGatewayOrder(Orders $order, string $currency): array
    {
        if ($order->paypal_order_id) {
            return [
                'order' => $order->fresh(),
                'approval_url' => $this->getApprovalUrl($order->paypal_order_id),
            ];
        }

        $paypalOrder = $this->provider->createOrder([
            'intent' => 'CAPTURE',
            'application_context' => [
                'return_url' => route('paypal.success'),
                'cancel_url' => route('paypal.cancel'),
                'shipping_preference' => 'NO_SHIPPING',
            ],
            'purchase_units' => [[
                'reference_id' => (string) $order->id,
                'custom_id' => 'order:'.$order->id,
                'description' => 'Order #'.$order->order_number,
                'amount' => [
                    'currency_code' => $currency,
                    'value' => $this->formatAmount($order->total),
                ],
            ]],
        ]);

        if (! isset($paypalOrder['id'])) {
            Log::error('PayPal order creation failed', ['response' => $paypalOrder, 'order_id' => $order->id]);
            throw new Exception('Unable to create PayPal order.');
        }

        $approvalUrl = collect($paypalOrder['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        if (! $approvalUrl) {
            Log::error('PayPal approval URL missing', ['response' => $paypalOrder, 'order_id' => $order->id]);
            throw new Exception('PayPal approval URL not found.');
        }

        $order->update([
            'paypal_order_id' => $paypalOrder['id'],
            'gateway_response' => $paypalOrder,
        ]);

        Log::info('PayPal order created', [
            'order_id' => $order->id,
            'paypal_order_id' => $paypalOrder['id'],
            'total' => $order->total,
        ]);

        return ['order' => $order->fresh(), 'approval_url' => $approvalUrl];
    }

    public function success(string $token): array
    {
        $order = Orders::where('paypal_order_id', $token)->firstOrFail();

        if ($order->payment_status === 'paid') {
            return ['success' => true, 'message' => 'Payment already captured.', 'order' => $order];
        }

        if (! in_array($order->payment_status, ['pending', 'approved'], true)) {
            throw new Exception("Order #{$order->id} cannot be captured from status {$order->payment_status}.");
        }

        $capture = $this->provider->capturePaymentOrder($token);

        Log::info('PayPal capture response received', [
            'order_id' => $order->id,
            'paypal_order_id' => $token,
            'status' => $capture['status'] ?? null,
        ]);

        if (($capture['status'] ?? null) !== 'COMPLETED') {
            $order->update([
                'payment_status' => 'failed',
                'gateway_response' => $capture,
            ]);

            throw new Exception('PayPal payment capture failed.');
        }

        $this->markOrderPaid($order, $capture);

        return ['success' => true, 'message' => 'Payment captured.', 'order' => $order->fresh()];
    }

    public function cancel(): array
    {
        return [
            'success' => false,
            'message' => 'Payment was cancelled by the user.',
        ];
    }

    public function cancelByToken(?string $token): array
    {
        if ($token) {
            Orders::where('paypal_order_id', $token)
                ->where('payment_status', 'pending')
                ->update(['payment_status' => 'cancelled']);
        }

        return $this->cancel();
    }

    public function handleWebhook(array $payload): void
    {
        $eventType = $payload['event_type'] ?? 'unknown';
        $resource = $payload['resource'] ?? [];

        try {
            match ($eventType) {
                'CHECKOUT.ORDER.APPROVED' => $this->captureApprovedOrder($resource),
                'PAYMENT.CAPTURE.COMPLETED' => $this->handleCaptureCompleted($resource),
                'PAYMENT.CAPTURE.DECLINED',
                'PAYMENT.CAPTURE.DENIED' => $this->handleCaptureFailed($resource),
                default => Log::info('Unhandled PayPal webhook event', ['event_type' => $eventType]),
            };
        } catch (Exception $e) {
            Log::error('PayPal webhook handling failed', [
                'event_type' => $eventType,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function captureApprovedOrder(array $resource): void
    {
        $paypalOrderId = $resource['id'] ?? null;

        if (! $paypalOrderId) {
            Log::warning('PayPal approved webhook missing order id', ['resource' => $resource]);

            return;
        }

        $order = Orders::where('paypal_order_id', $paypalOrderId)->first();

        if (! $order || $order->payment_status === 'paid') {
            return;
        }

        $capture = $this->provider->capturePaymentOrder($paypalOrderId);

        if (($capture['status'] ?? null) === 'COMPLETED') {
            $this->markOrderPaid($order, $capture);

            return;
        }

        Log::warning('PayPal approved order capture did not complete', [
            'order_id' => $order->id,
            'paypal_order_id' => $paypalOrderId,
            'status' => $capture['status'] ?? null,
        ]);
    }

    private function handleCaptureCompleted(array $resource): void
    {
        $order = $this->findOrderFromCapture($resource);

        if (! $order) {
            Log::warning('PayPal capture completed for unknown order', ['resource' => $resource]);

            return;
        }

        $this->markOrderPaid($order, $resource);
    }

    private function handleCaptureFailed(array $resource): void
    {
        $order = $this->findOrderFromCapture($resource);

        if (! $order) {
            Log::warning('PayPal capture failed for unknown order', ['resource' => $resource]);

            return;
        }

        DB::transaction(function () use ($order, $resource) {
            $locked = Orders::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked || $locked->payment_status === 'paid') {
                return;
            }

            $locked->update([
                'payment_status' => 'failed',
                'gateway_response' => $resource,
            ]);

            if (! $locked->mail_sent && $locked->user?->email) {
                Mail::to($locked->user->email)->queue(new PaymentFailMail($locked->total, $locked->user->name));
                $locked->update(['mail_sent' => true]);
            }
        });
    }

    private function markOrderPaid(Orders $order, array $paypalResponse): void
    {
        DB::transaction(function () use ($order, $paypalResponse) {
            $locked = Orders::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->payment_status === 'paid') {
                return;
            }

            $capture = $paypalResponse['purchase_units'][0]['payments']['captures'][0] ?? $paypalResponse;
            $capturedAmount = (float) ($capture['amount']['value'] ?? 0);
            $capturedCurrency = strtoupper((string) ($capture['amount']['currency_code'] ?? $paypalResponse['purchase_units'][0]['amount']['currency_code'] ?? config('paypal.currency', 'USD')));
            $expectedAmount = (float) $locked->total;
            $fromStatus = $locked->status;
            $expectedCurrency = strtoupper((string) config('paypal.currency', 'USD'));

            if (abs($capturedAmount - $expectedAmount) > 0.009) {
                Log::error('PayPal amount mismatch', [
                    'order_id' => $locked->id,
                    'expected' => $this->formatAmount($expectedAmount),
                    'captured' => $this->formatAmount($capturedAmount),
                ]);

                throw new Exception('Captured amount does not match order total.');
            }

            if ($capturedCurrency !== $expectedCurrency) {
                Log::error('PayPal currency mismatch', [
                    'order_id' => $locked->id,
                    'expected' => $expectedCurrency,
                    'captured' => $capturedCurrency,
                ]);

                throw new Exception('Captured currency does not match the configured payment currency.');
            }

            $locked->update([
                'status' => 'paid',
                'payment_status' => 'paid',
                'transaction_id' => $capture['id'] ?? null,
                'payer_email' => $paypalResponse['payer']['email_address'] ?? $locked->payer_email,
                'gateway_response' => $paypalResponse,
                'paid_at' => now(),
            ]);

            $this->stock->reduce($locked->items);
            $this->timeline->log($locked, 'paid', $fromStatus, $locked->user_id, 'Payment captured.');
            $this->clearUserCart($locked->user_id);

            if ($locked->discount > 0) {
                $cart = Cart::where('user_id', $locked->user_id)->with('coupon')->first();
                $cart?->coupon?->increment('used_count');
            }

            if (! $locked->mail_sent && $locked->user?->email) {
                $locked->user->notify(new PaymentSuccessNotification($locked));
                $locked->update(['mail_sent' => true]);
            }
        });
    }

    private function findOrderFromCapture(array $resource): ?Orders
    {
        $referenceId = $resource['purchase_units'][0]['reference_id'] ?? null;
        $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
        $captureId = $resource['id'] ?? null;

        if (! $referenceId && ! $paypalOrderId && ! $captureId) {
            return null;
        }

        return Orders::where(function ($query) use ($referenceId, $paypalOrderId, $captureId) {
            $query
                ->when($referenceId, fn ($query) => $query->orWhereKey($referenceId))
                ->when($paypalOrderId, fn ($query) => $query->orWhere('paypal_order_id', $paypalOrderId))
                ->when($captureId, fn ($query) => $query->orWhere('transaction_id', $captureId));
        })->first();
    }

    private function getApprovalUrl(string $paypalOrderId): string
    {
        $details = $this->provider->showOrderDetails($paypalOrderId);
        $approvalUrl = collect($details['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        if (! $approvalUrl) {
            throw new Exception("Cannot retrieve approval URL for PayPal order {$paypalOrderId}.");
        }

        return $approvalUrl;
    }

    private function cartSubtotal(iterable $items): float
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += $this->cartItemTotal($item);
        }

        return round($subtotal, 2);
    }

    private function cartItemTotal(CartItems $item): float
    {
        $unitPrice = (float) $item->product->price;

        return round($unitPrice * (int) $item->quantity, 2);
    }

    private function buildIdempotencyKey(int $userId, iterable $items, float $subtotal): string
    {
        $payload = collect($items)->map(fn (CartItems $item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'color' => $item->color,
            'size' => $item->size,
            'price' => $this->cartItemTotal($item),
        ])->sortBy('product_id')->values()->toJson();

        return hash('sha256', $userId.'|'.$subtotal.'|'.$payload);
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
        } while (Orders::where('order_number', $number)->exists());

        return $number;
    }

    private function clearUserCart(int $userId): void
    {
        $cart = Cart::where('user_id', $userId)->first();

        if ($cart) {
            $cart->items()->delete();
        }

        TaggedCache::tags(['cart', "user_{$userId}"])->flush();
        TaggedCache::tags('user_profile')->flush();
    }

    private function formatAmount(float|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
