<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BrandRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|extensions:jpg,jpeg,png,webp|mimes:jpeg,png,jpg,webp|dimensions:max_width=4096,max_height=4096|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Brand name is required',
            'image.required' => 'Image is required',
            'image.image' => 'The image must be an image',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif',
            'image.max' => 'The image may not be greater than 2MB',
        ];
    }
}
