<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderIndexRequest;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\Http\Resources\Admin\OrderResource;
use App\Services\Admin\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    public function index(OrderIndexRequest $request): AnonymousResourceCollection
    {
        return OrderResource::collection($this->orders->paginate($request->validated()));
    }

    public function show(int $id): OrderResource
    {
        return new OrderResource($this->orders->find($id));
    }

    public function updateStatus(OrderStatusRequest $request, int $id): OrderResource
    {
        $validated = $request->validated();

        return new OrderResource(
            $this->orders->updatePaymentStatus($id, $validated['status'], $validated['note'] ?? null)
        );
    }

    public function updateOrderStatus(Request $request, int $id): OrderResource
    {
        $validated = $request->validate([
            'order_status' => ['required', 'string', 'in:pending,confirmed,processing,completed,cancelled'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        return new OrderResource($this->orders->updateDedicatedStatus($id, 'order_status', $validated['order_status'], $validated['note'] ?? null));
    }

    public function updatePaymentStatus(Request $request, int $id): OrderResource
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'string', 'in:unpaid,pending,paid,failed,cancelled,refunded,partially_refunded'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        return new OrderResource($this->orders->updateDedicatedStatus($id, 'payment_status', $validated['payment_status'], $validated['note'] ?? null));
    }

    public function updateShippingStatus(Request $request, int $id): OrderResource
    {
        $validated = $request->validate([
            'shipping_status' => ['required', 'string', 'in:pending,packed,shipped,in_transit,delivered,returned,cancelled'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        return new OrderResource($this->orders->updateDedicatedStatus($id, 'shipping_status', $validated['shipping_status'], $validated['note'] ?? null));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->orders->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Order deleted successfully.',
        ]);
    }
}
