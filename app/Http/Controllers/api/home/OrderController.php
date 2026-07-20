<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Orders;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse;
    public function createOrder(Request $request)
    {
        $data = $request->all();
        $cart = auth()->user()->cart();
        $items = $cart->items;
        Orders::create();
        return $this->success($request->all());
    }

    public function orders(Request $request)
    {
        return $this->success($request->all());
    }
}