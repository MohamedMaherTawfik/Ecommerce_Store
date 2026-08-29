<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Home\CheckoutSelectAddressRequest;
use App\Http\Requests\Home\CheckoutSelectShippingRequest;
use App\Http\Requests\Home\CheckoutShippingRateRequest;
use App\Http\Requests\Home\PlaceOrderRequest;
use App\Http\Resources\Home\CheckoutSummaryResource;
use App\Http\Resources\Home\OrderCheckoutResource;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CheckoutService $checkout) {}

    public function summary(Request $request)
    {
        return $this->success(
            new CheckoutSummaryResource($this->checkout->summary($request->user()->id)),
            'Checkout summary loaded successfully.'
        );
    }

    public function selectAddress(CheckoutSelectAddressRequest $request)
    {
        return $this->success(
            new CheckoutSummaryResource($this->checkout->selectAddress($request->user()->id, (int) $request->validated('address_id'))),
            'Checkout address selected.'
        );
    }

    public function shippingRates(CheckoutShippingRateRequest $request)
    {
        return $this->success(
            $this->checkout->shippingRates($request->user()->id, $request->validated('address_id')),
            'Shipping rates loaded successfully.'
        );
    }

    public function selectShipping(CheckoutSelectShippingRequest $request)
    {
        return $this->success(
            new CheckoutSummaryResource($this->checkout->selectShipping($request->user()->id, $request->validated())),
            'Shipping method selected.'
        );
    }

    public function placeOrder(PlaceOrderRequest $request)
    {
        $result = $this->checkout->placeOrder($request->user()->id, $request->validated());

        return $this->success([
            'order' => new OrderCheckoutResource($result['order']),
            'payment' => $result['payment'],
        ], 'Order placed successfully.');
    }
}
