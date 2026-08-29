<?php

namespace App\Services\Installer;

use App\Support\Testing\TestIsolationGuard;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\File;
use RuntimeException;

class EnvironmentSetupService
{
    public function __construct(private readonly InstallationStateService $installationState) {}

    public function ensureBootstrapState(): void
    {
        if (app()->environment('testing')) {
            TestIsolationGuard::assertDatabase();

            if (! config('testing.installer_mode', false)) {
                return;
            }

            TestIsolationGuard::assertInstallerPaths();
        }

        $this->ensureEnvironmentFileExists();
        $this->ensureAppKey();
        $this->ensureDefaultDatabaseConfiguration();
        $this->applySafeRuntimeDefaultsIfNotInstalled();
    }

    public function ensureEnvironmentFileExists(): void
    {
        $envPath = $this->environmentPath();

        if (File::exists($envPath)) {
            return;
        }

        $examplePath = base_path('.env.example');

        if (File::exists($examplePath)) {
            if (! File::copy($examplePath, $envPath)) {
                throw new RuntimeException("Failed to copy .env.example to {$envPath}");
            }

            return;
        }

        if (File::put($envPath, '') === false) {
            throw new RuntimeException("Failed to create .env file at {$envPath}");
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setEnvValues(array $data): void
    {
        $this->ensureEnvironmentFileExists();

        $envPath = $this->environmentPath();
        $originalContent = File::exists($envPath) ? File::get($envPath) : '';
        $content = $originalContent;

        foreach ($data as $key => $value) {
            $key = trim((string) $key);
            $value = trim((string) ($value ?? ''));

            if ($value !== '' && preg_match('/[\s#="\'`$\\\\]/', $value)) {
                $value = '"'.addcslashes($value, '\\"$').'"';
            }

            $newLine = "{$key}={$value}";
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            if (preg_match($pattern, $content) === 1) {
                $content = preg_replace($pattern, $newLine, $content, 1) ?? $content;
                $content = $this->removeDuplicateEnvKeys($content, $key);

                continue;
            }

            $content = rtrim($content, "\n")."\n{$newLine}\n";
        }

        $content = rtrim($content, "\n")."\n";

        if ($content !== $originalContent) {
            if (File::put($envPath, $content) === false) {
                throw new RuntimeException("Failed to write .env values to {$envPath}");
            }
        }
    }

    public function ensureAppKey(): string
    {
        $existing = (string) config('app.key', '');

        if ($existing !== '') {
            return $existing;
        }

        $key = 'base64:'.base64_encode(Encrypter::generateKey((string) config('app.cipher', 'AES-256-CBC')));
        $this->setEnvValues(['APP_KEY' => $key]);

        config(['app.key' => $key]);
        app()->forgetInstance('encrypter');

        return $key;
    }

    public function applySafeRuntimeDefaultsIfNotInstalled(): void
    {
        if ($this->isInstalled()) {
            return;
        }

        config([
            'session.driver' => 'file',
            'cache.default' => 'file',
            'queue.default' => 'sync',
        ]);
    }

    public function ensureDefaultDatabaseConfiguration(): void
    {
        if ($this->isInstalled()) {
            return;
        }

        if (app()->environment('testing')) {
            TestIsolationGuard::assertDatabase();

            return;
        }

        $sqlitePath = database_path('database.sqlite');
        $databaseDir = dirname($sqlitePath);

        if (! File::exists($databaseDir)) {
            File::ensureDirectoryExists($databaseDir, 0755, true);
        }

        if (! File::exists($sqlitePath)) {
            File::put($sqlitePath, '');
        }

        $this->setEnvValues([
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => 'database/database.sqlite',
            'DB_HOST' => '',
            'DB_PORT' => '',
            'DB_USERNAME' => '',
            'DB_PASSWORD' => '',
        ]);

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $sqlitePath,
        ]);
    }

    public function isInstalled(): bool
    {
        return $this->installationState->isInstalled();
    }

    public function environmentPath(): string
    {
        if (! app()->environment('testing')) {
            return base_path('.env');
        }

        if (! config('testing.installer_mode', false)) {
            throw new RuntimeException('Environment writes are disabled outside disposable installer test mode.');
        }

        TestIsolationGuard::assertInstallerPaths();

        return (string) config('testing.environment_path');
    }

    private function removeDuplicateEnvKeys(string $content, string $key): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $seen = false;
        $filtered = [];

        foreach ($lines as $line) {
            if (preg_match('/^'.preg_quote($key, '/').'=/', $line) === 1) {
                if (! $seen) {
                    $filtered[] = $line;
                    $seen = true;
                }

                continue;
            }

            $filtered[] = $line;
        }

        return implode("\n", $filtered);
    }
}
