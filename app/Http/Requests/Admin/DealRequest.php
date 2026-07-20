<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class DealRequest extends FormRequest {
    public function authorize() { return true; }
    public function rules() {
        return [
            'name' => ($this->isMethod('post') ? 'required' : 'sometimes') . '|string|max:255',
            'category' => ($this->isMethod('post') ? 'required' : 'sometimes') . '|string|max:255',
            'icon' => 'nullable|string|max:255',
            'discount' => ($this->isMethod('post') ? 'required' : 'sometimes') . '|integer|min:0',
            'sale_price' => ($this->isMethod('post') ? 'required' : 'sometimes') . '|numeric|min:0',
            'original_price' => ($this->isMethod('post') ? 'required' : 'sometimes') . '|numeric|min:0',
            'sold_percent' => 'integer|min:0|max:100',
            'sold_label' => 'nullable|string|max:255',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date',
        ];
    }
}
