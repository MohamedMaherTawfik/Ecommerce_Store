<?php

use App\Http\Middleware\AuthenticateWithAuthCookie;
use App\Http\Middleware\CheckIfInstalled;
use App\Http\Middleware\EnsureModulePermission;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(
            fn (Request $request): ?string => $request->is('api/*') ? null : route('login')
        );
        $middleware->append(CheckIfInstalled::class);
        $middleware->api(prepend: [
            AuthenticateWithAuthCookie::class,
        ]);
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'permission' => EnsureModulePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $exception): bool => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof HttpResponseException) {
                return $exception->getResponse();
            }

            $status = match (true) {
                $exception instanceof ValidationException => 422,
                $exception instanceof AuthenticationException => 401,
                $exception instanceof AuthorizationException => 403,
                $exception instanceof ModelNotFoundException => 404,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };

            $message = match ($status) {
                400 => 'Bad request.',
                401 => 'Unauthenticated.',
                403 => 'Forbidden.',
                404 => 'Resource not found.',
                405 => 'Method not allowed.',
                409 => 'Conflict.',
                422 => 'Validation failed.',
                429 => 'Too many requests. Please try again later.',
                default => 'Server error.',
            };

            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => null,
                'errors' => $exception instanceof ValidationException ? $exception->errors() : [],
            ], $status);
        });
    })->create();
