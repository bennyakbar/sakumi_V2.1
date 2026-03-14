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

        $sessionUnitId = session('current_unit_id');
        $fallbackUnitId = $user->unit_id;
        $resolvedUnitId = $sessionUnitId ?: $fallbackUnitId;

        // Auto-heal: persist fallback into session so subsequent requests don't
        // repeat the lookup and the value survives across the request lifecycle.
        if (! $sessionUnitId && $resolvedUnitId) {
            session(['current_unit_id' => (int) $resolvedUnitId]);
        }

        if (! $resolvedUnitId) {
            abort(403, __('message.no_unit_assigned'));
        }

        $canSwitchUnit = $user->hasAnyRole(['super_admin', 'superadmin', 'bendahara']);
        if (! $canSwitchUnit && (int) $resolvedUnitId !== (int) $fallbackUnitId) {
            $resolvedUnitId = $fallbackUnitId;
            session(['current_unit_id' => $resolvedUnitId]);
        }

        $currentUnit = Unit::query()
            ->whereKey($resolvedUnitId)
            ->where('is_active', true)
            ->first();

        if (! $currentUnit) {
            session()->forget('current_unit_id');
            abort(403, __('message.no_unit_assigned'));
        }

        $this->unitContext->set((int) $currentUnit->id);

        $switchableUnits = $canSwitchUnit
            ? Unit::where('is_active', true)->orderBy('code')->get()
            : collect();

        View::share('currentUnit', $currentUnit);
        View::share('switchableUnits', $switchableUnits);

        return $next($request);
    }
}
