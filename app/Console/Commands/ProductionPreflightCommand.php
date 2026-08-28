<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProductionPreflightCommand extends Command
{
    protected $signature = 'app:production-preflight';

    protected $description = 'Fail when production-critical configuration is missing or unsafe';

    public function handle(): int
    {
        $checks = [
            'APP_ENV is production' => app()->environment('production'),
            'APP_KEY is configured' => filled(config('app.key')),
            'APP_INSTALLED is true' => (bool) config('app.installed'),
            'APP_DEBUG is false' => ! config('app.debug'),
            'APP_URL uses HTTPS' => $this->isHttps(config('app.url')),
            'FRONTEND_URL uses HTTPS' => $this->isHttps(config('app.frontend_url')),
            'Admin environment editor is disabled' => ! config('app.allow_admin_env_editor'),
            'Queue is asynchronous' => ! in_array(config('queue.default'), ['sync', 'null'], true),
            'Cache is persistent' => ! in_array(config('cache.default'), ['array', 'null'], true),
            'Session storage is persistent' => ! in_array(config('session.driver'), ['array', 'null'], true),
            'Session cookies are secure' => config('session.secure') === true,
            'Auth token cookie is secure' => config('auth_cookie.secure') === true,
            'Auth token cookie uses SameSite strict' => config('auth_cookie.same_site') === 'strict',
            'Mail uses a delivery transport' => ! in_array(config('mail.default'), ['array', 'log'], true),
            'CORS origins are explicit HTTPS origins' => $this->originsAreSafe(config('cors.allowed_origins', [])),
            'Sanctum stateful domains are explicit production hosts' => $this->statefulDomainsAreSafe(config('sanctum.stateful', [])),
        ];

        if (config('payment.gateways.paymob.enabled')) {
            $checks += [
                'Paymob public key is configured' => filled(config('payment.gateways.paymob.public_key')),
                'Paymob secret key is configured' => filled(config('payment.gateways.paymob.secret_key')),
                'Paymob HMAC secret is configured' => filled(config('payment.gateways.paymob.hmac_secret')),
                'Paymob card integration is configured' => filled(config('payment.gateways.paymob.integration_ids.card')),
                'Paymob callback uses HTTPS' => $this->isHttps(config('payment.urls.callback')),
                'Paymob webhook uses HTTPS' => $this->isHttps(config('payment.urls.webhook')),
            ];
        }

        $failed = array_filter($checks, fn (bool $passed): bool => ! $passed);

        $this->table(
            ['Check', 'Result'],
            collect($checks)->map(fn (bool $passed, string $name): array => [
                $name,
                $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>',
            ])->values()->all()
        );

        if ($failed !== []) {
            $this->error('Production preflight failed. Correct every failed check before deployment.');

            return self::FAILURE;
        }

        $this->info('Production preflight passed.');

        return self::SUCCESS;
    }

    private function isHttps(mixed $url): bool
    {
        return is_string($url) && parse_url($url, PHP_URL_SCHEME) === 'https';
    }

    private function originsAreSafe(array $origins): bool
    {
        return $origins !== [] && collect($origins)->every(
            fn (mixed $origin): bool => is_string($origin)
                && $origin !== '*'
                && $this->isHttps($origin)
                && ! preg_match('/(^|\.)localhost$|^127\.|^\[?::1\]?$/i', (string) parse_url($origin, PHP_URL_HOST))
        );
    }

    private function statefulDomainsAreSafe(array $domains): bool
    {
        return $domains !== [] && collect($domains)->every(function (mixed $domain): bool {
            if (! is_string($domain) || str_contains($domain, '*')) {
                return false;
            }

            $host = strtolower(explode(':', trim($domain))[0]);

            return $host !== ''
                && $host !== 'localhost'
                && $host !== '127.0.0.1'
                && $host !== '::1';
        });
    }
}
