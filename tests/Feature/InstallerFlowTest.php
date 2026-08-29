<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckIfInstalled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstallerFlowTest extends TestCase
{
    public function test_installer_flow_uses_only_disposable_files_and_does_not_contaminate_the_configured_database(): void
    {
        $token = str_replace('-', '', (string) Str::uuid());
        $testingDirectory = storage_path('framework/testing');
        $databasePath = "{$testingDirectory}/installer_{$token}.sqlite";
        $environmentPath = "{$testingDirectory}/installer_{$token}.env";
        $markerPath = "{$testingDirectory}/installer_{$token}.json";
        $realDatabasePath = database_path('database.sqlite');
        $realEnvironmentPath = base_path('.env');
        $realDatabaseHash = File::exists($realDatabasePath) ? hash_file('sha256', $realDatabasePath) : null;
        $realEnvironmentHash = File::exists($realEnvironmentPath) ? hash_file('sha256', $realEnvironmentPath) : null;
        $originalConfig = [
            'app.installed' => config('app.installed'),
            'database.default' => config('database.default'),
            'database.connections.sqlite.database' => config('database.connections.sqlite.database'),
            'testing.installer_mode' => config('testing.installer_mode'),
            'testing.environment_path' => config('testing.environment_path'),
            'testing.installer_marker_path' => config('testing.installer_marker_path'),
        ];

        File::ensureDirectoryExists($testingDirectory);
        File::put($databasePath, '');

        try {
            config([
                'app.installed' => false,
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => $databasePath,
                'testing.installer_mode' => true,
                'testing.environment_path' => $environmentPath,
                'testing.installer_marker_path' => $markerPath,
            ]);
            DB::purge('sqlite');
            DB::reconnect('sqlite');
            CheckIfInstalled::resetCache();

            $this->api()->getJson('/api/installer/status')
                ->assertOk()
                ->assertJsonPath('data.state', 'not_installed');

            $this->api()->getJson('/api/installer/requirements')
                ->assertOk()
                ->assertJsonPath('data.ready', fn ($ready) => is_bool($ready))
                ->assertJsonPath('data.requirements.php', true)
                ->assertJsonPath('data.requirements.zip', true)
                ->assertJsonPath(
                    'data.requirements.image_processing',
                    extension_loaded('gd') || extension_loaded('imagick')
                );

            $email = "installer-{$token}@example.invalid";
            $payload = [
                'APP_NAME' => 'Marketplace QA',
                'APP_URL' => 'http://localhost:8000',
                'ADMIN_NAME' => 'Disposable Installer Admin',
                'ADMIN_EMAIL' => $email,
                'ADMIN_PASSWORD' => 'Disposable-Password-123!',
            ];

            $this->api()->postJson('/api/installer/finish', $payload)
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('migrated', true);

            $this->assertDatabaseHas('users', [
                'email' => $email,
                'role' => 'admin',
            ]);
            $this->assertStringContainsString('APP_INSTALLED=true', File::get($environmentPath));
            $this->assertTrue(File::exists($markerPath));

            $this->api()->postJson('/api/installer/finish', $payload)
                ->assertStatus(403)
                ->assertJsonPath('message', 'Installer is disabled because the application is already installed.');
        } finally {
            DB::purge('sqlite');
            config($originalConfig);
            CheckIfInstalled::resetCache();

            foreach ([$databasePath, "{$databasePath}-wal", "{$databasePath}-shm", $environmentPath, $markerPath] as $disposablePath) {
                if (File::exists($disposablePath)) {
                    File::delete($disposablePath);
                }
            }

            $this->assertSame($realDatabaseHash, File::exists($realDatabasePath) ? hash_file('sha256', $realDatabasePath) : null);
            $this->assertSame($realEnvironmentHash, File::exists($realEnvironmentPath) ? hash_file('sha256', $realEnvironmentPath) : null);
        }
    }
}
