<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roleArgs): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $roles = collect($roleArgs)
            ->flatMap(function (string $arg): array {
                $parts = preg_split('/[|,]/', $arg);
                return is_array($parts) ? $parts : [$arg];
            })
            ->map(fn (string $role): string => trim($role))
            ->filter()
            ->flatMap(function (string $role): array {
                // Keep backward compatibility between legacy "superadmin" and canonical "super_admin".
                if ($role === 'superadmin') {
                    return ['superadmin', 'super_admin'];
                }

                if ($role === 'super_admin') {
                    return ['super_admin', 'superadmin'];
                }

                return [$role];
            })
            ->unique()
            ->values()
            ->all();

        if ($roles === [] || ! $user->hasAnyRole($roles)) {
            // ── TEMPORARY DEBUG — remove after diagnosing 403 ──
            \Log::warning('CheckRole DENIED', [
                'user_id'         => $user->id,
                'user_email'      => $user->email,
                'user_class'      => get_class($user),
                'guard'           => $user->guard_name ?? 'NOT SET',
                'required_roles'  => $roles,
                'user_roles_db'   => $user->roles()->pluck('name', 'guard_name')->toArray(),
                'user_role_names' => $user->getRoleNames()->toArray(),
                'has_any_role'    => $user->hasAnyRole($roles),
                'roles_empty'     => $roles === [],
                'url'             => $request->fullUrl(),
                'model_type_in_db' => \DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->pluck('role_id')
                    ->toArray(),
            ]);
            // ── END TEMPORARY DEBUG ──

            abort(403, __('message.unauthorized'));
        }

        return $next($request);
    }
}
