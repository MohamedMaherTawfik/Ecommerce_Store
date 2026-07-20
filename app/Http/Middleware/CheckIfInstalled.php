<?php

namespace App\Http\Middleware;

use App\Services\Installer\EnvironmentSetupService;
use App\Services\Installer\InstallationStateService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckIfInstalled
{
    /**
     * Routes that are always allowed during install mode.
     * Uses request->is() pattern matching.
     */
    private const INSTALL_WHITELIST = [
        'api/installer*',
        'install*',
        'installer*',
    ];

    /**
     * Cache the install check per-request to avoid repeated checks.
     */
    private static ?bool $isInstalled = null;

    public function __construct(
        private readonly EnvironmentSetupService $environmentSetup,
        private readonly InstallationStateService $installationState
    )
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $this->environmentSetup->ensureBootstrapState();
        } catch (\Throwable $e) {
            Log::error('Installer bootstrap failed', [
                'path' => $request->path(),
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Application bootstrap failed. Please check .env file permissions.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            abort(500, 'Application bootstrap failed. Please check .env file permissions.');
        }

        $this->installationState->logState('middleware');
        $installed = self::isInstalled();

        if ($installed) {
            if ($request->is('api/installer*') && !$request->is('api/installer/status')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Installer is disabled because the application is already installed.',
                ], 403);
            }

            if ($this->isInstallerPage($request)) {
                return redirect($this->installedHomePath());
            }

            return $next($request);
        }

        foreach (self::INSTALL_WHITELIST as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Application is not installed. Please run the installer.',
                'action' => 'install',
            ], 503);
        }

        return redirect('/install');
    }

    /**
     * Check if the application is installed (cached per request lifecycle).
     */
    public static function isInstalled(): bool
    {
        if (self::$isInstalled === null) {
            self::$isInstalled = app(InstallationStateService::class)->isInstalled();
        }

        return self::$isInstalled;
    }

    /**
     * Reset the cached install status (useful after installation completes).
     */
    public static function resetCache(): void
    {
        self::$isInstalled = null;
        app(InstallationStateService::class)->resetRuntimeCache();
    }

    private function isInstallerPage(Request $request): bool
    {
        return $request->is('install') || $request->is('install/*')
            || $request->is('installer') || $request->is('installer/*');
    }

    private function installedHomePath(): string
    {
        $locale = trim((string) config('app.locale', 'en'));

        return '/' . ($locale !== '' ? $locale : 'en') . '/';
    }
}
