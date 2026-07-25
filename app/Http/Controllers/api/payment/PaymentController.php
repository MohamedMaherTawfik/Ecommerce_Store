<?php

namespace App\Http\Controllers\api\payment;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CheckoutRequest;
use App\Models\Cart;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Services\Home\CartPricingService;
use App\Services\Home\OrderTimelineService;
use App\Services\Home\StockService;
use App\Services\Payment\PaymentGatewayManager;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    use ApiResponse {
        success as apiSuccess;
        error as apiError;
        notFound as apiNotFound;
    }

    public function __construct(
        private readonly CartPricingService $pricing,
        private readonly StockService $stock,
        private readonly OrderTimelineService $timeline,
    ) {}

    public function pay(CheckoutRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $method = 'paymob';
        $validated['payment_method'] = $method;
        $validated['user_id'] = $request->user()->id;

        try {
            Log::info('Cart before checkout', ['user_id' => $validated['user_id'], 'method' => $method]);

            return DB::transaction(function () use ($validated) {
                $order = $this->createOnlinePaymentOrder($validated);

                Log::info('Order created', ['order_id' => $order->id, 'order_number' => $order->order_number]);

                $payment = app(PaymentGatewayManager::class)->resolve('paymob')->pay([
                    ...$validated,
                    'order' => $order->fresh(['items.product', 'user']),
                ]);

                return $this->apiSuccess([
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_status' => $order->payment_status,
                    'total' => $order->total,
                    ...$payment,
                ], 'Payment initialized successfully.');
            });
        } catch (Exception $e) {
            Log::error('Checkout payment initialization failed', [
                'user_id' => $request->user()->id,
                'gateway' => $method,
                'message' => $e->getMessage(),
            ]);

            return $this->apiError($e->getMessage(), 422);
        }
    }

    public function orderStatus(Request $request, int $id): JsonResponse
    {
        $order = Orders::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('statusLogs.changedBy:id,name')
            ->first();

        if (! $order) {
            return $this->apiNotFound('Order not found.');
        }

        return $this->apiSuccess([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total' => $order->total,
            'transaction_id' => $order->transaction_id,
            'paid_at' => $order->paid_at,
            'timeline' => $order->statusLogs->sortBy('created_at')->values(),
        ], 'Order status retrieved successfully.');
    }

    private function createOnlinePaymentOrder(array $data): Orders
    {
        return DB::transaction(function () use ($data) {
            $cart = Cart::where('user_id', $data['user_id'])
                ->with(['items.product', 'coupon'])
                ->lockForUpdate()
                ->first();

            if (! $cart || $cart->items->isEmpty()) {
                throw new Exception('Cart is empty.');
            }

            $items = $cart->items->filter(fn ($item) => $item->product && $item->quantity > 0);
            if ($items->isEmpty()) {
                throw new Exception('Cart has no valid items.');
            }

            $this->stock->ensureAvailable($items);
            $totals = $this->pricing->totals($cart);
            $currency = strtoupper((string) (
                config('payment.gateways.paymob.currency')
                ?: config('checkout.currency', 'EGP')
            ));
            $order = Orders::create([
                'user_id' => $data['user_id'],
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'order_status' => 'pending',
                'payment_method' => 'paymob',
                'payment_status' => 'pending',
                'shipping_status' => 'pending',
                'refund_status' => 'none',
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'subtotal' => $totals['subtotal'],
                'tax' => 0,
                'shipping_cost' => 0,
                'discount' => $totals['discount'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => 0,
                'shipping_amount' => 0,
                'total' => $totals['total'],
                'currency' => $currency,
                'phone' => $data['phone'],
                'address' => $data['address'],
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? 'Egypt',
                'notes' => $data['notes'] ?? null,
                'shipping_address_snapshot' => [
                    'name' => $data['customer_name'] ?? $data['name'] ?? null,
                    'email' => $data['customer_email'] ?? null,
                    'phone' => $data['phone'],
                    'street' => $data['address'],
                    'city' => $data['city'] ?? null,
                    'country' => $data['country'] ?? 'Egypt',
                    'country_code' => $data['country_code'] ?? 'EG',
                ],
                'billing_address_snapshot' => [
                    'name' => $data['customer_name'] ?? $data['name'] ?? null,
                    'email' => $data['customer_email'] ?? null,
                    'phone' => $data['phone'],
                    'street' => $data['address'],
                    'city' => $data['city'] ?? null,
                    'country' => $data['country'] ?? 'Egypt',
                    'country_code' => $data['country_code'] ?? 'EG',
                ],
            ]);

            foreach ($items as $item) {
                OrderItems::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => round((float) $item->product->price * (int) $item->quantity, 2),
                ]);
            }

            if (config('checkout.stock_deduction_mode') === 'order_placement') {
                $this->stock->reduce($items);
            }

            $this->timeline->log($order, 'pending', null, $data['user_id'], 'Paymob payment order created.');

            if ($cart->coupon) {
                $cart->coupon->increment('used_count');
            }

            $cart->items()->delete();
            $cart->update(['coupon_id' => null, 'discount' => 0]);

            return $order;
        });
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
        } while (Orders::where('order_number', $number)->exists());

        return $number;
    }
}
