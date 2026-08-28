<?php

namespace App\Http\Middleware;

use App\Http\Controllers\api\auth\apiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class checkAdmin
{
    use apiResponse;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $this->sendError('Unauthenticated', 401);
        }

        if ($user->role != 'admin') {
            return $this->sendError('Forbidden', 403);
        }

        return $next($request);
    }
}
