<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Home\OrderCheckoutResource;
use App\Models\Orders;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $orders = Orders::where('user_id', $request->user()->id)
            ->with(['items.product:id,name,slug,image', 'shipment', 'invoice', 'latestPayment'])
            ->latest()
            ->paginate((int) $request->integer('per_page', 10));

        return $this->success(OrderCheckoutResource::collection($orders), 'Orders loaded successfully.');
    }

    public function show(Request $request, int $id)
    {
        $order = Orders::where('user_id', $request->user()->id)
            ->with([
                'items.product:id,name,slug,image',
                'shipment',
                'invoice',
                'latestPayment',
                'returns.items',
                'statusLogs.changedBy:id,name',
            ])
            ->findOrFail($id);

        return $this->success(new OrderCheckoutResource($order), 'Order loaded successfully.');
    }
}
