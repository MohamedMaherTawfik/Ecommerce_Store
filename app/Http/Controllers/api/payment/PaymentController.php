<?php

namespace App\Http\Controllers\api\payment;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CheckoutRequest;
use App\Models\Addresses;
use App\Models\Orders;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    use ApiResponse {
        success as apiSuccess;
        error as apiError;
        notFound as apiNotFound;
    }

    public function __construct(private readonly CheckoutService $checkout) {}

    public function pay(CheckoutRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $method = 'paymob';
        $validated['payment_method'] = $method;

        $address = $this->resolveCheckoutAddress($request, $validated);
        $result = $this->checkout->placeOrder($request->user()->id, [
            ...$validated,
            'shipping_address_id' => $address->id,
        ]);
        $order = $result['order'];
        $payment = $result['payment'];

        Log::info('Checkout order created', [
            'user_id' => $request->user()->id,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'method' => $method,
        ]);

        return $this->apiSuccess([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status,
            'total' => $order->total,
            ...$payment,
        ], 'Payment initialized successfully.');
    }

    public function orderStatus(Request $request, int $id): JsonResponse
    {
        $order = Orders::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('statusLogs.changedBy:id,name')
            ->first();

        if (! $order) {
            return $this->apiNotFound('Order not found.');
        }

        return $this->apiSuccess([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total' => $order->total,
            'transaction_id' => $order->transaction_id,
            'paid_at' => $order->paid_at,
            'timeline' => $order->statusLogs->sortBy('created_at')->values(),
        ], 'Order status retrieved successfully.');
    }

    private function resolveCheckoutAddress(Request $request, array $data): Addresses
    {
        $user = $request->user();
        $street = trim((string) ($data['address'] ?? ''));
        $city = trim((string) ($data['city'] ?? ''));
        $country = trim((string) ($data['country'] ?? 'Egypt'));

        $address = Addresses::firstOrCreate(
            [
                'user_id' => $user->id,
                'phone' => $data['phone'],
                'street' => $street,
                'city' => $city,
                'country' => $country,
            ],
            [
                'type' => 'both',
                'name' => $user->name,
                'email' => $user->email,
                'country_code' => $data['country_code'] ?? 'EG',
                'address' => $street,
                'is_default_shipping' => ! Addresses::where('user_id', $user->id)->where('is_default_shipping', true)->exists(),
                'is_default_billing' => ! Addresses::where('user_id', $user->id)->where('is_default_billing', true)->exists(),
            ]
        );

        if ($address->wasRecentlyCreated && ($address->is_default_shipping || $address->is_default_billing)) {
            Addresses::where('user_id', $user->id)
                ->whereKeyNot($address->id)
                ->when(
                    $address->is_default_shipping,
                    fn ($query) => $query->update(['is_default_shipping' => false])
                );

            Addresses::where('user_id', $user->id)
                ->whereKeyNot($address->id)
                ->when(
                    $address->is_default_billing,
                    fn ($query) => $query->update(['is_default_billing' => false])
                );
        }

        return $address;
    }
}
