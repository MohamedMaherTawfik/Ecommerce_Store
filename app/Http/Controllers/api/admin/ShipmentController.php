<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShipmentStatusRequest;
use App\Models\Orders;
use App\Models\Shipment;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\OrderShippedNotification;
use App\Services\Shipping\ShippingProviderManager;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    use ApiResponse;

    public function create(Request $request, int $order)
    {
        $orderModel = Orders::with('shipment')->findOrFail($order);
        $shipment = $orderModel->shipment ?: Shipment::create([
            'order_id' => $orderModel->id,
            'provider' => $request->input('provider', 'manual'),
            'shipment_status' => 'processing',
            'currency' => $orderModel->currency ?? config('checkout.currency'),
        ]);

        return $this->success($shipment->fresh(), 'Shipment created.');
    }

    public function buyLabel(Request $request, ShippingProviderManager $providers, int $order)
    {
        $shipment = Orders::findOrFail($order)->shipment()->firstOrFail();
        $result = $providers->resolve($shipment->provider)->buyLabel($request->all() + [
            'shipment_id' => $shipment->easypost_shipment_id,
            'rate_id' => $request->input('rate_id', $shipment->rate_id),
        ]);

        $shipment->update([
            'tracking_number' => $result['tracking_number'] ?? $shipment->tracking_number,
            'tracking_url' => $result['tracking_url'] ?? $shipment->tracking_url,
            'label_url' => $result['label_url'] ?? $shipment->label_url,
            'shipment_status' => 'label_created',
            'raw_response' => $result,
        ]);

        return $this->success($shipment->fresh(), 'Shipment label created.');
    }

    public function track(ShippingProviderManager $providers, int $order)
    {
        $shipment = Orders::findOrFail($order)->shipment()->firstOrFail();

        return $this->success(
            $providers->resolve($shipment->provider)->track((string) $shipment->tracking_number),
            'Shipment tracking loaded.'
        );
    }

    public function updateStatus(ShipmentStatusRequest $request, int $order)
    {
        $shipment = Orders::findOrFail($order)->shipment()->firstOrFail();
        $shipment->update($request->validated() + [
            'shipped_at' => $request->validated('shipment_status') === 'shipped' ? now() : $shipment->shipped_at,
            'delivered_at' => $request->validated('shipment_status') === 'delivered' ? now() : $shipment->delivered_at,
        ]);
        $shipment->order->update(['shipping_status' => $shipment->shipment_status]);

        if ($shipment->shipment_status === 'shipped') {
            $shipment->order->user?->notify(new OrderShippedNotification($shipment->order->fresh(['shipment'])));
        }

        if ($shipment->shipment_status === 'delivered') {
            $shipment->order->user?->notify(new OrderDeliveredNotification($shipment->order->fresh(['shipment'])));
        }

        return $this->success($shipment->fresh(), 'Shipment status updated.');
    }
}
