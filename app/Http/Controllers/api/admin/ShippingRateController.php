<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShippingRateRequest;
use App\Models\ShippingRate;

class ShippingRateController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(ShippingRate::with(['method', 'zone'])->latest()->paginate(20), 'Shipping rates loaded.');
    }

    public function store(ShippingRateRequest $request)
    {
        return $this->success(ShippingRate::create($request->validated()), 'Shipping rate created.');
    }

    public function update(ShippingRateRequest $request, int $id)
    {
        $rate = ShippingRate::findOrFail($id);
        $rate->update($request->validated());

        return $this->success($rate->fresh(['method', 'zone']), 'Shipping rate updated.');
    }

    public function destroy(int $id)
    {
        ShippingRate::findOrFail($id)->delete();

        return $this->success([], 'Shipping rate deleted.');
    }
}
