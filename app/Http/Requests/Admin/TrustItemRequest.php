<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class TrustItemRequest extends FormRequest {
    public function authorize() { return true; }
    public function rules() {
        return [
            'icon' => ($this->isMethod('post') ? 'required' : 'sometimes') . '|string|max:255',
            'label' => ($this->isMethod('post') ? 'required' : 'sometimes') . '|string|max:255',
            'sub' => ($this->isMethod('post') ? 'required' : 'sometimes') . '|string|max:255',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ];
    }
}
