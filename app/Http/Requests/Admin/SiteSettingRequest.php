<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SiteSettingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $key = $this->route('key');

        return [
            'key' => ($key ? 'sometimes|string|max:255' : 'required|string|unique:site_settings,key|max:255'),
            'value' => $this->hasFile('value')
                ? 'nullable|image|extensions:jpg,jpeg,png,webp,ico|mimes:jpg,jpeg,png,webp,ico|dimensions:max_width=4096,max_height=4096|max:4096'
                : 'nullable|string|max:10000',
        ];
    }
}
