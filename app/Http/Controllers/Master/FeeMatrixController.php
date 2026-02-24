<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreFeeMatrixRequest;
use App\Http\Requests\Master\UpdateFeeMatrixRequest;
use App\Models\FeeMatrix;
use App\Models\FeeType;
use App\Models\SchoolClass;
use App\Models\StudentCategory;
use App\Services\PermanentDeleteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FeeMatrixController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $feeMatrices = FeeMatrix::with(['schoolClass', 'category', 'feeType'])
            ->latest()
            ->paginate(15);

        return view('master.fee-matrix.index', compact('feeMatrices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $classes = SchoolClass::where('is_active', true)->get();
        $categories = StudentCategory::all();
        $feeTypes = FeeType::where('is_active', true)->get();
        return view('master.fee-matrix.create', compact('classes', 'categories', 'feeTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFeeMatrixRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $exists = FeeMatrix::where('class_id', $validated['class_id'])
            ->where('category_id', $validated['category_id'])
            ->where('fee_type_id', $validated['fee_type_id'])
            ->whereDate('effective_from', $validated['effective_from'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors(['error' => __('message.fee_matrix_exists')]);
        }

        FeeMatrix::create($validated);

        return redirect()->route('master.fee-matrix.index')
            ->with('success', __('message.fee_matrix_created'));
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
    public function edit(FeeMatrix $feeMatrix): View
    {
        $classes = SchoolClass::where('is_active', true)->get();
        $categories = StudentCategory::all();
        $feeTypes = FeeType::where('is_active', true)->get();
        return view('master.fee-matrix.edit', compact('feeMatrix', 'classes', 'categories', 'feeTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFeeMatrixRequest $request, FeeMatrix $feeMatrix): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        $exists = FeeMatrix::where('class_id', $validated['class_id'])
            ->where('category_id', $validated['category_id'])
            ->where('fee_type_id', $validated['fee_type_id'])
            ->whereDate('effective_from', $validated['effective_from'])
            ->where('id', '!=', $feeMatrix->id)
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors(['error' => __('message.fee_matrix_exists')]);
        }

        $feeMatrix->update($validated);

        return redirect()->route('master.fee-matrix.index')
            ->with('success', __('message.fee_matrix_updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, FeeMatrix $feeMatrix): RedirectResponse
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

            $mappingCount = DB::table('student_fee_mappings')->where('fee_matrix_id', $feeMatrix->id)->count();
            if ($mappingCount > 0) {
                return back()->withErrors([
                    'delete' => __('message.permanent_delete_blocked_dependencies', [
                        'details' => "student_fee_mappings:{$mappingCount}",
                    ]),
                ]);
            }

            $feeMatrix->forceDelete();

            return redirect()->route('master.fee-matrix.index')
                ->with('success', __('message.fee_matrix_permanently_deleted'));
        }

        $feeMatrix->delete();

        return redirect()->route('master.fee-matrix.index')
            ->with('success', __('message.fee_matrix_deleted'));
    }

}
