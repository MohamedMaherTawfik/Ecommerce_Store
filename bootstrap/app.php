<?php

use App\Http\Middleware\ApiKeyMiddleware;
use App\Http\Middleware\AuthenticateWithAuthCookie;
use App\Http\Middleware\CheckIfInstalled;
use App\Http\Middleware\EnsureModulePermission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(CheckIfInstalled::class);
        $middleware->api(prepend: [
            ApiKeyMiddleware::class,
            AuthenticateWithAuthCookie::class,
        ]);
        $middleware->alias([
            'permission' => EnsureModulePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // $exceptions->render(function (\Throwable $e, Request $request) {
        //     if (! $request->expectsJson() && ! $request->is('api/*')) {
        //         return null;
        //     }

        //     $payload = [
        //         'success' => false,
        //         'message' => 'Server error.',
        //         'data' => null,
        //         'errors' => [],
        //     ];

        //     if ($e instanceof ValidationException) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Validation failed.',
        //             'data' => null,
        //             'errors' => $e->errors(),
        //         ], 422);
        //     }

        //     if ($e instanceof HttpResponseException) {
        //         return $e->getResponse();
        //     }

        //     if ($e instanceof AuthenticationException) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Unauthenticated.',
        //             'data' => null,
        //             'errors' => [],
        //         ], 401);
        //     }

        //     if ($e instanceof AuthorizationException) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Forbidden.',
        //             'data' => null,
        //             'errors' => [],
        //         ], 403);
        //     }

        //     if ($e instanceof ModelNotFoundException) {
        //         $model = class_basename($e->getModel() ?: 'resource');

        //         return response()->json([
        //             'success' => false,
        //             'message' => "{$model} not found.",
        //             'data' => null,
        //             'errors' => [],
        //         ], 404);
        //     }

        //     if ($e instanceof QueryException) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => app()->isProduction() ? 'Database error.' : $e->getMessage(),
        //             'data' => null,
        //             'errors' => [],
        //         ], 500);
        //     }

        //     if (config('app.debug')) {
        //         $payload['message'] = $e->getMessage();
        //     }

        //     return response()->json($payload, 500);
        // });
    })->create();
