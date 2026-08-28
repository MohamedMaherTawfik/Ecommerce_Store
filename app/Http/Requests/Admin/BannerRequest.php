<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type' => ($this->isMethod('post') ? 'required' : 'sometimes').'|in:hero,promo,newsletter',
            'eyebrow' => 'nullable|string|max:255',
            'title' => ($this->isMethod('post') ? 'required' : 'sometimes').'|string|max:255',
            'subtitle' => 'nullable|string',
            'cta_text' => 'nullable|string|max:255',
            'cta_link' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:255',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ];
    }
}
