<?php

namespace App\Services\Installer;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class InstallationStateService
{
    private static bool $stateLogged = false;

    private ?array $runtimeState = null;

    public function isInstalled(): bool
    {
        $state = $this->state();

        return $state['installed'];
    }

    /**
     * @return array{
     *     installed: bool,
     *     env_installed: bool,
     *     marker_installed: bool,
     *     marker_path: string,
     *     state: string
     * }
     */
    public function state(): array
    {
        if ($this->runtimeState !== null) {
            return $this->runtimeState;
        }

        $envInstalled = $this->envInstalled();
        $markerInstalled = $this->markerExists();
        $databaseReady = $this->databaseReady();
        $installed = $envInstalled && $markerInstalled && $databaseReady;

        $state = 'not_installed';
        if ($installed) {
            $state = 'installed';
        } elseif ($envInstalled) {
            $state = 'installed_env_only';
        } elseif ($markerInstalled) {
            $state = 'installed_marker_only';
        }

        return $this->runtimeState = [
            'installed' => $installed,
            'env_installed' => $envInstalled,
            'marker_installed' => $markerInstalled,
            'database_ready' => $databaseReady,
            'marker_path' => $this->markerPath(),
            'state' => $state,
        ];
    }

    public function resetRuntimeCache(): void
    {
        $this->runtimeState = null;
    }

    public function markerPath(): string
    {
        return storage_path('installed.json');
    }

    public function markerExists(): bool
    {
        $path = $this->markerPath();

        if (! File::exists($path)) {
            return false;
        }

        try {
            $contents = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

            return filter_var($contents['installed'] ?? false, FILTER_VALIDATE_BOOLEAN);
        } catch (\Throwable) {
            return false;
        }
    }

    public function logState(string $context): void
    {
        if (self::$stateLogged) {
            return;
        }

        self::$stateLogged = true;
        $state = $this->state();

        if (! $this->shouldLogState($state['state'], $context)) {
            return;
        }

        $level = in_array($state['state'], ['installed_env_only', 'installed_marker_only'], true) ? 'warning' : 'info';

        Log::log($level, 'Installation state evaluated', [
            'context' => $context,
            ...$state,
        ]);
    }

    private function envInstalled(): bool
    {
        return filter_var(config('app.installed'), FILTER_VALIDATE_BOOLEAN);
    }

    private function databaseReady(): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        try {
            if (! Schema::hasTable('migrations')) {
                return false;
            }

            return DB::table('migrations')->count() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function shouldLogState(string $state, string $context): bool
    {
        $store = config('cache.stores.file') !== null ? 'file' : 'array';
        $key = 'installer_state_log:'.sha1($context.'|'.$state);

        try {
            return Cache::store($store)->add($key, now()->toIso8601String(), now()->addMinutes(15));
        } catch (\Throwable) {
            return true;
        }
    }
}
