<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckIfInstalled;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InstallerFlowTest extends TestCase
{
    public function test_installer_flow_can_report_state_and_finish_when_uninstalled(): void
    {
        $envPath = base_path('.env');
        $markerPath = storage_path('installed.json');
        $envBackup = File::exists($envPath) ? File::get($envPath) : null;
        $markerBackup = File::exists($markerPath) ? File::get($markerPath) : null;

        try {
            File::delete($markerPath);
            config(['app.installed' => false]);
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

            $payload = [
                'APP_NAME' => 'Marketplace QA',
                'APP_URL' => 'http://localhost:8000',
                'ADMIN_NAME' => 'Marketplace Admin',
                'ADMIN_EMAIL' => 'admin.qa@example.com',
                'ADMIN_PASSWORD' => 'Abcd1234!',
            ];

            $this->api()->postJson('/api/installer/finish', $payload)
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('migrated', true);

            $this->assertDatabaseHas('users', [
                'email' => 'admin.qa@example.com',
                'role' => 'admin',
            ]);

            $this->assertStringContainsString('APP_INSTALLED=true', File::get($envPath));

            $this->api()->postJson('/api/installer/finish', $payload)
                ->assertStatus(403)
                ->assertJsonPath('message', 'Installer is disabled because the application is already installed.');
        } finally {
            if ($envBackup !== null) {
                File::put($envPath, $envBackup);
            }

            if ($markerBackup !== null) {
                File::put($markerPath, $markerBackup);
            } elseif (File::exists($markerPath)) {
                File::delete($markerPath);
            }

            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            CheckIfInstalled::resetCache();
        }
    }
}
