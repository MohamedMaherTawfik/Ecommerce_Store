<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShippingMethodRequest;
use App\Models\ShippingMethod;

class ShippingMethodController extends Controller
{
    use ApiResponse;

    public function index() { return $this->success(ShippingMethod::with('rates')->orderBy('name')->paginate(20), 'Shipping methods loaded.'); }
    public function show(int $id) { return $this->success(ShippingMethod::with('rates')->findOrFail($id), 'Shipping method loaded.'); }

    public function store(ShippingMethodRequest $request)
    {
        return $this->success(ShippingMethod::create($request->validated()), 'Shipping method created.');
    }

    public function update(ShippingMethodRequest $request, int $id)
    {
        $method = ShippingMethod::findOrFail($id);
        $method->update($request->validated());

        return $this->success($method->fresh(), 'Shipping method updated.');
    }

    public function destroy(int $id)
    {
        ShippingMethod::findOrFail($id)->delete();

        return $this->success([], 'Shipping method deleted.');
    }
}
