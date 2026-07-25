<?php

namespace App\Http\Resources\Home;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderCheckoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'order_status' => $this->order_status ?? $this->status,
            'payment_status' => $this->payment_status,
            'shipping_status' => $this->shipping_status,
            'refund_status' => $this->refund_status,
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) ($this->discount_amount ?? $this->discount),
            'tax_amount' => (float) ($this->tax_amount ?? $this->tax),
            'shipping_amount' => (float) ($this->shipping_amount ?? $this->shipping_cost),
            'total' => (float) $this->total,
            'currency' => $this->currency,
            'shipping_address' => $this->shipping_address_snapshot,
            'shipping' => $this->whenLoaded('shipment', fn () => $this->shipment),
            'payment' => $this->whenLoaded('latestPayment', fn () => $this->latestPayment),
            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice),
            'items' => $this->whenLoaded('items', fn () => $this->items),
            'returns' => $this->whenLoaded('returns', fn () => ReturnRequestResource::collection($this->returns)),
            'timeline' => $this->whenLoaded('statusLogs', fn () => $this->statusLogs->sortBy('created_at')->values()->map(fn ($log) => [
                'id' => $log->id,
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                'note' => $log->note,
                'created_at' => $log->created_at?->toISOString(),
            ])),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
