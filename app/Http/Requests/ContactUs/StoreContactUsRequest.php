<?php

namespace App\Http\Requests\ContactUs;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactUsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ];

        if ($this->user('sanctum')) {
            $rules['name'] = ['nullable'];
            $rules['email'] = ['nullable'];
        } else {
            $rules['name'] = ['nullable', 'string', 'max:255'];
            $rules['email'] = ['nullable', 'email', 'max:255'];
        }

        return $rules;
    }
}
