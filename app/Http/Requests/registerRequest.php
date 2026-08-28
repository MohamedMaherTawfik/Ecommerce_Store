<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class registerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

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
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[\p{L}\d\s]+$/u',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'image' => [
                'nullable',
                'image',
                'extensions:jpg,jpeg,png,webp',
                'mimes:jpg,jpeg,png,webp',
                'dimensions:max_width=4096,max_height=4096',
                'max:5120',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:255',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'name.regex' => 'يجب أن يحتوي الاسم على أحرف و ارقام فقط (بدون رموز).',
            'image.image' => 'الصورة يجب ان تكون صورة.',
            'image.max' => 'حجم الصورة يجب ان يكون اقل من 2MB.',
            'image.mimes' => 'الصورة يجب ان تكون من نوع jpeg, png, jpg, gif.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.confirmed' => 'كلمتا المرور غير متطابقتين.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
        ];
    }
}
