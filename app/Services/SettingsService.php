<?php

namespace App\Services;

use App\Http\Middleware\CheckIfInstalled;
use App\Models\SiteSetting;
use App\Support\TaggedCache;
use Illuminate\Support\Facades\Config;

class SettingsService
{
    /**
     * Get all settings grouped or flat.
     */
    public static function all()
    {
        return TaggedCache::tags(['settings'])->remember('all_settings_flat', 3600, function () {
            $settings = SiteSetting::all()->pluck('value', 'key')->toArray();

            // Format image URLs
            foreach ($settings as $key => $val) {
                if (is_string($val) && (str_starts_with($val, 'storage/') || str_starts_with($val, '/storage/'))) {
                    $settings[$key] = asset(ltrim($val, '/'));
                }
            }

            return $settings;
        });
    }

    /**
     * Update multiple settings.
     */
    public static function updateMany(array $data)
    {
        foreach ($data as $key => $value) {
            // Handle file uploads (if value is an UploadedFile, it's processed in the controller usually,
            // but if it's already a string path or we are just updating text)
            if ($value !== null) {
                SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }

        TaggedCache::tags(['settings'])->flush();
    }

    /**
     * Override config from settings.
     */
    public static function overrideConfig()
    {
        try {
            // Check if installed first
            if (! CheckIfInstalled::isInstalled()) {
                return;
            }

            $settings = self::all();

            // Override App Name
            if (! empty($settings['APP_NAME'])) {
                Config::set('app.name', $settings['APP_NAME']);
            }
        } catch (\Exception $e) {
            // Silently fail during initial setups or migrations
        }
    }
}
