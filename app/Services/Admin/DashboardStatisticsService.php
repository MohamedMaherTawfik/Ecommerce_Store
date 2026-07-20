<?php

namespace App\Services\Admin;

use App\Models\Products;
use App\Models\User;

class DashboardStatisticsService
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function statistics(array $filters = []): array
    {
        $sales = $this->analytics->salesSummary($filters);

        return [
            'total_sales' => $sales['total_revenue'],
            'orders_count' => $sales['total_orders'],
            'paid_orders' => $sales['paid_orders'],
            'customers_count' => User::query()->where('role', 'user')->count(),
            'products_count' => Products::query()->count(),
            'best_selling_products' => $this->analytics->topProducts($filters + ['limit' => 10]),
            'top_categories' => $this->analytics->topCategories($filters + ['limit' => 10]),
            'top_customers' => $this->analytics->topCustomers($filters + ['limit' => 10]),
            'low_stock_products' => $this->lowStockProducts(),
            'revenue_chart' => $this->analytics->revenueChart($filters),
            'orders_chart' => $this->analytics->ordersChart($filters),
            'sales_chart' => $this->analytics->salesChart($filters),
            'customer_growth_chart' => $this->analytics->customerGrowthChart($filters),
        ];
    }

    private function lowStockProducts(): array
    {
        return Products::query()
            ->whereNotNull('low_stock_threshold')
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->limit(20)
            ->get(['id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold'])
            ->map(fn ($product) => [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'stock_quantity' => (int) $product->stock_quantity,
                'low_stock_threshold' => (int) $product->low_stock_threshold,
            ])
            ->all();
    }
}
