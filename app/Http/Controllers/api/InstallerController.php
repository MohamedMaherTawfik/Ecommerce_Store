<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckIfInstalled;
use App\Models\User;
use App\Services\Database\DatabaseSettingsService;
use App\Services\Installer\EnvironmentSetupService;
use App\Services\Installer\InstallationStateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InstallerController extends Controller
{
    private ?string $bootstrapRequestId = null;

    public function __construct(
        private readonly EnvironmentSetupService $environmentSetup,
        private readonly InstallationStateService $installationState,
        private readonly DatabaseSettingsService $databaseSettings
    ) {
        try {
            $this->environmentSetup->ensureBootstrapState();
        } catch (\Throwable $e) {
            $this->bootstrapRequestId = (string) Str::uuid();

            Log::error('Installer bootstrap failed in controller constructor', [
                'request_id' => $this->bootstrapRequestId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * GET /api/installer/status
     */
    public function status()
    {
        if ($response = $this->bootstrapFailureResponse()) {
            return $response;
        }

        $state = $this->installationState->state();

        Log::info('Installer status requested', [
            ...$state,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                ...$state,
            ],
        ]);
    }

    /**
     * GET /api/installer/requirements
     */
    public function requirements()
    {
        if ($response = $this->bootstrapFailureResponse()) {
            return $response;
        }

        if ($this->installationState->isInstalled()) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Application is already installed.',
            ], 403);
        }

        $requirements = [
            'php' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'pdo' => extension_loaded('pdo'),
            'pdo_sqlite' => extension_loaded('pdo_sqlite'),
            'mbstring' => extension_loaded('mbstring'),
            'openssl' => extension_loaded('openssl'),
            'tokenizer' => extension_loaded('tokenizer'),
            'xml' => extension_loaded('xml'),
            'ctype' => extension_loaded('ctype'),
            'json' => extension_loaded('json'),
            'curl' => extension_loaded('curl'),
            'fileinfo' => extension_loaded('fileinfo'),
            'zip' => extension_loaded('zip'),
            'image_processing' => extension_loaded('gd') || extension_loaded('imagick'),
        ];

        $permissions = [
            'storage' => is_writable(storage_path()),
            'storage/logs' => is_writable(storage_path('logs')),
            'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
            'database' => is_writable(database_path()),
        ];

        $sqlitePath = $this->databaseSettings->defaultSqlitePath();
        $permissions['database/database.sqlite'] = file_exists($sqlitePath)
            ? is_writable($sqlitePath)
            : is_writable(dirname($sqlitePath));

        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $permissions['.env'] = is_writable($envPath);
        } else {
            $permissions['.env (create)'] = is_writable(base_path());
        }

        $allRequirementsMet = ! in_array(false, $requirements, true) && ! in_array(false, $permissions, true);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'requirements' => $requirements,
                'permissions' => $permissions,
                'ready' => $allRequirementsMet,
                'php_version' => PHP_VERSION,
            ],
        ]);
    }

    /**
     * POST /api/installer/finish
     */
    public function finish(Request $request)
    {
        if ($response = $this->bootstrapFailureResponse()) {
            return $response;
        }

        if ($this->installationState->isInstalled()) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Installer is disabled because the application is already installed.',
            ], 403);
        }

        $envPath = base_path('.env');
        $envBackup = File::exists($envPath) ? File::get($envPath) : null;
        $markerPath = $this->installationState->markerPath();
        $markerBackup = File::exists($markerPath) ? File::get($markerPath) : null;
        $sqlitePath = $this->databaseSettings->defaultSqlitePath();
        $sqliteExistedBefore = File::exists($sqlitePath);

        Log::info('Installer finish requested', [
            'app_name' => $request->input('APP_NAME'),
            'app_url' => $request->input('APP_URL'),
            'admin_email' => $request->input('ADMIN_EMAIL'),
            ...$this->installationState->state(),
        ]);

        $validator = Validator::make($request->all(), [
            'APP_NAME' => 'required|string|max:255',
            'APP_URL' => 'required|url|max:500',
            'ADMIN_NAME' => 'required|string|max:255',
            'ADMIN_EMAIL' => 'required|email|max:255',
            'ADMIN_PASSWORD' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Validation failed',
                'error' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $completionStep = 'start';

        try {
            $completionStep = 'prepare_sqlite_database';
            $sqliteSettings = $this->databaseSettings->configureDefaultSqlite();
            $completionStep = 'sqlite_database_prepared';

            $completionStep = 'run_migrations';
            $migrationOutput = $this->databaseSettings->runMigrations($sqliteSettings);
            $completionStep = 'migrations_completed';

            $this->assertInstallationCanBeFinalized();
            $completionStep = 'preflight_completed';

            Log::info('Installer finish step', [
                'step' => $completionStep,
            ]);

            $completionStep = 'persist_environment_base';
            $this->environmentSetup->setEnvValues([
                'APP_NAME' => $request->APP_NAME,
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_URL' => $request->APP_URL,
                'FRONTEND_URL' => $request->APP_URL,
            ]);
            $this->environmentSetup->ensureAppKey();
            $this->clearAndRebuildConfig();
            $completionStep = 'environment_saved_and_config_rebuilt';

            Log::info('Installer finish step', [
                'step' => $completionStep,
            ]);

            $completionStep = 'upsert_admin';
            $admin = User::firstOrNew(['email' => $request->ADMIN_EMAIL]);
            $admin->name = $request->ADMIN_NAME;
            $admin->password = Hash::make($request->ADMIN_PASSWORD);
            $admin->role = 'admin';
            if (empty($admin->email_verified_at)) {
                $admin->email_verified_at = now();
            }
            $admin->save();
            $completionStep = 'admin_saved';

            Log::info('Installer finish step', [
                'step' => $completionStep,
            ]);

            $completionStep = 'persist_installed_flag';
            $this->environmentSetup->setEnvValues([
                'APP_INSTALLED' => 'true',
            ]);
            config(['app.installed' => true]);
            CheckIfInstalled::resetCache();
            $this->clearAndRebuildConfig();
            $this->clearApplicationCaches();
            $completionStep = 'write_installed_marker';
            $this->writeInstalledMarker($request->APP_NAME, $request->APP_URL);
            CheckIfInstalled::resetCache();
            $completionStep = 'installed_marker_written';

            Log::info('Installer finish step', [
                'step' => $completionStep,
                'marker_path' => $this->installationState->markerPath(),
                'marker_exists' => $this->installationState->markerExists(),
            ]);

            $completionStep = 'finalize_completed';

            Log::info('Installer finish completed successfully', [
                ...$this->installationState->state(),
            ]);

            return response()->json([
                'success' => true,
                'status' => 'success',
                'database_connected' => true,
                'migrated' => true,
                'migration_output' => $migrationOutput,
                'message' => 'Installation completed successfully',
            ]);
        } catch (\Throwable $e) {
            $this->rollbackInstallationState($envBackup, $markerBackup, $sqlitePath, $sqliteExistedBefore);
            $requestId = (string) Str::uuid();

            Log::error('Installation Error: '.$e->getMessage(), [
                'request_id' => $requestId,
                'completion_step' => $completionStep,
                'marker_path' => $this->installationState->markerPath(),
                'marker_exists' => $this->installationState->markerExists(),
                'storage_path' => storage_path(),
                'storage_is_dir' => is_dir(storage_path()),
                'storage_is_writable' => is_writable(storage_path()),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Installation failed. Review the server log using the request ID.',
                'request_id' => $requestId,
            ], 500);
        }
    }

    private function assertInstallationCanBeFinalized(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Database connection is not available. The SQLite database could not be prepared.');
        }

        if (! Schema::hasTable('migrations')) {
            throw new \RuntimeException('Migrations table was not found. Please run migrations successfully before finishing installation.');
        }

        $migrationCount = DB::connection()
            ->table('migrations')
            ->count();

        if ($migrationCount < 1) {
            throw new \RuntimeException('No completed migrations were found. Please run migrations successfully before finishing installation.');
        }
    }

    /**
     * POST /api/installer/process (backward compatibility)
     */
    public function process(Request $request)
    {
        if ($response = $this->bootstrapFailureResponse()) {
            return $response;
        }

        return $this->finish($request);
    }

    private function bootstrapFailureResponse()
    {
        if ($this->bootstrapRequestId === null) {
            return null;
        }

        return response()->json([
            'success' => false,
            'status' => 'error',
            'message' => 'Installer bootstrap failed. Unable to prepare environment file.',
            'request_id' => $this->bootstrapRequestId,
        ], 500);
    }

    private function clearAndRebuildConfig(): void
    {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:cache');
    }

    private function clearApplicationCaches(): void
    {
        Artisan::call('optimize:clear');
    }

    private function writeInstalledMarker(string $appName, string $appUrl): void
    {
        $storageDir = storage_path();
        $markerPath = $this->installationState->markerPath();

        Log::info('Installer marker write started', [
            'storage_path' => $storageDir,
            'storage_exists' => File::exists($storageDir),
            'storage_is_dir' => is_dir($storageDir),
            'storage_is_writable' => is_writable($storageDir),
            'marker_path' => $markerPath,
        ]);

        if (! File::exists($storageDir)) {
            File::ensureDirectoryExists($storageDir, 0755, true);
        }

        if (! is_dir($storageDir) || ! is_writable($storageDir)) {
            throw new \RuntimeException("Storage directory is not writable: {$storageDir}");
        }

        $payload = json_encode([
            'installed' => true,
            'installed_at' => now()->toDateTimeString(),
            'app_name' => $appName,
            'environment' => (string) config('app.env', 'production'),
            'app_url' => $appUrl,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            throw new \RuntimeException('Failed to prepare installation marker payload.');
        }

        $bytes = @file_put_contents($markerPath, $payload.PHP_EOL, LOCK_EX);

        clearstatcache(true, $markerPath);

        Log::info('Installer marker write attempted', [
            'path' => $markerPath,
            'bytes_written' => $bytes,
            'exists_after_write' => file_exists($markerPath),
            'is_writable_storage' => is_writable(storage_path()),
        ]);

        if ($bytes === false || ! file_exists($markerPath)) {
            throw new \RuntimeException("Failed to write installation marker at {$markerPath}");
        }
    }

    private function rollbackInstalledFlag(): void
    {
        try {
            $this->environmentSetup->setEnvValues([
                'APP_INSTALLED' => 'false',
            ]);

            config(['app.installed' => false]);
            CheckIfInstalled::resetCache();
            $this->clearAndRebuildConfig();

            Log::warning('Installer rollback executed for APP_INSTALLED flag because marker file is missing.');
        } catch (\Throwable $rollbackError) {
            Log::error('Installer rollback failed for APP_INSTALLED flag.', [
                'error' => $rollbackError->getMessage(),
            ]);
        }
    }

    private function rollbackInstallationState(?string $envBackup, ?string $markerBackup, string $sqlitePath, bool $sqliteExistedBefore): void
    {
        try {
            if ($envBackup !== null) {
                File::put(base_path('.env'), $envBackup);
            }

            if ($markerBackup !== null) {
                File::put($this->installationState->markerPath(), $markerBackup);
            } elseif (File::exists($this->installationState->markerPath())) {
                File::delete($this->installationState->markerPath());
            }

            if (! $sqliteExistedBefore && File::exists($sqlitePath)) {
                File::delete($sqlitePath);
            }

            if ($envBackup === null) {
                $this->rollbackInstalledFlag();
            }
            $this->clearAndRebuildConfig();
            CheckIfInstalled::resetCache();
        } catch (\Throwable $rollbackError) {
            Log::error('Installer full rollback failed.', [
                'error' => $rollbackError->getMessage(),
            ]);
        }
    }
}
