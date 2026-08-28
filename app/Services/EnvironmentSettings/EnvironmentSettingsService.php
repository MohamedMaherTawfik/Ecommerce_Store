<?php

namespace App\Services\EnvironmentSettings;

use App\Repositories\EnvironmentSettingsRepository;
use App\Support\Env\EnvEditor;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class EnvironmentSettingsService
{
    public function __construct(
        private readonly EnvEditor $envEditor,
        private readonly EnvironmentSettingsRepository $repository
    ) {}

    public function getPagePayload(): array
    {
        $tabs = config('environment_settings.tabs', []);

        return [
            'permission' => config('environment_settings.permission'),
            'tabs' => $this->transformTabs($tabs),
            'values' => $this->resolveCurrentValues($tabs, true),
            'meta' => [
                'test_mail_recipient' => optional(auth()->user())->email,
            ],
        ];
    }

    public function update(array $validated): array
    {
        $payload = $this->normalizeForSave($validated);

        // Update .env
        $this->envEditor->setMany($payload);

        // Update Database
        $this->saveToDatabase($payload);

        // Refresh Runtime
        $this->refreshRuntimeState($payload);

        return [
            'permission' => config('environment_settings.permission'),
            'tabs' => $this->transformTabs(config('environment_settings.tabs', [])),
            'values' => $this->redactValues($payload),
            'meta' => [
                'test_mail_recipient' => optional(auth()->user())->email,
            ],
        ];
    }

    public function sendTestMail(string $recipientEmail): array
    {
        $this->refreshRuntimeState($this->resolveCurrentValues(config('environment_settings.tabs', []), false));

        app()->forgetInstance('mail.manager');
        app()->forgetInstance('mailer');

        Mail::raw(
            "This is a test email from {$this->currentAppName()}.\n\nIf you received this message, the current mail configuration is working correctly.",
            function ($message) use ($recipientEmail) {
                $message
                    ->to($recipientEmail)
                    ->subject('Environment Settings Test Email');
            }
        );

        return [
            'recipient_email' => $recipientEmail,
            'sent_at' => now()->toDateTimeString(),
        ];
    }

    private function resolveCurrentValues(array $tabs, bool $redactSecrets): array
    {
        $values = [];
        $dbSettings = $this->repository->getAllGrouped();

        foreach ($tabs as $groupKey => $tab) {
            foreach (($tab['fields'] ?? []) as $key => $definition) {
                $type = $definition['type'] ?? 'text';
                $isEncrypted = $type === 'password';

                $dbSetting = $this->repository->getByKey($key);

                if ($dbSetting && $dbSetting->value !== null) {
                    $resolved = $isEncrypted ? Crypt::decryptString($dbSetting->value) : $dbSetting->value;
                } else {
                    $fallbackKey = $definition['fallback_to'] ?? null;
                    $fallback = $fallbackKey ? $this->envEditor->get($fallbackKey) : null;
                    $resolved = $this->envEditor->get($key, $fallback);

                    if (($resolved === null || $resolved === '') && $fallbackKey) {
                        $resolved = $this->envEditor->get($fallbackKey, '');
                    }
                }

                if ($isEncrypted && $redactSecrets) {
                    $values[$key] = ['configured' => filled($resolved)];
                } else {
                    $values[$key] = $type === 'toggle'
                        ? filter_var($resolved, FILTER_VALIDATE_BOOLEAN)
                        : (string) ($resolved ?? '');
                }
            }
        }

        return $values;
    }

    private function transformTabs(array $tabs): array
    {
        $transformed = [];

        foreach ($tabs as $key => $tab) {
            $transformed[$key] = [
                'key' => $key,
                'label' => $tab['label'] ?? $key,
                'description' => $tab['description'] ?? null,
                'icon' => $tab['icon'] ?? 'bi bi-gear',
                'actions' => $tab['actions'] ?? [],
                'fields' => collect($tab['fields'] ?? [])->map(function (array $field, string $fieldKey) {
                    return [
                        'key' => $fieldKey,
                        'label' => $field['label'] ?? $fieldKey,
                        'type' => $field['type'] ?? 'text',
                        'placeholder' => $field['placeholder'] ?? null,
                        'help' => $field['help'] ?? null,
                        'fallback_to' => $field['fallback_to'] ?? null,
                        'section' => $field['section'] ?? 'Settings',
                        'options' => $field['options'] ?? [],
                    ];
                })->values()->all(),
            ];
        }

        return $transformed;
    }

    private function normalizeForSave(array $validated): array
    {
        $payload = [];
        $fields = $this->managedFields();
        $current = $this->resolveCurrentValues(config('environment_settings.tabs', []), false);

        foreach ($fields as $key => $definition) {
            $type = $definition['type'] ?? 'text';

            if ($type === 'toggle') {
                $payload[$key] = filter_var($validated[$key] ?? false, FILTER_VALIDATE_BOOLEAN);

                continue;
            }

            $incoming = trim((string) ($validated[$key] ?? ''));
            $payload[$key] = $type === 'password' && $incoming === ''
                ? (string) ($current[$key] ?? '')
                : $incoming;
        }

        if (($payload['GOOGLE_REDIRECT_URL'] ?? '') === '') {
            $payload['GOOGLE_REDIRECT_URL'] = $payload['GOOGLE_REDIRECT_URI'] ?? '';
        }

        if (($payload['MAIL_SCHEME'] ?? '') === 'null') {
            $payload['MAIL_SCHEME'] = '';
        }

        return $payload;
    }

    private function saveToDatabase(array $payload): void
    {
        $tabs = config('environment_settings.tabs', []);

        foreach ($tabs as $groupKey => $tab) {
            foreach (($tab['fields'] ?? []) as $key => $definition) {
                if (array_key_exists($key, $payload)) {
                    $value = $payload[$key];
                    $type = $definition['type'] ?? 'text';
                    $isEncrypted = $type === 'password';

                    if ($value !== null && $value !== '') {
                        $valueToSave = $isEncrypted ? Crypt::encryptString((string) $value) : (string) $value;
                    } else {
                        $valueToSave = null;
                    }

                    $this->repository->updateOrCreate(
                        $groupKey,
                        $key,
                        $valueToSave,
                        $type,
                        $isEncrypted
                    );
                }
            }
        }
    }

    private function redactValues(array $payload): array
    {
        if (($payload['GOOGLE_REDIRECT_URL'] ?? '') === '') {
            $payload['GOOGLE_REDIRECT_URL'] = $payload['GOOGLE_REDIRECT_URI'] ?? '';
        }

        foreach ($this->managedFields() as $key => $definition) {
            if (($definition['type'] ?? 'text') === 'password') {
                $payload[$key] = ['configured' => filled($payload[$key] ?? null)];
            }
        }

        return $payload;
    }

    private function refreshRuntimeState(array $payload): void
    {
        foreach ($payload as $key => $value) {
            $stringValue = is_bool($value) ? ($value ? 'true' : 'false') : (string) ($value ?? '');

            putenv("{$key}={$stringValue}");
            $_ENV[$key] = $stringValue;
            $_SERVER[$key] = $stringValue;
        }

        Artisan::call('config:clear');
        Artisan::call('cache:clear');

        Config::set('services.google.client_id', (string) ($payload['GOOGLE_CLIENT_ID'] ?? ''));
        Config::set('services.google.client_secret', (string) ($payload['GOOGLE_CLIENT_SECRET'] ?? ''));
        Config::set('services.google.redirect', (string) ($payload['GOOGLE_REDIRECT_URI'] ?? ''));
        Config::set('services.google.redirect_url', (string) ($payload['GOOGLE_REDIRECT_URL'] ?? ($payload['GOOGLE_REDIRECT_URI'] ?? '')));

        Config::set('payment.gateways.paymob.enabled', filter_var($payload['PAYMOB_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN));
        Config::set('payment.gateways.paymob.base_url', (string) ($payload['PAYMOB_BASE_URL'] ?? 'https://accept.paymob.com'));
        Config::set('payment.gateways.paymob.public_key', (string) ($payload['PAYMOB_PUBLIC_KEY'] ?? ''));
        Config::set('payment.gateways.paymob.secret_key', (string) ($payload['PAYMOB_SECRET_KEY'] ?? ''));
        Config::set('payment.gateways.paymob.hmac_secret', (string) ($payload['PAYMOB_HMAC_SECRET'] ?? ''));
        Config::set('payment.gateways.paymob.currency', (string) ($payload['PAYMOB_CURRENCY'] ?? 'EGP'));
        Config::set('payment.gateways.paymob.iframe_id', (string) ($payload['PAYMOB_IFRAME_ID'] ?? ''));
        Config::set('payment.gateways.paymob.integration_ids.card', (string) ($payload['PAYMOB_INTEGRATION_ID_CARD'] ?? ''));
        Config::set('payment.gateways.paymob.integration_ids.mobile_wallet', (string) ($payload['PAYMOB_INTEGRATION_ID_WALLET'] ?? ''));
        Config::set('payment.gateways.paymob.integration_ids.apple_pay', (string) ($payload['PAYMOB_INTEGRATION_ID_APPLE_PAY'] ?? ''));
        Config::set('payment.urls.callback', (string) ($payload['PAYMOB_CALLBACK_URL'] ?? ''));
        Config::set('payment.urls.webhook', (string) ($payload['PAYMOB_WEBHOOK_URL'] ?? ''));

        Config::set('mail.default', (string) ($payload['MAIL_MAILER'] ?? 'log'));
        Config::set('mail.mailers.smtp.scheme', $this->nullableValue($payload['MAIL_SCHEME'] ?? null));
        Config::set('mail.mailers.smtp.host', (string) ($payload['MAIL_HOST'] ?? '127.0.0.1'));
        Config::set('mail.mailers.smtp.port', (int) ($payload['MAIL_PORT'] ?? 2525));
        Config::set('mail.mailers.smtp.username', $this->nullableValue($payload['MAIL_USERNAME'] ?? null));
        Config::set('mail.mailers.smtp.password', $this->nullableValue($payload['MAIL_PASSWORD'] ?? null));
        Config::set('mail.from.address', (string) ($payload['MAIL_FROM_ADDRESS'] ?? 'hello@example.com'));
        Config::set('mail.from.name', (string) ($payload['MAIL_FROM_NAME'] ?? $this->currentAppName()));

        Config::set('filesystems.disks.s3.key', $this->nullableValue($payload['AWS_ACCESS_KEY_ID'] ?? null));
        Config::set('filesystems.disks.s3.secret', $this->nullableValue($payload['AWS_SECRET_ACCESS_KEY'] ?? null));
        Config::set('filesystems.disks.s3.region', (string) ($payload['AWS_DEFAULT_REGION'] ?? 'us-east-1'));
        Config::set('filesystems.disks.s3.bucket', $this->nullableValue($payload['AWS_BUCKET'] ?? null));
        Config::set('filesystems.disks.s3.use_path_style_endpoint', filter_var($payload['AWS_USE_PATH_STYLE_ENDPOINT'] ?? false, FILTER_VALIDATE_BOOLEAN));
    }

    private function managedFields(): array
    {
        return collect(config('environment_settings.tabs', []))
            ->pluck('fields')
            ->filter(fn ($fields) => is_array($fields))
            ->collapse()
            ->all();
    }

    private function currentAppName(): string
    {
        return (string) $this->envEditor->get('APP_NAME', config('app.name', 'Laravel'));
    }

    private function nullableValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
