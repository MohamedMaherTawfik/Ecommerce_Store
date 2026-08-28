<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShippingZoneRequest;
use App\Models\ShippingZone;

class ShippingZoneController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(ShippingZone::latest()->paginate(20), 'Shipping zones loaded.');
    }

    public function store(ShippingZoneRequest $request)
    {
        return $this->success(ShippingZone::create($request->validated()), 'Shipping zone created.');
    }

    public function update(ShippingZoneRequest $request, int $id)
    {
        $zone = ShippingZone::findOrFail($id);
        $zone->update($request->validated());

        return $this->success($zone->fresh(), 'Shipping zone updated.');
    }

    public function destroy(int $id)
    {
        ShippingZone::findOrFail($id)->delete();

        return $this->success([], 'Shipping zone deleted.');
    }
}
