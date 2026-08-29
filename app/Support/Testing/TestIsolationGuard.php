<?php

namespace App\Support\Testing;

use RuntimeException;

final class TestIsolationGuard
{
    public static function assertDatabase(?array $settings = null): void
    {
        if (! app()->environment('testing')) {
            return;
        }

        if (! config('testing.database_guard', false)) {
            throw new RuntimeException('Test database guard is not enabled.');
        }

        $driver = strtolower((string) ($settings['driver'] ?? config('database.default')));
        $database = (string) ($driver === 'sqlite'
            ? ($settings['sqlite_path'] ?? $settings['database'] ?? config('database.connections.sqlite.database'))
            : ($settings['database'] ?? config("database.connections.{$driver}.database")));

        if ($driver === 'sqlite' && $database === ':memory:') {
            return;
        }

        if ($driver === 'sqlite') {
            self::assertDisposablePath($database, 'SQLite test database', '/^(?:installer|test|audit|migration)_[A-Za-z0-9_.-]+\.sqlite$/');

            return;
        }

        if (! in_array($driver, ['mysql', 'pgsql'], true) || preg_match('/(?:^|[_-])test(?:ing)?(?:$|[_-])/i', $database) !== 1) {
            throw new RuntimeException('Testing may only connect to an explicitly named test database.');
        }
    }

    public static function assertInstallerPaths(): void
    {
        if (! app()->environment('testing') || ! config('testing.installer_mode', false)) {
            return;
        }

        self::assertDisposablePath((string) config('testing.environment_path'), 'installer environment file', '/^installer_[A-Za-z0-9_.-]+\.env$/');
        self::assertDisposablePath((string) config('testing.installer_marker_path'), 'installer marker file', '/^installer_[A-Za-z0-9_.-]+\.json$/');
    }

    public static function assertDisposablePath(string $path, string $description, string $filenamePattern): void
    {
        if ($path === '') {
            throw new RuntimeException("A disposable {$description} path is required.");
        }

        $approvedDirectory = self::normalizePath(storage_path('framework/testing'));
        $candidate = self::normalizePath(self::absolutePath($path));
        $approvedPrefix = rtrim($approvedDirectory, '/').'/';

        if (! str_starts_with($candidate, $approvedPrefix) || preg_match($filenamePattern, basename($candidate)) !== 1) {
            throw new RuntimeException("The {$description} must be uniquely named under storage/framework/testing.");
        }
    }

    private static function absolutePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    private static function normalizePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $segments = [];

        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        $prefix = preg_match('/^[A-Za-z]:/', $normalized) === 1 ? '' : '/';

        return strtolower($prefix.implode('/', $segments));
    }
}
