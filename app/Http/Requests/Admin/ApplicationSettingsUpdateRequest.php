<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationSettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach ($this->managedFields() as $field => $definition) {
            $type = $definition['type'] ?? 'text';

            if ($type === 'toggle') {
                $payload[$field] = filter_var($this->input($field, false), FILTER_VALIDATE_BOOLEAN);

                continue;
            }

            $payload[$field] = $this->has($field)
                ? trim((string) $this->input($field))
                : null;
        }

        if (($payload['GOOGLE_REDIRECT_URL'] ?? '') === '') {
            $payload['GOOGLE_REDIRECT_URL'] = $payload['GOOGLE_REDIRECT_URI'] ?? null;
        }

        if (($payload['MAIL_SCHEME'] ?? null) === 'null') {
            $payload['MAIL_SCHEME'] = '';
        }

        $this->merge($payload);
    }

    public function rules(): array
    {
        $rules = [];

        foreach ($this->managedFields() as $field => $definition) {
            $rules[$field] = $definition['rules'] ?? ['nullable', 'string'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->managedFields() as $field => $definition) {
            $attributes[$field] = $definition['label'] ?? $field;
        }

        return $attributes;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function managedFields(): array
    {
        return collect(config('application_settings.tabs', []))
            ->pluck('fields')
            ->filter(fn ($fields) => is_array($fields))
            ->collapse()
            ->all();
    }
}
