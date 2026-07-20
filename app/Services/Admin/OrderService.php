<?php

namespace App\Services\Admin;

use App\Models\Orders;
use App\Services\Home\OrderTimelineService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly OrderTimelineService $timeline,
        private readonly AnalyticsService $analytics
    ) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        return Orders::query()
            ->with('user')
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
        return Orders::with(['user', 'items.product', 'statusLogs.changedBy'])
            ->withCount('items')
            ->findOrFail($id);
    }

    public function updatePaymentStatus(int $id, string $status, ?string $note = null): Orders
    {
        return DB::transaction(function () use ($id, $status, $note) {
            $order = Orders::whereKey($id)->lockForUpdate()->firstOrFail();

            if (in_array($status, ['pending', 'paid', 'shipped', 'delivered', 'cancelled'], true)) {
                $from = $order->status;
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
            $from = $order->{$column};
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
}
