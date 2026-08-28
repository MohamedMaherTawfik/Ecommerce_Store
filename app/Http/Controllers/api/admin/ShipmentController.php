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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipmentController extends Controller
{
    use ApiResponse;

    private const TRANSITIONS = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['label_created', 'shipped', 'failed', 'cancelled'],
        'label_created' => ['shipped', 'failed', 'cancelled'],
        'shipped' => ['in_transit', 'delivered', 'returned'],
        'in_transit' => ['delivered', 'returned'],
        'failed' => ['processing', 'cancelled'],
        'delivered' => ['returned'],
        'returned' => [],
        'cancelled' => [],
    ];

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
        [$shipment, $changed] = DB::transaction(function () use ($request, $order): array {
            $shipment = Shipment::where('order_id', $order)->lockForUpdate()->firstOrFail();
            $from = $shipment->shipment_status;
            $to = $request->validated('shipment_status');

            if ($from !== $to && ! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
                throw ValidationException::withMessages([
                    'shipment_status' => "Invalid shipment status transition from {$from} to {$to}.",
                ]);
            }

            $shipment->update($request->validated() + [
                'shipped_at' => $to === 'shipped' ? ($shipment->shipped_at ?: now()) : $shipment->shipped_at,
                'delivered_at' => $to === 'delivered' ? ($shipment->delivered_at ?: now()) : $shipment->delivered_at,
            ]);
            $shipment->order()->update(['shipping_status' => $shipment->shipment_status]);

            return [$shipment->fresh('order.user'), $from !== $to];
        });

        if ($changed && $shipment->shipment_status === 'shipped') {
            $shipment->order->user?->notify(new OrderShippedNotification($shipment->order->fresh(['shipment'])));
        }

        if ($changed && $shipment->shipment_status === 'delivered') {
            $shipment->order->user?->notify(new OrderDeliveredNotification($shipment->order->fresh(['shipment'])));
        }

        return $this->success($shipment->fresh(), 'Shipment status updated.');
    }
}
