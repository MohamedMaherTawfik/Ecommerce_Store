<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\EnvironmentSettings\EnvironmentSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApplicationSettingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly EnvironmentSettingsService $environmentSettings
    ) {}

    public function show()
    {
        try {
            return $this->success(
                $this->environmentSettings->getPagePayload(),
                'Environment settings loaded successfully.'
            );
        } catch (\Throwable $exception) {
            Log::error('Failed to load environment settings', [
                'exception' => $exception::class,
                'request_id' => (string) Str::uuid(),
            ]);

            return $this->error('Failed to load environment settings.');
        }
    }

    public function update(Request $request)
    {
        if (! config('app.allow_admin_env_editor')) {
            return $this->forbidden('Environment editing is disabled.');
        }

        try {
            // Validate that we get an array of values
            $validated = $request->validate([
                '*' => 'nullable',
            ]);

            return $this->success(
                $this->environmentSettings->update($validated),
                'Environment settings updated successfully.'
            );
        } catch (\Throwable $exception) {
            Log::error('Failed to update environment settings', [
                'exception' => $exception::class,
                'request_id' => (string) Str::uuid(),
            ]);

            return $this->error('Failed to update environment settings.');
        }
    }

    public function sendTestMail(Request $request)
    {
        try {
            $validated = $request->validate([
                'recipient_email' => 'required|email',
            ]);

            return $this->success(
                $this->environmentSettings->sendTestMail($validated['recipient_email']),
                'Test email sent successfully.'
            );
        } catch (\Throwable $exception) {
            Log::error('Failed to send settings test mail', [
                'exception' => $exception::class,
                'request_id' => (string) Str::uuid(),
            ]);

            return $this->validationError([
                'recipient_email' => ['Unable to send test email with the current configuration.'],
            ], 'Unable to send test email.');
        }
    }
}
