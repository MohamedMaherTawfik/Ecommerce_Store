<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['nullable', 'in:paymob'],
            'payment_channel' => ['required', 'in:card,apple_pay,mobile_wallet'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['required', 'string', 'min:8', 'max:128', 'regex:/^[A-Za-z0-9][A-Za-z0-9._:-]+$/'],
            'use_wallet' => ['nullable', 'boolean'],
            'wallet_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
