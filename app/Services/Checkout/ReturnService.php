<?php

namespace App\Services\Checkout;

use App\Models\Orders;
use App\Models\Refund;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function create(int $userId, Orders $order, array $data): ReturnRequest
    {
        if ($order->user_id !== $userId) {
            abort(403);
        }

        if (! in_array($order->status, ['delivered', 'completed'], true) && ! config('checkout.allow_non_delivered_returns', false)) {
            throw ValidationException::withMessages(['order' => ['Only delivered orders can be returned.']]);
        }

        return DB::transaction(function () use ($userId, $order, $data) {
            $return = ReturnRequest::create([
                'order_id' => $order->id,
                'user_id' => $userId,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]);

            foreach ($data['items'] as $itemData) {
                $orderItem = $order->items()->whereKey($itemData['order_item_id'])->firstOrFail();
                if ((int) $itemData['quantity'] > (int) $orderItem->quantity) {
                    throw ValidationException::withMessages(['items' => ['Return quantity cannot exceed purchased quantity.']]);
                }

                $return->items()->create([
                    'order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'product_variant_id' => $orderItem->product_variant_id,
                    'quantity' => $itemData['quantity'],
                    'reason' => $itemData['reason'] ?? null,
                    'status' => 'pending',
                ]);
            }

            $order->update(['refund_status' => 'requested']);

            return $return->fresh(['items.orderItem.product', 'order']);
        });
    }

    public function updateStatus(ReturnRequest $return, string $status, ?string $note = null): ReturnRequest
    {
        return DB::transaction(function () use ($return, $status, $note) {
            $payload = ['status' => $status, 'admin_note' => $note];

            if ($status === 'approved') {
                $payload['approved_by'] = auth()->id();
                $payload['approved_at'] = now();
            }

            if ($status === 'rejected') {
                $payload['rejected_at'] = now();
            }

            $return->update($payload);
            $return->order->update(['refund_status' => $status === 'rejected' ? 'rejected' : 'processing']);

            if (in_array($status, ['received', 'refunded'], true) && config('checkout.restore_stock_on_return')) {
                $this->inventory->restore($return->items);
            }

            return $return->fresh(['items.orderItem.product', 'order']);
        });
    }

    public function refund(ReturnRequest $return, array $data): Refund
    {
        return DB::transaction(function () use ($return, $data) {
            $refund = Refund::create([
                'order_id' => $return->order_id,
                'payment_id' => $return->order->latestPayment?->id,
                'return_request_id' => $return->id,
                'user_id' => $return->user_id,
                'amount' => $data['amount'] ?? $return->order->total,
                'currency' => $return->order->currency ?? config('checkout.currency'),
                'gateway' => 'paymob',
                'status' => 'pending',
                'reason' => $return->reason,
                'admin_note' => $data['admin_note'] ?? null,
                'processed_by' => auth()->id(),
            ]);

            $return->update(['status' => 'processing']);
            $return->order->update(['refund_status' => 'processing']);

            return $refund;
        });
    }
}
