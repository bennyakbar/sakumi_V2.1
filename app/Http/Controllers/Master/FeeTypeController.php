<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreFeeTypeRequest;
use App\Http\Requests\Master\UpdateFeeTypeRequest;
use App\Models\FeeType;
use App\Services\PermanentDeleteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\View\View;

class FeeTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $feeTypes = FeeType::latest()->paginate(15);

        return view('master.fee-types.index', compact('feeTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('master.fee-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFeeTypeRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_monthly'] = $request->has('is_monthly');
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        FeeType::create($validated);

        return redirect()->route('master.fee-types.index')
            ->with('success', __('message.fee_type_created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FeeType $feeType): View
    {
        return view('master.fee-types.edit', compact('feeType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFeeTypeRequest $request, FeeType $feeType): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_monthly'] = $request->has('is_monthly');
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $feeType->is_active;

        $feeType->update($validated);

        return redirect()->route('master.fee-types.index')
            ->with('success', __('message.fee_type_updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, FeeType $feeType): RedirectResponse
    {
        $permanentDelete = app(PermanentDeleteService::class);
        if ($permanentDelete->isRequested($request)) {
            $actor = $request->user();
            if (!$actor || !$permanentDelete->isAllowedFor($actor)) {
                return back()->withErrors(['delete' => __('message.permanent_delete_not_allowed')]);
            }
            if (!$permanentDelete->hasValidConfirmation($request)) {
                return back()->withErrors(['delete' => __('message.permanent_delete_confirmation_invalid')]);
            }

            $blocking = $permanentDelete->onlyBlockingDependencies(
                $permanentDelete->dependencyCounts(PermanentDeleteService::ENTITY_FEE_TYPE, (int) $feeType->id)
            );
            if (!empty($blocking)) {
                $permanentDelete->logSnapshot($actor, PermanentDeleteService::ENTITY_FEE_TYPE, $feeType, $blocking, 'blocked');
                return back()->withErrors([
                    'delete' => __('message.permanent_delete_blocked_dependencies', [
                        'details' => $permanentDelete->formatDependencies($blocking),
                    ]),
                ]);
            }

            try {
                $permanentDelete->logSnapshot($actor, PermanentDeleteService::ENTITY_FEE_TYPE, $feeType, [], 'attempt');
                $feeType->forceDelete();
            } catch (QueryException $e) {
                $permanentDelete->logSnapshot($actor, PermanentDeleteService::ENTITY_FEE_TYPE, $feeType, [], 'failed', $e->getMessage());
                return back()->withErrors(['delete' => __('message.permanent_delete_failed_fk')]);
            }

            return redirect()->route('master.fee-types.index')
                ->with('success', __('message.fee_type_permanently_deleted'));
        }

        if ($feeType->feeMatrix()->exists()) {
            return back()->with('error', __('message.fee_type_in_use'));
        }

        $feeType->delete();

        return redirect()->route('master.fee-types.index')
            ->with('success', __('message.fee_type_deleted'));
    }

}
