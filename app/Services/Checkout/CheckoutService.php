<?php

namespace App\Services\Checkout;

use App\Models\Addresses;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\PaymentMethod;
use App\Models\Shipment;
use App\Notifications\AdminNewOrderNotification;
use App\Notifications\OrderPlacedNotification;
use App\Services\Home\CartPricingService;
use App\Services\Home\OrderTimelineService;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly CartPricingService $pricing,
        private readonly TaxService $taxes,
        private readonly ShippingRateService $shipping,
        private readonly InventoryService $inventory,
        private readonly PaymentGatewayManager $payments,
        private readonly OrderTimelineService $timeline,
        private readonly InvoiceService $invoices,
    ) {}

    public function summary(int $userId): array
    {
        $cart = $this->cart($userId);
        $address = $this->selectedAddress($userId);
        $totals = $this->totals($cart, $address);

        return [
            'cart' => $cart,
            'items' => $cart->items,
            'subtotal' => $totals['subtotal'],
            'discount_amount' => $totals['discount'],
            'coupon' => $cart->coupon,
            'tax_amount' => $totals['tax']['amount'],
            'shipping_amount' => $totals['shipping']['amount'],
            'grand_total' => $totals['grand_total'],
            'selected_address' => $address,
            'available_shipping_methods' => $totals['shipping']['rates'],
            'selected_shipping' => $totals['shipping']['selected'],
            'available_payment_methods' => PaymentMethod::where('code', 'paymob')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'currency' => $totals['currency'],
        ];
    }

    public function selectAddress(int $userId, int $addressId): array
    {
        $address = Addresses::where('user_id', $userId)->findOrFail($addressId);
        Cache::put($this->addressCacheKey($userId), $address->id, now()->addHours(2));

        return $this->summary($userId);
    }

    public function shippingRates(int $userId, ?int $addressId = null): array
    {
        $address = $addressId
            ? Addresses::where('user_id', $userId)->findOrFail($addressId)
            : $this->selectedAddress($userId);
        $cart = $this->cart($userId);
        $subtotal = $this->pricing->totals($cart)['subtotal'];
        $currency = config('checkout.currency');

        return $this->shipping->rates($address?->snapshot() ?? [], $subtotal, $currency);
    }

    public function selectShipping(int $userId, array $rate): array
    {
        Cache::put($this->shippingCacheKey($userId), $rate, now()->addHours(2));

        return $this->summary($userId);
    }

    public function placeOrder(int $userId, array $data): array
    {
        $idempotencyKey = (string) $data['idempotency_key'];
        $existing = Orders::where('user_id', $userId)
            ->where('idempotency_key', $idempotencyKey)
            ->with(['items.product', 'shipment', 'invoice', 'latestPayment'])
            ->first();

        if ($existing) {
            return $this->replayResult($existing);
        }

        try {
            return DB::transaction(function () use ($userId, $data, $idempotencyKey) {
                $cart = $this->cart($userId, true);
                $coupon = $cart->coupon_id
                    ? Coupon::whereKey($cart->coupon_id)->lockForUpdate()->first()
                    : null;

                if ($coupon && ! $coupon->isUsable()) {
                    throw ValidationException::withMessages(['coupon' => ['This coupon is no longer available.']]);
                }

                $cart->setRelation('coupon', $coupon);
                $address = Addresses::where('user_id', $userId)->findOrFail($data['shipping_address_id'] ?? $this->selectedAddress($userId)?->id);
                $this->inventory->ensureAvailable($cart->items);
                $totals = $this->totals($cart, $address);

                $order = Orders::create([
                    'user_id' => $userId,
                    'order_number' => $this->generateOrderNumber(),
                    'status' => 'pending',
                    'order_status' => 'pending',
                    'payment_method' => 'paymob',
                    'payment_status' => 'pending',
                    'shipping_status' => 'pending',
                    'refund_status' => 'none',
                    'idempotency_key' => $idempotencyKey,
                    'subtotal' => $totals['subtotal'],
                    'discount' => $totals['discount'],
                    'discount_amount' => $totals['discount'],
                    'tax' => $totals['tax']['amount'],
                    'tax_amount' => $totals['tax']['amount'],
                    'shipping_cost' => $totals['shipping']['amount'],
                    'shipping_amount' => $totals['shipping']['amount'],
                    'tax_included' => $totals['tax']['included'],
                    'total' => $totals['grand_total'],
                    'currency' => $totals['currency'],
                    'phone' => $address->phone,
                    'address' => trim(($address->street ?? $address->address ?? '').' '.($address->building_no ?? '')),
                    'city' => $address->city,
                    'country' => $address->country ?: 'Egypt',
                    'notes' => $data['notes'] ?? null,
                    'shipping_address_snapshot' => $address->snapshot(),
                    'billing_address_snapshot' => $address->snapshot(),
                    'shipping_snapshot' => $totals['shipping']['selected'],
                    'tax_snapshot' => $totals['tax']['lines'],
                ]);

                $orderItems = [];
                foreach ($cart->items as $item) {
                    $unitPrice = (float) $item->product->price;
                    $orderItems[] = [
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id ?? null,
                        'quantity' => $item->quantity,
                        'price' => round($unitPrice * (int) $item->quantity, 2),
                        'unit_price' => $unitPrice,
                        'total_price' => round($unitPrice * (int) $item->quantity, 2),
                        'product_name' => $item->product->name,
                        'sku' => $item->product->sku,
                        'options' => json_encode(['size' => $item->size, 'color' => $item->color]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                OrderItems::insert($orderItems);

                foreach ($totals['tax']['lines'] as $taxLine) {
                    $order->taxes()->create($taxLine);
                }

                Shipment::create([
                    'order_id' => $order->id,
                    'shipping_method_id' => $totals['shipping']['selected']['shipping_method_id'] ?? null,
                    'provider' => $totals['shipping']['selected']['provider'] ?? 'manual',
                    'carrier' => $totals['shipping']['selected']['carrier'] ?? null,
                    'service' => $totals['shipping']['selected']['service'] ?? null,
                    'cost' => $totals['shipping']['amount'],
                    'currency' => $totals['currency'],
                    'raw_response' => $totals['shipping']['selected'],
                ]);

                if (config('checkout.stock_deduction_mode') === 'order_placement') {
                    $this->inventory->deduct($cart->items);
                }

                if ($coupon) {
                    $coupon->increment('used_count');
                    CouponRedemption::create([
                        'coupon_id' => $coupon->id,
                        'user_id' => $userId,
                        'order_id' => $order->id,
                        'redeemed_at' => now(),
                    ]);
                }

                $cart->items()->delete();
                $cart->update(['coupon_id' => null, 'discount' => 0]);
                $this->timeline->log($order, 'pending', null, $userId, 'Checkout order placed.');
                $this->invoices->createForOrder($order->fresh(['user']));

                $paymentResult = $this->payments->resolve('paymob')->pay([
                    ...$data,
                    'user_id' => $userId,
                    'order' => $order->fresh(['items.product', 'user']),
                ]);

                DB::afterCommit(function () use ($order) {
                    $order->user?->notify(new OrderPlacedNotification($order->fresh(['user'])));

                    if (config('checkout.admin_notification_email')) {
                        Notification::route('mail', config('checkout.admin_notification_email'))
                            ->notify(new AdminNewOrderNotification($order->fresh(['user'])));
                    }
                });

                return ['order' => $order->fresh(['items.product', 'shipment', 'invoice', 'latestPayment']), 'payment' => $paymentResult];
            });
        } catch (QueryException $exception) {
            $existing = Orders::where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->with(['items.product', 'shipment', 'invoice', 'latestPayment'])
                ->first();

            if ($existing) {
                return $this->replayResult($existing);
            }

            throw $exception;
        }
    }

    private function replayResult(Orders $order): array
    {
        $payment = $order->latestPayment;

        return [
            'order' => $order,
            'payment' => [
                'success' => true,
                'replayed' => true,
                'gateway' => $payment?->gateway ?? 'paymob',
                'payment_url' => $payment?->payment_url,
                'approval_url' => $payment?->payment_url,
                'transaction_id' => $payment?->gateway_order_id,
                'gateway_reference' => $payment?->gateway_reference,
                'payment_channel' => data_get($payment?->metadata, 'payment_channel'),
            ],
        ];
    }

    private function totals(Cart $cart, ?Addresses $address): array
    {
        $pricing = $this->pricing->totals($cart);
        $currency = config('checkout.currency');
        $addressSnapshot = $address?->snapshot() ?? [];
        $rates = config('checkout.shipping_enabled')
            ? $this->shipping->rates($addressSnapshot, $pricing['subtotal'], $currency)
            : [];
        $selected = Cache::get($this->shippingCacheKey($cart->user_id)) ?: ($rates[0] ?? ['amount' => 0, 'provider' => 'manual']);
        $shippingAmount = (float) ($selected['amount'] ?? 0);
        $tax = config('checkout.tax_enabled')
            ? $this->taxes->calculate($addressSnapshot, $pricing['subtotal'] - $pricing['discount'], $shippingAmount)
            : ['amount' => 0, 'included' => false, 'lines' => []];

        return [
            'subtotal' => $pricing['subtotal'],
            'discount' => $pricing['discount'],
            'shipping' => ['amount' => $shippingAmount, 'rates' => $rates, 'selected' => $selected],
            'tax' => $tax,
            'grand_total' => round($pricing['total'] + $shippingAmount + ($tax['included'] ? 0 : $tax['amount']), 2),
            'currency' => $currency,
        ];
    }

    private function cart(int $userId, bool $lock = false): Cart
    {
        $query = Cart::where('user_id', $userId)->with(['items.product.stocks', 'coupon']);
        if ($lock) {
            $query->lockForUpdate();
        }

        $cart = $query->first();
        if (! $cart || $cart->items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => ['Cart is empty.']]);
        }

        return $cart;
    }

    private function selectedAddress(int $userId): ?Addresses
    {
        $selectedId = Cache::get($this->addressCacheKey($userId));

        return Addresses::where('user_id', $userId)
            ->when($selectedId, fn ($query) => $query->whereKey($selectedId))
            ->first()
            ?: Addresses::where('user_id', $userId)->where('is_default_shipping', true)->first();
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
        } while (Orders::where('order_number', $number)->exists());

        return $number;
    }

    private function addressCacheKey(int $userId): string
    {
        return "checkout_address_{$userId}";
    }

    private function shippingCacheKey(int $userId): string
    {
        return "checkout_shipping_{$userId}";
    }
}
