<?php

namespace App\Helpers;

use App\Services\Installer\EnvironmentSetupService;

class EnvHelper
{
    /**
     * Set multiple environment variables in the .env file.
     *
     * @param  array<string, mixed>  $data
     */
    public static function setEnv(array $data): void
    {
        app(EnvironmentSetupService::class)->setEnvValues($data);
    }
}
