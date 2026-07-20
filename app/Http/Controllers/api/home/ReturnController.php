<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Home\ReturnRequestStoreRequest;
use App\Http\Resources\Home\ReturnRequestResource;
use App\Models\Orders;
use App\Models\ReturnRequest;
use App\Services\Checkout\ReturnService;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $returns = ReturnRequest::where('user_id', $request->user()->id)
            ->with('items.orderItem.product')
            ->latest()
            ->paginate(15);

        return $this->success(ReturnRequestResource::collection($returns), 'Returns loaded successfully.');
    }

    public function store(ReturnRequestStoreRequest $request, ReturnService $returns, int $order)
    {
        $orderModel = Orders::where('user_id', $request->user()->id)->with('items')->findOrFail($order);

        return $this->success(
            new ReturnRequestResource($returns->create($request->user()->id, $orderModel, $request->validated())),
            'Return request created successfully.'
        );
    }

    public function show(Request $request, int $id)
    {
        $return = ReturnRequest::where('user_id', $request->user()->id)
            ->with('items.orderItem.product', 'order')
            ->findOrFail($id);

        return $this->success(new ReturnRequestResource($return), 'Return request loaded successfully.');
    }

    public function cancel(Request $request, int $id)
    {
        $return = ReturnRequest::where('user_id', $request->user()->id)->where('status', 'pending')->findOrFail($id);
        $return->update(['status' => 'cancelled']);

        return $this->success(new ReturnRequestResource($return->fresh()), 'Return request cancelled.');
    }
}
