<?php

namespace App\Services\Admin;

use App\Models\Orders;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    private const PAID_STATUSES = ['paid', 'partially_refunded', 'refunded'];

    public function salesSummary(array $filters = []): array
    {
        return $this->remember('sales', $filters, function () use ($filters) {
            $allOrders = $this->datedOrders($filters);
            $paidOrders = $this->paidOrders($filters);

            return [
                'total_orders' => (clone $allOrders)->count(),
                'paid_orders' => (clone $paidOrders)->count(),
                'completed_orders' => (clone $allOrders)
                    ->where(function (Builder $query) {
                        $query->where('status', 'delivered')
                            ->orWhere('order_status', 'completed');
                    })
                    ->count(),
                'cancelled_orders' => (clone $allOrders)->where('status', 'cancelled')->count(),
                'items_sold' => (int) $this->paidItemsQuery($filters)->sum('order_items.quantity'),
                'total_revenue' => (float) (clone $paidOrders)->sum('total'),
                'average_order_value' => round((float) (clone $paidOrders)->avg('total'), 2),
            ];
        });
    }

    public function revenueChart(array $filters = []): array
    {
        return $this->remember('revenue', $filters, function () use ($filters) {
            $period = $filters['period'] ?? 'monthly';
            $expression = $this->dateExpression($period, 'orders.created_at');

            return $this->paidOrders($filters)
                ->selectRaw("{$expression} as label, SUM(total) as value, COUNT(*) as orders_count")
                ->groupBy(DB::raw($expression))
                ->orderBy('label')
                ->get()
                ->map(fn ($row) => [
                    'label' => (string) $row->label,
                    'value' => (float) $row->value,
                    'orders_count' => (int) $row->orders_count,
                ])
                ->all();
        });
    }

    public function ordersChart(array $filters = []): array
    {
        return $this->remember('orders_chart', $filters, function () use ($filters) {
            $expression = $this->dateExpression($filters['period'] ?? 'monthly', 'orders.created_at');

            return $this->datedOrders($filters)
                ->selectRaw("{$expression} as label, COUNT(*) as value")
                ->groupBy(DB::raw($expression))
                ->orderBy('label')
                ->get()
                ->map(fn ($row) => ['label' => (string) $row->label, 'value' => (int) $row->value])
                ->all();
        });
    }

    public function salesChart(array $filters = []): array
    {
        return $this->remember('sales_chart', $filters, function () use ($filters) {
            $expression = $this->dateExpression($filters['period'] ?? 'monthly', 'orders.created_at');

            return $this->paidItemsQuery($filters)
                ->selectRaw("{$expression} as label, SUM(order_items.quantity) as value")
                ->groupBy(DB::raw($expression))
                ->orderBy('label')
                ->get()
                ->map(fn ($row) => ['label' => (string) $row->label, 'value' => (int) $row->value])
                ->all();
        });
    }

    public function customerGrowthChart(array $filters = []): array
    {
        return $this->remember('customer_growth', $filters, function () use ($filters) {
            $expression = $this->dateExpression($filters['period'] ?? 'monthly', 'users.created_at');
            $query = DB::table('users')->where('role', 'user')->whereNull('deleted_at');
            $this->applyDates($query, $filters, 'users.created_at');

            return $query
                ->selectRaw("{$expression} as label, COUNT(*) as value")
                ->groupBy(DB::raw($expression))
                ->orderBy('label')
                ->get()
                ->map(fn ($row) => ['label' => (string) $row->label, 'value' => (int) $row->value])
                ->all();
        });
    }

    public function topProducts(array $filters = []): array
    {
        return $this->remember('top_products', $filters, function () use ($filters) {
            return $this->paidItemsQuery($filters)
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->whereNull('products.deleted_at')
                ->selectRaw(
                    'order_items.product_id, products.name, products.sku, '.
                    'SUM(order_items.quantity) as total_sold, '.
                    'SUM(CASE WHEN order_items.total_price > 0 THEN order_items.total_price ELSE order_items.price * order_items.quantity END) as revenue'
                )
                ->groupBy('order_items.product_id', 'products.name', 'products.sku')
                ->orderByDesc('total_sold')
                ->limit($filters['limit'] ?? 10)
                ->get()
                ->map(fn ($row) => [
                    'product_id' => (int) $row->product_id,
                    'name' => $row->name,
                    'sku' => $row->sku,
                    'total_sold' => (int) $row->total_sold,
                    'revenue' => (float) $row->revenue,
                ])
                ->all();
        });
    }

    public function topCategories(array $filters = []): array
    {
        return $this->remember('top_categories', $filters, function () use ($filters) {
            return $this->paidItemsQuery($filters)
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->join('categories', 'categories.id', '=', 'products.categorey_id')
                ->whereNull('products.deleted_at')
                ->whereNull('categories.deleted_at')
                ->selectRaw(
                    'categories.id as category_id, categories.name, '.
                    'SUM(order_items.quantity) as total_sold, '.
                    'SUM(CASE WHEN order_items.total_price > 0 THEN order_items.total_price ELSE order_items.price * order_items.quantity END) as revenue'
                )
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('total_sold')
                ->limit($filters['limit'] ?? 10)
                ->get()
                ->map(fn ($row) => [
                    'category_id' => (int) $row->category_id,
                    'name' => $row->name,
                    'total_sold' => (int) $row->total_sold,
                    'revenue' => (float) $row->revenue,
                ])
                ->all();
        });
    }

    public function topCustomers(array $filters = []): array
    {
        return $this->remember('top_customers', $filters, function () use ($filters) {
            return $this->paidOrders($filters)
                ->join('users', 'users.id', '=', 'orders.user_id')
                ->whereNull('users.deleted_at')
                ->selectRaw(
                    'orders.user_id, users.name, users.email, COUNT(orders.id) as total_orders, SUM(orders.total) as total_spent'
                )
                ->groupBy('orders.user_id', 'users.name', 'users.email')
                ->orderByDesc('total_spent')
                ->limit($filters['limit'] ?? 10)
                ->get()
                ->map(fn ($row) => [
                    'user_id' => (int) $row->user_id,
                    'name' => $row->name,
                    'email' => $row->email,
                    'total_orders' => (int) $row->total_orders,
                    'total_spent' => (float) $row->total_spent,
                ])
                ->all();
        });
    }

    public function clearCache(): void
    {
        Cache::increment('analytics_cache_version');
    }

    private function datedOrders(array $filters): Builder
    {
        $query = Orders::query();
        $this->applyDates($query, $filters, 'orders.created_at');

        return $query;
    }

    private function paidOrders(array $filters): Builder
    {
        return $this->datedOrders($filters)->whereIn('payment_status', self::PAID_STATUSES);
    }

    private function paidItemsQuery(array $filters)
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNull('orders.deleted_at')
            ->whereIn('orders.payment_status', self::PAID_STATUSES);
        $this->applyDates($query, $filters, 'orders.created_at');

        return $query;
    }

    private function applyDates($query, array $filters, string $column): void
    {
        [$from, $to] = $this->resolveDates($filters);

        if ($from) {
            $query->where($column, '>=', $from->startOfDay());
        }

        if ($to) {
            $query->where($column, '<=', $to->endOfDay());
        }
    }

    private function resolveDates(array $filters): array
    {
        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            return [
                ! empty($filters['date_from']) ? CarbonImmutable::parse($filters['date_from']) : null,
                ! empty($filters['date_to']) ? CarbonImmutable::parse($filters['date_to']) : null,
            ];
        }

        $now = CarbonImmutable::now();

        return match ($filters['period'] ?? 'monthly') {
            'daily' => [$now->startOfDay(), $now->endOfDay()],
            'weekly' => [$now->startOfWeek(), $now->endOfWeek()],
            'yearly' => [$now->startOfYear(), $now->endOfYear()],
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };
    }

    private function dateExpression(string $period, string $column): string
    {
        // If the column is orders.created_at, we use our fast indexed virtual columns
        if ($column === 'orders.created_at') {
            return match ($period) {
                'daily' => 'orders.created_date',
                'yearly' => 'orders.created_year',
                default => 'orders.created_month',
            };
        }

        // Fallback for other tables
        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => match ($period) {
                'daily' => "DATE_FORMAT({$column}, '%Y-%m-%d')",
                'weekly' => "DATE_FORMAT({$column}, '%x-%v')",
                'yearly' => "DATE_FORMAT({$column}, '%Y')",
                default => "DATE_FORMAT({$column}, '%Y-%m')",
            },
            'pgsql' => match ($period) {
                'daily' => "TO_CHAR({$column}, 'YYYY-MM-DD')",
                'weekly' => "TO_CHAR({$column}, 'IYYY-IW')",
                'yearly' => "TO_CHAR({$column}, 'YYYY')",
                default => "TO_CHAR({$column}, 'YYYY-MM')",
            },
            default => match ($period) {
                'daily' => "strftime('%Y-%m-%d', {$column})",
                'weekly' => "strftime('%Y-%W', {$column})",
                'yearly' => "strftime('%Y', {$column})",
                default => "strftime('%Y-%m', {$column})",
            },
        };
    }

    private function remember(string $name, array $filters, callable $callback): mixed
    {
        $version = (int) Cache::get('analytics_cache_version', 1);
        $key = 'analytics:'.$version.':'.$name.':'.sha1(json_encode($filters));

        return Cache::remember(
            $key,
            now()->addMinutes(config('store.analytics_cache_minutes')),
            $callback
        );
    }
}
