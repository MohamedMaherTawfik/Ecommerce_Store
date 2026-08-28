<?php

namespace App\Services\Admin;

use App\Models\Orders;
use App\Services\Home\OrderTimelineService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    private const TRANSITIONS = [
        'status' => [
            'pending' => ['approved', 'paid', 'failed', 'cancelled'],
            'approved' => ['paid', 'cancelled'],
            'paid' => ['shipped'],
            'shipped' => ['delivered'],
            'failed' => ['pending', 'cancelled'],
            'delivered' => [],
            'cancelled' => [],
        ],
        'order_status' => [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['processing', 'cancelled'],
            'processing' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ],
        'payment_status' => [
            'unpaid' => ['pending', 'paid', 'failed', 'cancelled'],
            'pending' => ['paid', 'failed', 'cancelled'],
            'failed' => ['pending', 'paid', 'cancelled'],
            'paid' => ['partially_refunded', 'refunded'],
            'partially_refunded' => ['refunded'],
            'refunded' => [],
            'cancelled' => [],
        ],
        'shipping_status' => [
            'pending' => ['packed', 'cancelled'],
            'packed' => ['shipped', 'cancelled'],
            'shipped' => ['in_transit', 'delivered', 'returned'],
            'in_transit' => ['delivered', 'returned'],
            'delivered' => ['returned'],
            'returned' => [],
            'cancelled' => [],
        ],
    ];

    public function __construct(
        private readonly OrderTimelineService $timeline,
        private readonly AnalyticsService $analytics
    ) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        return Orders::query()
            ->with(['user', 'latestPayment'])
            ->withCount('items')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('payment_status', $status))
            ->when($filters['order_status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
    }

    public function find(int $id): Orders
    {
        return Orders::with(['user', 'items.product', 'statusLogs.changedBy', 'latestPayment', 'shipment', 'invoice'])
            ->withCount('items')
            ->findOrFail($id);
    }

    public function updatePaymentStatus(int $id, string $status, ?string $note = null): Orders
    {
        return DB::transaction(function () use ($id, $status, $note) {
            $order = Orders::whereKey($id)->lockForUpdate()->firstOrFail();

            if (in_array($status, ['pending', 'approved', 'paid', 'shipped', 'delivered', 'cancelled'], true)) {
                $from = $order->status;
                $this->assertTransition('status', $from, $status);
                $order->update([
                    'status' => $status,
                    'delivered_at' => $status === 'delivered' ? now() : $order->delivered_at,
                ]);

                if ($from !== $status) {
                    $this->timeline->log($order, $status, $from, auth()->id(), $note);
                }

                $this->analytics->clearCache();

                return $this->find($order->id);
            }

            $from = $order->status;
            $this->assertTransition('status', $from, $status);
            $payload = ['payment_status' => $status];

            if ($status === 'paid') {
                $payload['status'] = $order->status === 'pending' ? 'paid' : $order->status;
                $payload['paid_at'] = $order->paid_at ?: now();
            }

            if (in_array($status, ['failed', 'cancelled'], true) && $order->status === 'pending') {
                $payload['status'] = 'cancelled';
            }

            $order->update($payload);

            if (($payload['status'] ?? null) && $payload['status'] !== $from) {
                $this->timeline->log($order, $payload['status'], $from, auth()->id(), $note);
            }

            $this->analytics->clearCache();

            return $this->find($order->id);
        });
    }

    public function updateDedicatedStatus(int $id, string $column, string $status, ?string $note = null): Orders
    {
        return DB::transaction(function () use ($id, $column, $status, $note) {
            $order = Orders::whereKey($id)->lockForUpdate()->firstOrFail();
            $from = $order->{$column} ?: array_key_first(self::TRANSITIONS[$column] ?? []);
            $this->assertTransition($column, $from, $status);
            $payload = [$column => $status];

            if ($column === 'order_status') {
                $payload['status'] = match ($status) {
                    'confirmed', 'processing' => 'confirmed',
                    'completed' => 'delivered',
                    'cancelled' => 'cancelled',
                    default => 'pending',
                };
            }

            if ($column === 'payment_status' && $status === 'paid') {
                $payload['paid_at'] = $order->paid_at ?: now();
                $payload['status'] = $order->status === 'pending' ? 'paid' : $order->status;
            }

            if ($column === 'shipping_status' && $status === 'delivered') {
                $payload['delivered_at'] = now();
                $payload['status'] = 'delivered';
            }

            $order->update($payload);

            if ($from !== $status) {
                $this->timeline->log($order, "{$column}:{$status}", $from, auth()->id(), $note);
            }

            $this->analytics->clearCache();

            return $this->find($order->id);
        });
    }

    public function delete(int $id): void
    {
        $order = Orders::findOrFail($id);
        $order->delete();
    }

    private function assertTransition(string $column, string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        if (! in_array($to, self::TRANSITIONS[$column][$from] ?? [], true)) {
            throw ValidationException::withMessages([
                $column => "Invalid {$column} transition from {$from} to {$to}.",
            ]);
        }
    }
}
