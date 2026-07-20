<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SendApplicationSettingsTestMailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'recipient_email' => $this->filled('recipient_email')
                ? trim((string) $this->input('recipient_email'))
                : optional($this->user())->email,
        ]);
    }

    public function rules(): array
    {
        return [
            'recipient_email' => ['required', 'email', 'max:255'],
        ];
    }
}
