<?php

namespace App\Http\Middleware;

use App\Http\Controllers\concerns\ApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): JsonResponse|Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessAdmin()) {
            return $this->unauthorized();
        }

        if (! $user->isSuperAdmin()) {
            $middleware = $request->route()?->gatherMiddleware() ?? [];
            $hasModulePermission = collect($middleware)
                ->contains(fn ($name) => is_string($name) && str_starts_with($name, 'permission:'));

            if (! $hasModulePermission) {
                return $this->forbidden('This administrative area is restricted to the primary admin role.');
            }
        }

        return $next($request);
    }
}
