<?php

namespace App\Http\Middleware;

use App\Http\Controllers\concerns\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class EnsureModulePermission
{
    use ApiResponse;

    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();
        $permissions = array_filter(explode('|', $permission));

        if (! $user || ! collect($permissions)->contains(
            fn (string $name) => $user->hasPermission($name)
        )) {
            return $this->forbidden('You do not have permission to access this module.');
        }

        return $next($request);
    }
}
