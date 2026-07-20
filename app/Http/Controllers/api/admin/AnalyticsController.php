<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\Admin\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AnalyticsService $analytics) {}

    public function revenue(Request $request)
    {
        $filters = $this->filters($request);

        return $this->success(
            $this->analytics->revenueChart($filters),
            'Revenue data loaded.'
        );
    }

    public function sales(Request $request)
    {
        return $this->success(
            $this->analytics->salesSummary($this->filters($request)),
            'Sales summary loaded.'
        );
    }

    public function topProducts(Request $request)
    {
        return $this->success(
            $this->analytics->topProducts($this->filters($request)),
            'Top products loaded.'
        );
    }

    public function topCategories(Request $request)
    {
        return $this->success(
            $this->analytics->topCategories($this->filters($request)),
            'Top categories loaded.'
        );
    }

    public function topCustomers(Request $request)
    {
        return $this->success(
            $this->analytics->topCustomers($this->filters($request)),
            'Top customers loaded.'
        );
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'period' => ['sometimes', 'string', 'in:daily,weekly,monthly,yearly'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
    }
}
