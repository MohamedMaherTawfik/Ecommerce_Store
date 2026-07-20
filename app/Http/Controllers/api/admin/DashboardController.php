<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardStatisticsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function statistics(Request $request, DashboardStatisticsService $statistics)
    {
        $filters = $request->validate([
            'period' => ['sometimes', 'string', 'in:daily,weekly,monthly,yearly'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        return $this->success(
            $statistics->statistics($filters),
            'Dashboard statistics loaded.'
        );
    }
}
