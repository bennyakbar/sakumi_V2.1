<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictRoleManagement
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('super_admin')) {
            abort(403, __('message.super_admin_only'));
        }

        // Prevent users from modifying their own role
        $targetUserId = $request->route('user');
        if ($targetUserId && (int) $targetUserId === $user->id) {
            abort(403, __('message.cannot_modify_own_role'));
        }

        return $next($request);
    }
}
