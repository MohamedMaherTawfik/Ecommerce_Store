<?php

namespace App\Services\Checkout;

use App\Models\Orders;
use App\Models\Refund;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    private const TRANSITIONS = [
        'pending' => ['approved', 'rejected', 'cancelled'],
        'approved' => ['received'],
        'received' => ['processing'],
        'processing' => ['refunded'],
        'rejected' => [],
        'cancelled' => [],
        'refunded' => [],
    ];

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
            $locked = ReturnRequest::with(['items', 'order'])
                ->whereKey($return->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === $status) {
                return $locked;
            }

            if (! in_array($status, self::TRANSITIONS[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => ["Return cannot transition from {$locked->status} to {$status}."],
                ]);
            }

            $payload = ['status' => $status, 'admin_note' => $note];

            if ($status === 'approved') {
                $payload['approved_by'] = auth()->id();
                $payload['approved_at'] = now();
            }

            if ($status === 'rejected') {
                $payload['rejected_at'] = now();
            }

            if ($status === 'received' && config('checkout.restore_stock_on_return') && ! $locked->stock_restored_at) {
                $this->inventory->restore($locked->items);
                $payload['stock_restored_at'] = now();
            }

            $locked->update($payload);
            $locked->order->update(['refund_status' => $status === 'rejected' ? 'rejected' : 'processing']);

            Log::info('Return status changed', [
                'return_request_id' => $locked->id,
                'from_status' => $return->status,
                'to_status' => $status,
                'actor_id' => auth()->id(),
            ]);

            return $locked->fresh(['items.orderItem.product', 'order']);
        });
    }

    public function refund(ReturnRequest $return, array $data): Refund
    {
        return DB::transaction(function () use ($return, $data) {
            $locked = ReturnRequest::with(['order.latestPayment'])
                ->whereKey($return->id)
                ->lockForUpdate()
                ->firstOrFail();
            $idempotencyKey = hash('sha256', "return:{$locked->id}:paymob");
            $existing = Refund::where('idempotency_key', $idempotencyKey)
                ->orWhere('return_request_id', $locked->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            if ($locked->status !== 'received') {
                throw ValidationException::withMessages([
                    'status' => ['Only a received return can be refunded.'],
                ]);
            }

            if ($locked->order->payment_status !== 'paid') {
                throw ValidationException::withMessages([
                    'payment' => ['Only a paid order can be refunded.'],
                ]);
            }

            $amount = (float) ($data['amount'] ?? $locked->order->total);
            if ($amount <= 0 || $amount - (float) $locked->order->total > 0.009) {
                throw ValidationException::withMessages([
                    'amount' => ['Refund amount must be positive and cannot exceed the order total.'],
                ]);
            }

            $refund = Refund::firstOrCreate(['idempotency_key' => $idempotencyKey], [
                'order_id' => $locked->order_id,
                'payment_id' => $locked->order->latestPayment?->id,
                'return_request_id' => $locked->id,
                'user_id' => $locked->user_id,
                'amount' => $amount,
                'currency' => $locked->order->currency ?? config('checkout.currency'),
                'gateway' => 'paymob',
                'status' => 'pending',
                'reason' => $locked->reason,
                'admin_note' => $data['admin_note'] ?? null,
                'processed_by' => auth()->id(),
            ]);

            $locked->update(['status' => 'processing']);
            $locked->order->update(['refund_status' => 'processing']);

            Log::info('Refund operation recorded', [
                'refund_id' => $refund->id,
                'return_request_id' => $locked->id,
                'order_id' => $locked->order_id,
                'actor_id' => auth()->id(),
            ]);

            return $refund;
        });
    }
}
