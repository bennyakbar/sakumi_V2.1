<?php

namespace App\Http\Controllers\Admission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admission\EnrollApplicantRequest;
use App\Http\Requests\Admission\RejectApplicantRequest;
use App\Http\Requests\Admission\StoreApplicantRequest;
use App\Http\Requests\Admission\UpdateApplicantRequest;
use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use App\Models\SchoolClass;
use App\Models\StudentCategory;
use App\Services\AdmissionService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApplicantController extends Controller
{
    public function __construct(
        private readonly AdmissionService $admissionService,
    ) {
    }

    public function index(): View
    {
        $applicants = Applicant::query()
            ->with(['admissionPeriod:id,name', 'targetClass:id,name', 'category:id,name'])
            ->when(request('search'), function ($query, string $search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('registration_number', 'like', "%{$search}%");
                });
            })
            ->when(request('admission_period_id'), fn ($q, $v) => $q->where('admission_period_id', $v))
            ->when(request('status'), fn ($q, $v) => $q->where('status', $v))
            ->when(request('target_class_id'), fn ($q, $v) => $q->where('target_class_id', $v))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $periods = AdmissionPeriod::query()->orderByDesc('registration_open')->get(['id', 'name']);
        $classes = SchoolClass::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admission.applicants.index', compact('applicants', 'periods', 'classes'));
    }

    public function create(): View
    {
        $periods = AdmissionPeriod::query()
            ->whereIn('status', ['open'])
            ->orderByDesc('registration_open')
            ->get(['id', 'name']);
        $classes = SchoolClass::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $categories = StudentCategory::query()->orderBy('name')->get(['id', 'name']);

        return view('admission.applicants.create', compact('periods', 'classes', 'categories'));
    }

    public function store(StoreApplicantRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $unitId = (int) session('current_unit_id');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                DB::transaction(function () use ($data, $unitId, $request) {
                    $data['registration_number'] = $this->admissionService->generateRegistrationNumber($unitId);
                    $data['created_by'] = $request->user()->id;
                    $data['status'] = 'registered';
                    Applicant::create($data);
                });

                return redirect()->route('admission.applicants.index')
                    ->with('success', __('message.applicant_created'));
            } catch (UniqueConstraintViolationException $e) {
                $isRegNumberCollision = str_contains($e->getMessage(), 'applicants.registration_number');
                if (! $isRegNumberCollision || $attempt === 2) {
                    throw $e;
                }
            }
        }

        return back()->withErrors([
            'registration_number' => __('message.unexpected_error'),
        ]);
    }

    public function show(Applicant $applicant): View
    {
        $applicant->load([
            'admissionPeriod',
            'targetClass',
            'category',
            'student',
            'creator',
            'statusChanger',
        ]);

        return view('admission.applicants.show', compact('applicant'));
    }

    public function edit(Applicant $applicant): View
    {
        if (in_array($applicant->status, ['enrolled'], true)) {
            return redirect()->route('admission.applicants.show', $applicant)
                ->withErrors(['edit' => __('message.applicant_cannot_edit_enrolled')]);
        }

        $periods = AdmissionPeriod::query()->orderByDesc('registration_open')->get(['id', 'name']);
        $classes = SchoolClass::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $categories = StudentCategory::query()->orderBy('name')->get(['id', 'name']);

        return view('admission.applicants.edit', compact('applicant', 'periods', 'classes', 'categories'));
    }

    public function update(UpdateApplicantRequest $request, Applicant $applicant): RedirectResponse
    {
        if (in_array($applicant->status, ['enrolled'], true)) {
            return back()->withErrors(['edit' => __('message.applicant_cannot_edit_enrolled')]);
        }

        $applicant->update($request->validated());

        return redirect()->route('admission.applicants.index')
            ->with('success', __('message.applicant_updated'));
    }

    public function destroy(Applicant $applicant): RedirectResponse
    {
        if ($applicant->status === 'enrolled') {
            return back()->withErrors(['delete' => __('message.applicant_cannot_delete_enrolled')]);
        }

        $applicant->delete();

        return redirect()->route('admission.applicants.index')
            ->with('success', __('message.applicant_deleted'));
    }

    public function review(Applicant $applicant): RedirectResponse
    {
        try {
            $this->admissionService->moveToReview($applicant, auth()->id());

            return back()->with('success', __('message.applicant_reviewed'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    public function accept(Applicant $applicant): RedirectResponse
    {
        try {
            $this->admissionService->accept($applicant, auth()->id());

            return back()->with('success', __('message.applicant_accepted'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    public function reject(RejectApplicantRequest $request, Applicant $applicant): RedirectResponse
    {
        try {
            $this->admissionService->reject($applicant, auth()->id(), $request->input('rejection_reason'));

            return back()->with('success', __('message.applicant_rejected'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    public function enroll(EnrollApplicantRequest $request, Applicant $applicant): RedirectResponse
    {
        try {
            $this->admissionService->enroll($applicant, auth()->id(), $request->input('nis'));

            return redirect()->route('admission.applicants.show', $applicant)
                ->with('success', __('message.applicant_enrolled'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    public function bulkStatus(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:applicants,id'],
            'status' => ['required', 'in:under_review,accepted,rejected'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $targetStatus = (string) $request->input('status');
        $requiredPermission = match ($targetStatus) {
            'under_review' => 'admission.applicants.review',
            'accepted' => 'admission.applicants.accept',
            'rejected' => 'admission.applicants.reject',
            default => null,
        };

        abort_unless(
            $requiredPermission && $request->user()->can($requiredPermission),
            403
        );

        $result = $this->admissionService->bulkUpdateStatus(
            $request->input('ids'),
            $targetStatus,
            auth()->id(),
            $request->input('rejection_reason'),
        );

        $response = back()->with('success', __('message.applicants_bulk_updated', ['count' => $result['updated']]));

        if (! empty($result['failed'])) {
            $failedIds = collect($result['failed'])
                ->pluck('id')
                ->implode(', ');

            $response->with('error', __('message.unexpected_error').' (Failed IDs: '.$failedIds.')');
        }

        return $response;
    }
}
