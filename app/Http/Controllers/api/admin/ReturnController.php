<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReturnStatusRequest;
use App\Http\Resources\Home\ReturnRequestResource;
use App\Models\ReturnRequest;
use App\Services\Checkout\ReturnService;

class ReturnController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(ReturnRequestResource::collection(
            ReturnRequest::with(['user', 'order', 'items.orderItem.product'])->latest()->paginate(20)
        ), 'Returns loaded.');
    }

    public function show(int $id)
    {
        return $this->success(new ReturnRequestResource(
            ReturnRequest::with(['user', 'order', 'items.orderItem.product'])->findOrFail($id)
        ), 'Return loaded.');
    }

    public function approve(ReturnStatusRequest $request, ReturnService $returns, int $id)
    {
        return $this->success(new ReturnRequestResource($returns->updateStatus(ReturnRequest::findOrFail($id), 'approved', $request->validated('admin_note'))), 'Return approved.');
    }

    public function reject(ReturnStatusRequest $request, ReturnService $returns, int $id)
    {
        return $this->success(new ReturnRequestResource($returns->updateStatus(ReturnRequest::findOrFail($id), 'rejected', $request->validated('admin_note'))), 'Return rejected.');
    }

    public function markReceived(ReturnStatusRequest $request, ReturnService $returns, int $id)
    {
        return $this->success(new ReturnRequestResource($returns->updateStatus(ReturnRequest::findOrFail($id), 'received', $request->validated('admin_note'))), 'Return marked received.');
    }

    public function refund(ReturnStatusRequest $request, ReturnService $returns, int $id)
    {
        return $this->success($returns->refund(ReturnRequest::with('order.latestPayment')->findOrFail($id), $request->validated()), 'Refund recorded.');
    }
}
