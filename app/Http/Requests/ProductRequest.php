<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
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
        $productId = $this->route('id');

        return [
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'return_policy' => 'nullable|string',
            'image' => 'nullable|image|extensions:jpg,jpeg,png,webp|mimes:jpg,jpeg,png,webp|dimensions:max_width=4096,max_height=4096|max:4096',
            'quantity' => 'required|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:1000',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|image|extensions:jpg,jpeg,png,webp|mimes:jpg,jpeg,png,webp|dimensions:max_width=4096,max_height=4096|max:4096',
            'canonical_url' => 'nullable|url|max:2048',
            'sku' => 'nullable|string|max:100',
            'tax' => 'nullable|numeric',

            'sizes' => 'nullable|array',
            'sizes.*' => 'string',

            'colors' => 'nullable|array',
            'colors.*' => 'string',

            'images' => 'nullable|array|max:5',
            'images.*' => 'image|extensions:jpg,jpeg,png,webp|mimes:jpg,jpeg,png,webp|dimensions:max_width=4096,max_height=4096|max:2048',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->sizes) {
            $this->merge([
                'sizes' => json_decode($this->sizes, true),
            ]);
        }

        if ($this->colors) {
            $this->merge([
                'colors' => json_decode($this->colors, true),
            ]);
        }

        $this->merge([
            'slug' => $this->filled('slug') ? str($this->input('slug'))->slug()->toString() : null,
        ]);
    }
}
