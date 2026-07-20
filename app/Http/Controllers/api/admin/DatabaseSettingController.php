<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DatabaseSettingsRequest;
use App\Services\Database\DatabaseSettingsService;
use Illuminate\Support\Facades\Log;

class DatabaseSettingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DatabaseSettingsService $databaseSettings
    ) {
    }

    public function show()
    {
        try {
            return $this->success($this->databaseSettings->publicSettings(
                $this->databaseSettings->currentSettings()
            ), 'Database settings retrieved successfully');
        } catch (\Throwable $e) {
            Log::error($e);

            return $this->error('Failed to load database settings.');
        }
    }

    public function test(DatabaseSettingsRequest $request)
    {
        if (! config('app.allow_admin_env_editor')) {
            return $this->forbidden('Database settings testing is disabled.');
        }

        try {
            $result = $this->databaseSettings->testConnection($request->validated());

            return $this->success($result, 'Database connection test passed.');
        } catch (\Throwable $e) {
            return $this->validationError([
                'connection' => [$e->getMessage()],
            ], 'Database connection test failed.');
        }
    }

    public function update(DatabaseSettingsRequest $request)
    {
        if (! config('app.allow_admin_env_editor')) {
            return $this->forbidden('Database settings editing is disabled.');
        }

        try {
            $result = $this->databaseSettings->applySettings($request->validated());

            return $this->success($result, 'Database settings updated successfully.');
        } catch (\Throwable $e) {
            Log::error('Database settings update failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->validationError([
                'connection' => [$e->getMessage()],
            ], 'Unable to save database settings.');
        }
    }
}
