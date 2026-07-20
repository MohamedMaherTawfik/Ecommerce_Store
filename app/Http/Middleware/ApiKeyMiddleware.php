<?php

namespace App\Http\Middleware;

use App\Http\Controllers\concerns\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    use ApiResponse;

    private const API_KEY_WHITELIST = [
        'api/installer*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        foreach (self::API_KEY_WHITELIST as $pattern) {
            if ($request->is($pattern)) {
                Log::info('API key middleware skipped for whitelisted route', [
                    'path' => $request->path(),
                    'pattern' => $pattern,
                ]);

                return $next($request);
            }
        }

        if ($request->user() || auth()->check()) {
            return $next($request);
        }

        $expectedKey = (string) config('services.api.key', env('API_KEY'));
        $providedKey = (string) $request->header('X-API-KEY', '');

        if ($expectedKey === '' && ! app()->isProduction() && ! app()->environment('testing')) {
            return $next($request);
        }

        if ($expectedKey !== '' && hash_equals($expectedKey, $providedKey)) {
            return $next($request);
        }

        if ($providedKey !== $expectedKey) {
            Log::warning('API key middleware rejected request', [
                'path' => $request->path(),
                'has_provided_api_key' => $providedKey !== '',
            ]);

            return $this->unauthorized('Must provide valid api key');
        }

        return $next($request);
    }
}
