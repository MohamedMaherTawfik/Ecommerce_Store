<?php

namespace App\Http\Resources\Home;

use App\Services\Home\CartPricingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totals = app(CartPricingService::class)->totals($this->resource);

        return [
            'id' => $this->id,
            'coupon' => $this->coupon ? [
                'id' => $this->coupon->id,
                'code' => $this->coupon->code,
                'type' => $this->coupon->type,
                'value' => (float) $this->coupon->value,
            ] : null,
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product' => $item->product,
                'quantity' => (int) $item->quantity,
                'size' => $item->size,
                'color' => $item->color,
                'price' => (float) $item->price,
            ]),
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'total' => $totals['total'],
        ];
    }
}
