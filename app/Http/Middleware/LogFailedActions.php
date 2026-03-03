<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Log all responses with 4xx/5xx status codes on write operations.
 *
 * Captures failed form submissions, authorization denials, and server
 * errors with enough context to debug without exposing sensitive data.
 */
class LogFailedActions
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $status = $response->getStatusCode();

        // Only log failures on state-changing methods
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        if ($status >= 400) {
            $level = $status >= 500 ? 'error' : 'warning';

            Log::channel('single')->{$level}('Failed action', [
                'status' => $status,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'route' => $request->route()?->getName(),
                'user_id' => $request->user()?->id,
                'user_email' => $request->user()?->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'validation_errors' => $status === 422 ? session('errors')?->toArray() : null,
            ]);
        }

        return $response;
    }
}
