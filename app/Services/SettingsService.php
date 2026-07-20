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

            // Override Mail
            if (! empty($settings['MAIL_HOST'])) {
                Config::set('mail.mailers.smtp.host', $settings['MAIL_HOST']);
                Config::set('mail.mailers.smtp.port', $settings['MAIL_PORT'] ?? 587);
                Config::set('mail.mailers.smtp.username', $settings['MAIL_USERNAME'] ?? '');
                Config::set('mail.mailers.smtp.password', $settings['MAIL_PASSWORD'] ?? '');
                Config::set('mail.mailers.smtp.encryption', $settings['MAIL_ENCRYPTION'] ?? 'tls');
                Config::set('mail.from.address', $settings['MAIL_FROM_ADDRESS'] ?? 'hello@example.com');
                Config::set('mail.from.name', $settings['APP_NAME'] ?? 'Ai Pro');
            }

            // Override App Name
            if (! empty($settings['APP_NAME'])) {
                Config::set('app.name', $settings['APP_NAME']);
            }

            // Stripe/PayPal can be overridden similarly
            if (! empty($settings['STRIPE_PUBLIC_KEY'])) {
                Config::set('services.stripe.public_key', $settings['STRIPE_PUBLIC_KEY']);
                Config::set('services.stripe.secret_key', $settings['STRIPE_SECRET_KEY'] ?? null);
                Config::set('services.stripe.webhook_secret', $settings['STRIPE_WEBHOOK_SECRET'] ?? null);
                Config::set('services.stripe.currency', $settings['STRIPE_CURRENCY'] ?? 'usd');
                Config::set('services.stripe.enabled', filter_var($settings['STRIPE_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN));
            }

            if (! empty($settings['PAYMOB_SECRET_KEY'])) {
                Config::set('services.paymob.secret_key', $settings['PAYMOB_SECRET_KEY']);
                Config::set('services.paymob.public_key', $settings['PAYMOB_PUBLIC_KEY'] ?? null);
                Config::set('services.paymob.integration_id', $settings['PAYMOB_INTEGRATION_ID'] ?? null);
                Config::set('services.paymob.hmac_secret', $settings['PAYMOB_HMAC_SECRET'] ?? null);
                Config::set('services.paymob.currency', $settings['PAYMOB_CURRENCY'] ?? 'EGP');
                Config::set('services.paymob.base_url', $settings['PAYMOB_BASE_URL'] ?? 'https://accept.paymob.com');
                Config::set('services.paymob.enabled', filter_var($settings['PAYMOB_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN));
            }

            if (! empty($settings['MYFATOORAH_API_KEY'])) {
                Config::set('services.myfatoorah.api_key', $settings['MYFATOORAH_API_KEY']);
                Config::set('services.myfatoorah.base_url', $settings['MYFATOORAH_BASE_URL'] ?? 'https://apitest.myfatoorah.com');
                Config::set('services.myfatoorah.country_iso', $settings['MYFATOORAH_COUNTRY_ISO'] ?? 'KWT');
                Config::set('services.myfatoorah.currency', $settings['MYFATOORAH_CURRENCY'] ?? 'KWD');
                Config::set('services.myfatoorah.webhook_secret', $settings['MYFATOORAH_WEBHOOK_SECRET'] ?? null);
                Config::set('services.myfatoorah.enabled', filter_var($settings['MYFATOORAH_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN));
            }

            if (! empty($settings['PAYPAL_CLIENT_ID'])) {
                Config::set('paypal.sandbox.client_id', $settings['PAYPAL_CLIENT_ID']);
                Config::set('paypal.sandbox.client_secret', $settings['PAYPAL_SECRET']);
                Config::set('paypal.live.client_id', $settings['PAYPAL_CLIENT_ID']);
                Config::set('paypal.live.client_secret', $settings['PAYPAL_SECRET']);
            }
        } catch (\Exception $e) {
            // Silently fail during initial setups or migrations
        }
    }
}
