<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\StoreUserRequest;
use App\Http\Requests\UserManagement\UpdateUserRequest;
use App\Models\Unit;
use App\Models\User;
use App\Services\PermanentDeleteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        $isSuperAdmin = $actor->hasRole('super_admin');
        $perPage = in_array((int) $request->input('per_page', 15), [15, 25, 50, 100], true)
            ? (int) $request->input('per_page', 15)
            : 15;

        $users = $this->buildIndexQuery($request, $isSuperAdmin)
            ->paginate($perPage)
            ->withQueryString();

        $units = $isSuperAdmin
            ? Unit::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name'])
            : collect();
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        return view('users.index', compact('users', 'units', 'roles', 'isSuperAdmin', 'perPage'));
    }

    public function export(Request $request)
    {
        $isSuperAdmin = $request->user()->hasRole('super_admin');
        $users = $this->buildIndexQuery($request, $isSuperAdmin)->get();

        $filename = 'users-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($users): void {
            $out = fopen('php://output', 'wb');
            if ($out === false) {
                return;
            }

            fputcsv($out, ['Name', 'Email', 'Unit', 'Roles', 'Status']);
            foreach ($users as $user) {
                fputcsv($out, [
                    $user->name,
                    $user->email,
                    $user->unit?->code ?? '',
                    $user->roles->pluck('name')->implode(', '),
                    $user->is_active ? 'active' : 'inactive',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create(Request $request): View
    {
        $actor = $request->user();
        $isSuperAdmin = $actor->hasRole('super_admin');

        $units = $isSuperAdmin
            ? Unit::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name'])
            : collect();

        $roles = $actor->can('users.manage-roles')
            ? Role::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('users.create', compact('units', 'roles', 'isSuperAdmin'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validated();

        $unitId = $actor->hasRole('super_admin')
            ? ($validated['unit_id'] ?? null)
            : session('current_unit_id');

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'unit_id' => $unitId,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($actor->can('users.manage-roles') && ! empty($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        activity('users')
            ->causedBy($actor)
            ->performedOn($user)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('users.created');

        return redirect()->route('users.index')->with('success', __('message.user_created'));
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorizeUnitScope($request->user(), $user);

        $isSuperAdmin = $request->user()->hasRole('super_admin');

        $units = $isSuperAdmin
            ? Unit::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name'])
            : collect();

        $roles = $request->user()->can('users.manage-roles')
            ? Role::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        $selectedRole = $user->roles->pluck('name')->first();

        return view('users.edit', compact('user', 'units', 'roles', 'selectedRole', 'isSuperAdmin'));
    }

    public function show(Request $request, User $user): View
    {
        $this->authorizeUnitScope($request->user(), $user);
        $user->load(['unit:id,code,name', 'roles:id,name']);

        $activities = $user->actions()
            ->latest()
            ->limit(20)
            ->get(['id', 'description', 'properties', 'created_at']);

        return view('users.show', compact('user', 'activities'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->authorizeUnitScope($actor, $user);

        $validated = $request->validated();
        $requestedRole = $validated['role'] ?? null;

        $updates = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => (bool) ($validated['is_active'] ?? $user->is_active),
        ];

        if ($actor->hasRole('super_admin') && array_key_exists('unit_id', $validated)) {
            $updates['unit_id'] = $validated['unit_id'];
        }

        if (! empty($validated['password'])) {
            $updates['password'] = $validated['password'];
        }

        if ($user->id === $actor->id && (isset($updates['is_active']) && $updates['is_active'] === false)) {
            return back()->withInput()->withErrors(['is_active' => __('message.cannot_deactivate_self')]);
        }

        if ($actor->can('users.manage-roles') && array_key_exists('role', $validated) && $user->id === $actor->id) {
            return back()->withInput()->withErrors(['role' => __('message.cannot_modify_own_role')]);
        }

        $user->update($updates);

        if ($actor->can('users.manage-roles') && array_key_exists('role', $validated)) {
            if ($requestedRole) {
                $user->syncRoles([$requestedRole]);
            } else {
                $user->syncRoles([]);
            }
        }

        activity('users')
            ->causedBy($actor)
            ->performedOn($user)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('users.updated');

        return redirect()->route('users.index')->with('success', __('message.user_updated'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->authorizeUnitScope($actor, $user);
        $permanentDelete = app(PermanentDeleteService::class);

        if ($user->id === $actor->id) {
            return back()->withErrors(['delete' => __('message.cannot_deactivate_self')]);
        }

        if ($permanentDelete->isRequested($request)) {
            if (!$permanentDelete->isAllowedFor($actor)) {
                return back()->withErrors(['delete' => __('message.permanent_delete_not_allowed')]);
            }
            if (!$permanentDelete->hasValidConfirmation($request)) {
                return back()->withErrors(['delete' => __('message.permanent_delete_confirmation_invalid')]);
            }

            $blocking = $permanentDelete->onlyBlockingDependencies(
                $permanentDelete->dependencyCounts(PermanentDeleteService::ENTITY_USER, (int) $user->id)
            );
            if (!empty($blocking)) {
                $permanentDelete->logSnapshot($actor, PermanentDeleteService::ENTITY_USER, $user, $blocking, 'blocked');
                return back()->withErrors([
                    'delete' => __('message.permanent_delete_blocked_dependencies', [
                        'details' => $permanentDelete->formatDependencies($blocking),
                    ]),
                ]);
            }

            try {
                $permanentDelete->logSnapshot($actor, PermanentDeleteService::ENTITY_USER, $user, [], 'attempt');

                DB::transaction(function () use ($user): void {
                    $user->roles()->detach();
                    $user->delete();
                });
            } catch (QueryException $e) {
                $permanentDelete->logSnapshot($actor, PermanentDeleteService::ENTITY_USER, $user, [], 'failed', $e->getMessage());
                return back()->withErrors([
                    'delete' => __('message.permanent_delete_failed_fk'),
                ]);
            }

            activity('users')
                ->causedBy($actor)
                ->withProperties([
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'target_user_id' => $user->id,
                ])
                ->log('users.permanently_deleted');

            return redirect()->route('users.index')->with('success', __('message.user_permanently_deleted'));
        }

        $user->update(['is_active' => false]);

        activity('users')
            ->causedBy($actor)
            ->performedOn($user)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('users.deactivated');

        return redirect()->route('users.index')->with('success', __('message.user_deleted'));
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->authorizeUnitScope($actor, $user);

        $temporaryPassword = Str::password(16);

        $user->update([
            'password' => Hash::make($temporaryPassword),
            'remember_token' => Str::random(60),
        ]);

        activity('users')
            ->causedBy($actor)
            ->performedOn($user)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('users.password_reset_by_admin');

        return back()
            ->with('success', __('message.user_password_reset'))
            ->with('temporary_password', $temporaryPassword)
            ->with('temporary_password_user_id', $user->id);
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
            'action' => ['required', 'in:activate,deactivate'],
        ]);

        $query = User::query()->whereIn('id', $validated['ids']);
        if (! $actor->hasRole('super_admin')) {
            $query->where('unit_id', session('current_unit_id'));
        }

        if ($validated['action'] === 'deactivate') {
            $query->where('id', '!=', $actor->id);
        }

        $updatedCount = $query->update([
            'is_active' => $validated['action'] === 'activate',
        ]);

        activity('users')
            ->causedBy($actor)
            ->withProperties([
                'action' => $validated['action'],
                'count' => $updatedCount,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('users.bulk_status_updated');

        return redirect()->route('users.index')
            ->with('success', __('message.users_bulk_updated', ['count' => $updatedCount]));
    }

    private function authorizeUnitScope(User $actor, User $target): void
    {
        if ($actor->hasRole('super_admin')) {
            return;
        }

        if ((int) $actor->unit_id !== (int) $target->unit_id) {
            abort(403);
        }
    }

    private function buildIndexQuery(Request $request, bool $isSuperAdmin): Builder
    {
        $query = User::query()
            ->with(['unit:id,code,name', 'roles:id,name'])
            ->when($request->string('search')->toString(), function ($builder, string $search): void {
                $builder->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(
                ! $isSuperAdmin,
                fn ($builder) => $builder->where('unit_id', session('current_unit_id')),
                function ($builder) use ($request): void {
                    if ($request->filled('unit_id')) {
                        $builder->where('unit_id', (int) $request->input('unit_id'));
                    }
                }
            )
            ->when($request->input('status') === 'active', fn ($builder) => $builder->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($builder) => $builder->where('is_active', false))
            ->when($request->filled('role'), function ($builder) use ($request): void {
                $builder->whereHas('roles', fn ($roles) => $roles->where('name', $request->input('role')));
            });

        $sort = (string) $request->input('sort', 'latest');
        $roleOrderSql = "(SELECT MIN(r.name) FROM roles r INNER JOIN model_has_roles mhr ON mhr.role_id = r.id WHERE mhr.model_id = users.id AND mhr.model_type = ?)";

        match ($sort) {
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'email_asc' => $query->orderBy('email'),
            'email_desc' => $query->orderByDesc('email'),
            'status_asc' => $query->orderByDesc('is_active')->orderBy('name'),
            'status_desc' => $query->orderBy('is_active')->orderBy('name'),
            'role_asc' => $query->orderByRaw($roleOrderSql . ' asc', [User::class]),
            'role_desc' => $query->orderByRaw($roleOrderSql . ' desc', [User::class]),
            default => $query->latest(),
        };

        return $query;
    }
}
