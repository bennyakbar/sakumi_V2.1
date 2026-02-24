<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreFeeTypeRequest;
use App\Http\Requests\Master\UpdateFeeTypeRequest;
use App\Models\FeeType;
use App\Services\PermanentDeleteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

            $dependencies = [];
            $counts = [
                'fee_matrix' => DB::table('fee_matrix')->where('fee_type_id', $feeType->id)->count(),
                'transaction_items' => DB::table('transaction_items')->where('fee_type_id', $feeType->id)->count(),
                'student_obligations' => DB::table('student_obligations')->where('fee_type_id', $feeType->id)->count(),
                'invoice_items' => DB::table('invoice_items')->where('fee_type_id', $feeType->id)->count(),
            ];
            foreach ($counts as $key => $count) {
                if ($count > 0) {
                    $dependencies[] = "{$key}:{$count}";
                }
            }
            if (!empty($dependencies)) {
                return back()->withErrors([
                    'delete' => __('message.permanent_delete_blocked_dependencies', ['details' => implode(', ', $dependencies)]),
                ]);
            }

            $feeType->forceDelete();

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
