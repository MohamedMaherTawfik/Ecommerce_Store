<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class FeatureRequest extends FormRequest {
    public function authorize() { return true; }
    public function rules() {
        return [
            'icon' => ($this->isMethod('post') ? 'required' : 'sometimes') . '|string|max:255',
            'label' => ($this->isMethod('post') ? 'required' : 'sometimes') . '|string|max:255',
            'text' => ($this->isMethod('post') ? 'required' : 'sometimes') . '|string',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ];
    }
}
