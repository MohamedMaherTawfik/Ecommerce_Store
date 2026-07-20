<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;

class PaymentMethodController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $methods = PaymentMethod::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code', 'provider', 'mode', 'settings']);

        return $this->success($methods, 'Payment methods loaded successfully.');
    }
}
