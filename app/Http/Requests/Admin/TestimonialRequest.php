<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class TestimonialRequest extends FormRequest {
    public function authorize() { return true; }
    public function rules() {
        return [
            'name' => ($this->isMethod('post') ? 'required' : 'sometimes') . '|string|max:255',
            'role' => ($this->isMethod('post') ? 'required' : 'sometimes') . '|string|max:255',
            'text' => ($this->isMethod('post') ? 'required' : 'sometimes') . '|string',
            'avatar' => 'nullable|string',
            'rating' => 'integer|min:1|max:5',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ];
    }
}
