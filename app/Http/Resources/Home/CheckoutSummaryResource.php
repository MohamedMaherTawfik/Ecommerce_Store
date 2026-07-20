<?php

namespace App\Http\Resources\Home;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'items' => $this['items'],
            'subtotal' => $this['subtotal'],
            'discount_amount' => $this['discount_amount'],
            'coupon' => $this['coupon'],
            'tax_amount' => $this['tax_amount'],
            'shipping_amount' => $this['shipping_amount'],
            'grand_total' => $this['grand_total'],
            'selected_address' => $this['selected_address'] ? new AddressResource($this['selected_address']) : null,
            'available_shipping_methods' => $this['available_shipping_methods'],
            'selected_shipping' => $this['selected_shipping'],
            'available_payment_methods' => $this['available_payment_methods'],
            'currency' => $this['currency'],
        ];
    }
}
