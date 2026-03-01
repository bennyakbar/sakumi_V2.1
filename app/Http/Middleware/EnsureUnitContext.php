<?php

namespace App\Http\Middleware;

use App\Models\Unit;
use App\Services\UnitContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureUnitContext
{
    public function __construct(
        private readonly UnitContext $unitContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Resolve unit from session (existing flow) or fall back to user's unit
        if (! $this->unitContext->id()) {
            if (! $user->unit_id) {
                abort(403, __('message.no_unit_assigned'));
            }
            $this->unitContext->set($user->unit_id);
        }

        $currentUnit = Unit::find($this->unitContext->id());

        $switchableUnits = $user->hasRole(['super_admin', 'bendahara'])
            ? Unit::where('is_active', true)->orderBy('code')->get()
            : collect();

        View::share('currentUnit', $currentUnit);
        View::share('switchableUnits', $switchableUnits);

        return $next($request);
    }
}
