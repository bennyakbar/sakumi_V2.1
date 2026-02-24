<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreFeeTypeRequest;
use App\Http\Requests\Master\UpdateFeeTypeRequest;
use App\Models\FeeType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FeeTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $feeTypes = FeeType::withTrashed()->latest()->paginate(15);

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
    public function destroy(FeeType $feeType): RedirectResponse
    {
        if ($feeType->feeMatrix()->exists()) {
            return back()->with('error', __('message.fee_type_in_use'));
        }

        $feeType->delete();

        return redirect()->route('master.fee-types.index')
            ->with('success', __('message.fee_type_deleted'));
    }

    public function restore(int $id): RedirectResponse
    {
        $feeType = FeeType::withTrashed()->findOrFail($id);
        $feeType->restore();

        return redirect()->route('master.fee-types.index')
            ->with('success', __('message.fee_type_restored'));
    }
}
