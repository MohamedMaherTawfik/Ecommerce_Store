<?php

namespace App\Services\Database;

use App\Models\DatabaseSetting;
use App\Services\Installer\EnvironmentSetupService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class DatabaseSettingsService
{
    public function __construct(
        private readonly EnvironmentSetupService $environmentSetup
    ) {}

    public function defaultSqlitePath(): string
    {
        return database_path('database.sqlite');
    }

    public function currentSettings(): array
    {
        $driver = (string) config('database.default', 'sqlite');
        $connection = (array) config("database.connections.{$driver}", []);
        $envSettings = $this->normalizeSettings([
            'driver' => $driver,
            'host' => $connection['host'] ?? null,
            'port' => $connection['port'] ?? null,
            'database' => $connection['database'] ?? null,
            'username' => $connection['username'] ?? null,
            'password' => $connection['password'] ?? null,
            'sqlite_path' => $driver === 'sqlite' ? ($connection['database'] ?? $this->defaultSqlitePath()) : null,
        ]);

        try {
            if (! Schema::hasTable('database_settings')) {
                return $envSettings;
            }

            $record = DatabaseSetting::query()
                ->where('is_active', true)
                ->latest('id')
                ->first();

            if ($record === null) {
                return $envSettings;
            }

            return $this->normalizeSettings([
                'driver' => $record->driver,
                'host' => $record->host,
                'port' => $record->port,
                'database' => $record->database,
                'username' => $record->username,
                'password' => $record->password,
                'sqlite_path' => $record->sqlite_path,
            ]);
        } catch (\Throwable) {
            return $envSettings;
        }
    }

    public function normalizeSettings(array $settings): array
    {
        $driver = strtolower((string) ($settings['driver'] ?? 'sqlite'));

        return [
            'driver' => $driver,
            'host' => array_key_exists('host', $settings) && $settings['host'] !== null && $settings['host'] !== '' ? (string) $settings['host'] : null,
            'port' => array_key_exists('port', $settings) && $settings['port'] !== null && $settings['port'] !== '' ? (string) $settings['port'] : null,
            'database' => array_key_exists('database', $settings) && $settings['database'] !== null && $settings['database'] !== '' ? (string) $settings['database'] : null,
            'username' => array_key_exists('username', $settings) && $settings['username'] !== null && $settings['username'] !== '' ? (string) $settings['username'] : null,
            'password' => array_key_exists('password', $settings) && $settings['password'] !== null ? (string) $settings['password'] : null,
            'sqlite_path' => $driver === 'sqlite'
                ? $this->normalizeSqlitePath((string) ($settings['sqlite_path'] ?? $settings['database'] ?? $this->defaultSqlitePath()))
                : null,
        ];
    }

    public function ensureSqliteDatabaseExists(?string $path = null): string
    {
        $sqlitePath = $this->normalizeSqlitePath($path ?: $this->defaultSqlitePath());
        $directory = dirname($sqlitePath);

        if (! File::exists($directory)) {
            File::ensureDirectoryExists($directory, 0755, true);
        }

        if (! File::exists($sqlitePath)) {
            File::put($sqlitePath, '');
        }

        if (! File::isWritable($sqlitePath)) {
            throw new \RuntimeException("SQLite database file is not writable: {$sqlitePath}");
        }

        return $sqlitePath;
    }

    public function testConnection(array $settings): array
    {
        $settings = $this->preservePasswordWhenBlank($settings);
        $normalized = $this->normalizeSettings($settings);
        $connectionName = 'database_settings_test';
        $config = $this->makeConnectionConfig($normalized);

        config([
            "database.connections.{$connectionName}" => $config,
        ]);

        try {
            DB::purge($connectionName);
            $connection = DB::connection($connectionName);
            $connection->getPdo();

            $version = match ($normalized['driver']) {
                'sqlite' => (string) ($connection->selectOne('select sqlite_version() as version')->version ?? 'sqlite'),
                'pgsql' => (string) ($connection->selectOne('select version() as version')->version ?? 'pgsql'),
                default => (string) $connection->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION),
            };

            return [
                'driver' => $normalized['driver'],
                'database' => $normalized['driver'] === 'sqlite' ? $normalized['sqlite_path'] : $normalized['database'],
                'version' => $version,
                'message' => 'Database connection successful.',
            ];
        } catch (\Throwable $e) {
            throw new \RuntimeException($this->formatConnectionError($e, $normalized), previous: $e);
        } finally {
            DB::purge($connectionName);
        }
    }

    public function applySettings(array $settings): array
    {
        $settings = $this->preservePasswordWhenBlank($settings);
        $normalized = $this->normalizeSettings($settings);
        $tested = $this->testConnection($normalized);
        $targetConnection = 'database_settings_target';

        config([
            "database.connections.{$targetConnection}" => $this->makeConnectionConfig($normalized),
        ]);

        try {
            $this->ensureSettingsTableOnConnection($targetConnection);
            $this->persistSnapshot($targetConnection, $normalized);
        } finally {
            DB::purge($targetConnection);
        }

        $this->environmentSetup->setEnvValues($this->makeEnvPayload($normalized));
        $this->clearRuntimeCaches();
        $this->applyRuntimeConfig($normalized);

        return $tested + [
            'settings' => $this->publicSettings($normalized),
        ];
    }

    public function configureDefaultSqlite(): array
    {
        $settings = $this->normalizeSettings([
            'driver' => 'sqlite',
            'sqlite_path' => $this->defaultSqlitePath(),
        ]);

        $this->ensureSqliteDatabaseExists($settings['sqlite_path']);
        $this->environmentSetup->setEnvValues([
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => 'database/database.sqlite',
            'DB_HOST' => '',
            'DB_PORT' => '',
            'DB_USERNAME' => '',
            'DB_PASSWORD' => '',
        ]);
        $this->applyRuntimeConfig($settings);

        return $settings;
    }

    public function runMigrations(?array $settings = null): string
    {
        $normalized = $settings !== null ? $this->normalizeSettings($settings) : $this->currentSettings();

        $this->applyRuntimeConfig($normalized);

        Artisan::call('migrate', [
            '--force' => true,
        ]);

        return Artisan::output();
    }

    public function applyRuntimeConfig(array $settings): void
    {
        $normalized = $this->normalizeSettings($settings);
        $connectionConfig = $this->makeConnectionConfig($normalized);

        config([
            'database.default' => $normalized['driver'],
            "database.connections.{$normalized['driver']}" => $connectionConfig,
        ]);

        DB::purge($normalized['driver']);
        DB::disconnect($normalized['driver']);
        DB::reconnect($normalized['driver']);
    }

    public function clearRuntimeCaches(): void
    {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
    }

    public function publicSettings(array $settings): array
    {
        $normalized = $this->normalizeSettings($settings);

        return [
            'driver' => $normalized['driver'],
            'host' => $normalized['host'],
            'port' => $normalized['port'],
            'database' => $normalized['database'],
            'username' => $normalized['username'],
            'password' => ['configured' => filled($normalized['password'])],
            'sqlite_path' => $normalized['sqlite_path'],
        ];
    }

    private function makeConnectionConfig(array $settings): array
    {
        return match ($settings['driver']) {
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => $this->ensureSqliteDatabaseExists($settings['sqlite_path']),
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => $settings['host'],
                'port' => (int) $settings['port'],
                'database' => $settings['database'],
                'username' => $settings['username'],
                'password' => $settings['password'] ?? '',
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ],
            default => [
                'driver' => 'mysql',
                'host' => $settings['host'],
                'port' => (int) $settings['port'],
                'database' => $settings['database'],
                'username' => $settings['username'],
                'password' => $settings['password'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ],
        };
    }

    private function preservePasswordWhenBlank(array $settings): array
    {
        if (filled($settings['password'] ?? null)) {
            return $settings;
        }

        $current = $this->currentSettings();
        $settings['password'] = $current['password'] ?? null;

        return $settings;
    }

    private function makeEnvPayload(array $settings): array
    {
        if ($settings['driver'] === 'sqlite') {
            $database = (string) ($settings['sqlite_path'] ?? $this->defaultSqlitePath());
            $database = str_replace('\\', '/', $database);
            $database = preg_replace('#^.*?/database/database\.sqlite$#', 'database/database.sqlite', $database) ?? $database;

            return [
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => $database,
                'DB_HOST' => '',
                'DB_PORT' => '',
                'DB_USERNAME' => '',
                'DB_PASSWORD' => '',
            ];
        }

        return [
            'DB_CONNECTION' => $settings['driver'],
            'DB_HOST' => $settings['host'],
            'DB_PORT' => $settings['port'],
            'DB_DATABASE' => $settings['database'],
            'DB_USERNAME' => $settings['username'],
            'DB_PASSWORD' => $settings['password'] ?? '',
        ];
    }

    private function normalizeSqlitePath(string $path): string
    {
        $trimmed = trim($path);

        if ($trimmed === '') {
            $trimmed = $this->defaultSqlitePath();
        }

        if (str_starts_with($trimmed, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $trimmed) === 1) {
            return $trimmed;
        }

        return base_path($trimmed);
    }

    private function ensureSettingsTableOnConnection(string $connectionName): void
    {
        if (Schema::connection($connectionName)->hasTable('database_settings')) {
            return;
        }

        Schema::connection($connectionName)->create('database_settings', function (Blueprint $table) {
            $table->id();
            $table->string('driver', 20);
            $table->string('host')->nullable();
            $table->string('port', 10)->nullable();
            $table->string('database')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('sqlite_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
        });
    }

    private function persistSnapshot(string $connectionName, array $settings): void
    {
        DB::connection($connectionName)
            ->table('database_settings')
            ->updateOrInsert(
                ['id' => 1],
                [
                    'driver' => $settings['driver'],
                    'host' => $settings['host'],
                    'port' => $settings['port'],
                    'database' => $settings['database'],
                    'username' => $settings['username'],
                    'password' => $settings['password'] !== null && $settings['password'] !== ''
                        ? encrypt($settings['password'])
                        : null,
                    'sqlite_path' => $settings['sqlite_path'],
                    'is_active' => true,
                    'last_tested_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
    }

    private function formatConnectionError(\Throwable $e, array $settings): string
    {
        $message = $e->getMessage();

        if ($settings['driver'] === 'sqlite') {
            return "SQLite connection failed: {$message}";
        }

        $target = "{$settings['host']}:{$settings['port']}";

        if (stripos($message, 'Access denied') !== false || stripos($message, 'authentication failed') !== false) {
            return "Authentication failed for {$target}. Please verify the username and password.";
        }

        if (stripos($message, 'Unknown database') !== false || stripos($message, 'does not exist') !== false) {
            return "Database '{$settings['database']}' is not reachable on {$target}.";
        }

        if (stripos($message, 'Connection refused') !== false || stripos($message, 'could not connect') !== false) {
            return "Could not connect to {$target}. Please verify the host and port.";
        }

        return $message;
    }
}
