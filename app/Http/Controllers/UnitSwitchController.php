<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnitSwitchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'unit_id' => ['required', 'exists:units,id'],
        ]);

        $unit = Unit::where('id', $request->unit_id)
            ->where('is_active', true)
            ->first();

        if (! $unit) {
            return back()->withErrors(['unit_id' => __('message.unit_inactive')]);
        }

        $user = $request->user();

        if (! $user->hasRole(['super_admin', 'bendahara']) && $user->unit_id !== $unit->id) {
            abort(403, __('message.no_switch_permission'));
        }

        session(['current_unit_id' => $unit->id]);

        return back();
    }
}
