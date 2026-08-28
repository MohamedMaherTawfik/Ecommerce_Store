<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class categoreyRequest extends FormRequest
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
        $categoryId = $this->route('id');

        return [
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($categoryId)],
            'description' => 'nullable|string|max:5000',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:1000',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|url|max:2048',
            'image' => 'nullable|image|extensions:jpg,jpeg,png,webp|mimes:jpeg,png,jpg,webp|dimensions:max_width=4096,max_height=4096|max:4096',
            'og_image' => 'nullable|image|extensions:jpg,jpeg,png,webp|mimes:jpeg,png,jpg,webp|dimensions:max_width=4096,max_height=4096|max:4096',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->filled('slug') ? str($this->input('slug'))->slug()->toString() : null,
        ]);
    }

    public function messages()
    {
        return [
            'name.required' => 'Category name is required',
            'image.required' => 'Image is required',
            'image.image' => 'The image must be an image',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif',
            'image.max' => 'The image may not be greater than 2MB',
        ];
    }
}
