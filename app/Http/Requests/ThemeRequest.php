<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ThemeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'primary' => 'required|string|max:20',
            'secondary' => 'required|string|max:20',
            'accent' => 'required|string|max:20',

            'background' => 'nullable|string|max:20',
            'surface' => 'nullable|string|max:20',
            'border' => 'nullable|string|max:20',

            'text' => 'nullable|string|max:20',
            'text_secondary' => 'nullable|string|max:20',

            'success' => 'nullable|string|max:20',
            'warning' => 'nullable|string|max:20',
            'danger' => 'nullable|string|max:20',
            'info' => 'nullable|string|max:20',

            'hero_from' => 'nullable|string|max:20',
            'hero_to' => 'nullable|string|max:20',

            'is_dark' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];
    }
}
