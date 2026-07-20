<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class OrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:pending,approved,paid,failed,cancelled,shipped,delivered'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
