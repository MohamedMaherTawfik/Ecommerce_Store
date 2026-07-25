<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'order_status' => $this->order_status,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'shipping_status' => $this->shipping_status,
            'refund_status' => $this->refund_status,
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) ($this->tax_amount ?? $this->tax),
            'shipping_cost' => (float) ($this->shipping_amount ?? $this->shipping_cost),
            'discount' => (float) ($this->discount_amount ?? $this->discount),
            'total' => (float) $this->total,
            'currency' => $this->currency,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'notes' => $this->notes,
            'shipping_address_snapshot' => $this->shipping_address_snapshot,
            'billing_address_snapshot' => $this->billing_address_snapshot,
            'shipping_snapshot' => $this->shipping_snapshot,
            'tax_snapshot' => $this->tax_snapshot,
            'shipping' => $this->whenLoaded('shipment', fn () => $this->shipment),
            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice),
            'payment' => $this->whenLoaded('latestPayment', fn () => [
                'gateway' => $this->latestPayment?->gateway,
                'channel' => data_get($this->latestPayment?->metadata, 'payment_channel'),
                'intention_id' => $this->latestPayment?->gateway_order_id,
                'transaction_id' => $this->latestPayment?->transaction_id,
                'status' => $this->latestPayment?->status,
                'paid_at' => $this->latestPayment?->paid_at?->toISOString(),
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'phone' => $this->user?->phone,
            ]),
            'items' => $this->whenLoaded('items', fn () => OrderItemResource::collection($this->items)),
            'timeline' => $this->whenLoaded('statusLogs', fn () => $this->statusLogs->sortBy('created_at')->values()->map(fn ($log) => [
                'id' => $log->id,
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                'note' => $log->note,
                'changed_by' => $log->changedBy?->only(['id', 'name']),
                'created_at' => $log->created_at?->toISOString(),
            ])),
            'items_count' => $this->whenCounted('items'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
