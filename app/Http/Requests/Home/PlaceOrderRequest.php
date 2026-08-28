<?php

namespace App\Http\Requests\Home;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_address_id' => ['required', 'integer', 'exists:addresses,id'],
            'payment_method' => ['nullable', 'in:paymob'],
            'payment_channel' => ['required', 'in:card,apple_pay,mobile_wallet'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['required', 'string', 'min:8', 'max:128', 'regex:/^[A-Za-z0-9][A-Za-z0-9._:-]+$/'],
        ];
    }
}
