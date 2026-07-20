<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShipmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipment_status' => ['required', Rule::in(['pending', 'processing', 'label_created', 'shipped', 'in_transit', 'delivered', 'failed', 'returned', 'cancelled'])],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:1000'],
            'label_url' => ['nullable', 'url', 'max:1000'],
        ];
    }
}
