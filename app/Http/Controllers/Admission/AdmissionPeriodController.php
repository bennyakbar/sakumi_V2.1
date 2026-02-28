<?php

namespace App\Http\Controllers\Admission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admission\StoreAdmissionPeriodRequest;
use App\Http\Requests\Admission\UpdateAdmissionPeriodRequest;
use App\Models\AdmissionPeriod;
use App\Models\AdmissionPeriodQuota;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdmissionPeriodController extends Controller
{
    public function index(): View
    {
        $periods = AdmissionPeriod::query()
            ->withCount('applicants')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admission.periods.index', compact('periods'));
    }

    public function create(): View
    {
        $classes = SchoolClass::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admission.periods.create', compact('classes'));
    }

    public function store(StoreAdmissionPeriodRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $period = AdmissionPeriod::create($request->safe()->except('quotas'));

            if ($request->has('quotas')) {
                foreach ($request->input('quotas', []) as $quota) {
                    if (! empty($quota['class_id']) && ! empty($quota['quota'])) {
                        AdmissionPeriodQuota::create([
                            'unit_id' => $period->unit_id,
                            'admission_period_id' => $period->id,
                            'class_id' => $quota['class_id'],
                            'quota' => $quota['quota'],
                        ]);
                    }
                }
            }
        });

        return redirect()->route('admission.periods.index')
            ->with('success', __('message.admission_period_created'));
    }

    public function show(AdmissionPeriod $period): View
    {
        $period->load(['quotas.schoolClass', 'applicants' => fn ($q) => $q->latest()->limit(20)]);

        $quotaStats = [];
        foreach ($period->quotas as $quota) {
            $acceptedCount = $period->applicants()
                ->where('target_class_id', $quota->class_id)
                ->whereIn('status', ['accepted', 'enrolled'])
                ->count();
            $quotaStats[$quota->class_id] = [
                'class_name' => $quota->schoolClass->name ?? '-',
                'quota' => $quota->quota,
                'filled' => $acceptedCount,
            ];
        }

        return view('admission.periods.show', compact('period', 'quotaStats'));
    }

    public function edit(AdmissionPeriod $period): View
    {
        $period->load('quotas');
        $classes = SchoolClass::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admission.periods.edit', compact('period', 'classes'));
    }

    public function update(UpdateAdmissionPeriodRequest $request, AdmissionPeriod $period): RedirectResponse
    {
        DB::transaction(function () use ($request, $period) {
            $period->update($request->safe()->except('quotas'));

            // Sync quotas
            $period->quotas()->delete();
            if ($request->has('quotas')) {
                foreach ($request->input('quotas', []) as $quota) {
                    if (! empty($quota['class_id']) && ! empty($quota['quota'])) {
                        AdmissionPeriodQuota::create([
                            'unit_id' => $period->unit_id,
                            'admission_period_id' => $period->id,
                            'class_id' => $quota['class_id'],
                            'quota' => $quota['quota'],
                        ]);
                    }
                }
            }
        });

        return redirect()->route('admission.periods.index')
            ->with('success', __('message.admission_period_updated'));
    }

    public function destroy(AdmissionPeriod $period): RedirectResponse
    {
        if ($period->applicants()->exists()) {
            return back()->withErrors(['delete' => __('message.admission_period_has_applicants')]);
        }

        $period->delete();

        return redirect()->route('admission.periods.index')
            ->with('success', __('message.admission_period_deleted'));
    }
}
