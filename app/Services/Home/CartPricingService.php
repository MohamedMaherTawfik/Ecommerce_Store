<?php

namespace App\Services\Home;

use App\Models\Cart;
use App\Models\CartItems;
use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

class CartPricingService
{
    public function applyCoupon(Cart $cart, string $code): Cart
    {
        $coupon = Coupon::where('code', strtoupper(trim($code)))->first();

        if (!$coupon || !$coupon->isUsable()) {
            throw ValidationException::withMessages([
                'code' => ['Coupon is invalid, expired, or fully used.'],
            ]);
        }

        $totals = $this->totals($cart->loadMissing('items.product'), $coupon);

        if ($totals['subtotal'] <= 0) {
            throw ValidationException::withMessages([
                'code' => ['Cannot apply a coupon to an empty cart.'],
            ]);
        }

        $cart->update([
            'coupon_id' => $coupon->id,
            'discount' => $totals['discount'],
        ]);

        return $cart->fresh(['items.product', 'coupon']);
    }

    public function removeCoupon(Cart $cart): Cart
    {
        $cart->update([
            'coupon_id' => null,
            'discount' => 0,
        ]);

        return $cart->fresh(['items.product', 'coupon']);
    }

    public function totals(Cart $cart, ?Coupon $coupon = null): array
    {
        $cart->loadMissing('items.product', 'coupon');
        $subtotal = round($cart->items->sum(fn (CartItems $item) => $this->itemTotal($item)), 2);
        $activeCoupon = $coupon ?: $cart->coupon;
        $discount = $activeCoupon && $activeCoupon->isUsable()
            ? $this->discount($activeCoupon, $subtotal)
            : 0;

        return [
            'subtotal' => $subtotal,
            'discount' => round($discount, 2),
            'total' => round(max($subtotal - $discount, 0), 2),
        ];
    }

    private function itemTotal(CartItems $item): float
    {
        if (!$item->product) {
            return 0;
        }

        return round((float) $item->product->price * (int) $item->quantity, 2);
    }

    private function discount(Coupon $coupon, float $subtotal): float
    {
        if ($coupon->type === 'percentage') {
            return min($subtotal, $subtotal * ((float) $coupon->value / 100));
        }

        return min($subtotal, (float) $coupon->value);
    }
}
