<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $hasRole = $user->hasAnyRole($roles);

        if (! $hasRole) {

            Log::error('CheckRole DENIED', [
                'user_id' => $user->id,
                'user_class' => get_class($user),
                'guard' => auth()->getDefaultDriver(),
                'required_roles' => $roles,
                'user_role_names' => $user->getRoleNames(),
                'has_any_role' => $hasRole
            ]);

            abort(403, __('message.unauthorized'));
        }

        return $next($request);
    }
}
