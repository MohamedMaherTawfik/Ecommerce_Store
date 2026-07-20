<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Home\AddressRequest;
use App\Http\Resources\Home\AddressResource;
use App\Models\Addresses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $addresses = Addresses::where('user_id', $request->user()->id)->latest()->get();

        return $this->success(AddressResource::collection($addresses), 'Addresses loaded successfully.');
    }

    public function store(AddressRequest $request)
    {
        $address = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['user_id'] = $request->user()->id;
            $address = Addresses::create($data);
            $this->applyDefaults($address, $data);

            return $address->fresh();
        });

        return $this->success(new AddressResource($address), 'Address created successfully.');
    }

    public function show(Request $request, int $id)
    {
        return $this->success(
            new AddressResource($this->findOwned($request, $id)),
            'Address loaded successfully.'
        );
    }

    public function update(AddressRequest $request, int $id)
    {
        $address = DB::transaction(function () use ($request, $id) {
            $address = $this->findOwned($request, $id);
            $data = $request->validated();
            $address->update($data);
            $this->applyDefaults($address, $data);

            return $address->fresh();
        });

        return $this->success(new AddressResource($address), 'Address updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $this->findOwned($request, $id)->delete();

        return $this->success([], 'Address deleted successfully.');
    }

    public function defaultShipping(Request $request, int $id)
    {
        $address = $this->findOwned($request, $id);
        $this->setDefault($address, 'is_default_shipping');

        return $this->success(new AddressResource($address->fresh()), 'Default shipping address updated.');
    }

    public function defaultBilling(Request $request, int $id)
    {
        $address = $this->findOwned($request, $id);
        $this->setDefault($address, 'is_default_billing');

        return $this->success(new AddressResource($address->fresh()), 'Default billing address updated.');
    }

    private function findOwned(Request $request, int $id): Addresses
    {
        return Addresses::where('user_id', $request->user()->id)->findOrFail($id);
    }

    private function applyDefaults(Addresses $address, array $data): void
    {
        if (filter_var($data['is_default_shipping'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $this->setDefault($address, 'is_default_shipping');
        }

        if (filter_var($data['is_default_billing'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $this->setDefault($address, 'is_default_billing');
        }
    }

    private function setDefault(Addresses $address, string $column): void
    {
        Addresses::where('user_id', $address->user_id)->update([$column => false]);
        Addresses::whereKey($address->id)->update([$column => true]);
    }
}
