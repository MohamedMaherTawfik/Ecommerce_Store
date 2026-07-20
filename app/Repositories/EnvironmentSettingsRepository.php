<?php

namespace App\Repositories;

use App\Models\EnvironmentSetting;
use Illuminate\Support\Collection;

class EnvironmentSettingsRepository
{
    public function getAllGrouped(): Collection
    {
        return EnvironmentSetting::all()->groupBy('group');
    }

    public function getByKey(string $key): ?EnvironmentSetting
    {
        return EnvironmentSetting::where('key', $key)->first();
    }

    public function updateOrCreate(string $group, string $key, ?string $value, string $type, bool $isEncrypted): EnvironmentSetting
    {
        return EnvironmentSetting::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $value,
                'type' => $type,
                'is_encrypted' => $isEncrypted,
            ]
        );
    }
}
