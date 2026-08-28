<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class NavLinkRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'key' => ($this->isMethod('post') ? 'required' : 'sometimes').'|string|max:255',
            'route' => ($this->isMethod('post') ? 'required' : 'sometimes').'|string|max:255',
            'icon' => 'nullable|string|max:255',
            'location' => ($this->isMethod('post') ? 'required' : 'sometimes').'|in:navbar,footer_quick,footer_support',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ];
    }
}
