<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'spatie.role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'spatie.permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'spatie.role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'audit' => \App\Http\Middleware\AuditLog::class,
            'force.https' => \App\Http\Middleware\ForceHttps::class,
            'restrict.roles' => \App\Http\Middleware\RestrictRoleManagement::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\CheckInactivity::class,
            \App\Http\Middleware\EnsureUnitContext::class,
            \App\Http\Middleware\LogFailedActions::class,
        ]);

        if (env('APP_ENV') === 'production') {
            $middleware->web(prepend: [
                \App\Http\Middleware\ForceHttps::class,
            ]);
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Log all unhandled exceptions with request context for debugging
        $exceptions->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });

        // Return JSON for AJAX requests, friendly views for browsers
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Resource tidak ditemukan.',
                    'status' => 404,
                ], 404);
            }
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($request->expectsJson() && $e->getStatusCode() >= 500) {
                \Illuminate\Support\Facades\Log::error('Server error on AJAX request', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_id' => $request->user()?->id,
                    'status' => $e->getStatusCode(),
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'error' => 'Terjadi kesalahan pada server.',
                    'status' => $e->getStatusCode(),
                ], $e->getStatusCode());
            }
        });
    })->create();
