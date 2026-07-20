<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DatabaseSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $driver = strtolower((string) $this->input('driver', 'sqlite'));

        $this->merge([
            'driver' => $driver,
            'host' => $this->filled('host') ? trim((string) $this->input('host')) : null,
            'port' => $this->filled('port') ? trim((string) $this->input('port')) : null,
            'database' => $this->filled('database') ? trim((string) $this->input('database')) : null,
            'username' => $this->filled('username') ? trim((string) $this->input('username')) : null,
            'password' => $this->has('password') ? (string) $this->input('password') : null,
            'sqlite_path' => $this->filled('sqlite_path') ? trim((string) $this->input('sqlite_path')) : null,
        ]);
    }

    public function rules(): array
    {
        $driver = strtolower((string) $this->input('driver', 'sqlite'));
        $requiresServerFields = in_array($driver, ['mysql', 'pgsql'], true);

        return [
            'driver' => ['required', Rule::in(['sqlite', 'mysql', 'pgsql'])],
            'host' => [Rule::requiredIf($requiresServerFields), 'nullable', 'string', 'max:255'],
            'port' => [Rule::requiredIf($requiresServerFields), 'nullable', 'integer', 'min:1', 'max:65535'],
            'database' => [Rule::requiredIf($requiresServerFields), 'nullable', 'string', 'max:255'],
            'username' => [Rule::requiredIf($requiresServerFields), 'nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'sqlite_path' => [Rule::requiredIf($driver === 'sqlite'), 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'driver.in' => 'The selected database driver is not supported.',
            'host.required' => 'Database host is required for the selected driver.',
            'port.required' => 'Database port is required for the selected driver.',
            'database.required' => 'Database name is required for the selected driver.',
            'username.required' => 'Database username is required for the selected driver.',
            'sqlite_path.required' => 'SQLite file path is required when using sqlite.',
        ];
    }
}
