<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ApplyPromotionBatchRequest;
use App\Http\Requests\Master\ApprovePromotionBatchRequest;
use App\Http\Requests\Master\StorePromotionBatchRequest;
use App\Models\AcademicYear;
use App\Models\PromotionBatch;
use App\Models\PromotionBatchStudent;
use App\Models\SchoolClass;
use App\Models\StudentEnrollment;
use App\Services\PromotionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PromotionBatchController extends Controller
{
    public function index(): View
    {
        $batches = PromotionBatch::query()
            ->with(['fromAcademicYear:id,code', 'toAcademicYear:id,code'])
            ->withCount('items')
            ->latest()
            ->paginate(15);

        return view('master.promotions.index', compact('batches'));
    }

    public function create(): View
    {
        $academicYears = AcademicYear::query()->orderByDesc('code')->get(['id', 'code', 'status']);
        $classes = SchoolClass::query()->orderBy('name')->get(['id', 'name', 'academic_year_id']);
        $currentEnrollments = StudentEnrollment::query()
            ->with(['student:id,name,nis', 'schoolClass:id,name', 'academicYear:id,code'])
            ->where('is_current', true)
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'student_id', 'academic_year_id', 'class_id']);

        return view('master.promotions.create', compact('academicYears', 'classes', 'currentEnrollments'));
    }

    public function store(StorePromotionBatchRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $items = $validated['items'] ?? [];
        if (empty($items) && $request->hasFile('items_csv')) {
            $items = $this->itemsFromCsv($request->file('items_csv'));
        }

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'At least one promotion item is required.',
            ]);
        }

        $unitId = (int) session('current_unit_id');
        $itemsValidator = Validator::make(['items' => $items], [
            'items' => ['required', 'array', 'min:1'],
            'items.*.student_id' => ['required', Rule::exists('students', 'id')->where('unit_id', $unitId)],
            'items.*.from_enrollment_id' => ['required', Rule::exists('student_enrollments', 'id')->where('unit_id', $unitId)],
            'items.*.action' => ['required', 'in:promote,retain,graduate'],
            'items.*.to_class_id' => ['nullable', Rule::exists('classes', 'id')->where('unit_id', $unitId)],
            'items.*.reason' => ['nullable', 'string', 'max:255'],
        ]);
        if ($itemsValidator->fails()) {
            throw ValidationException::withMessages($itemsValidator->errors()->toArray());
        }

        $batch = DB::transaction(function () use ($validated, $request, $items): PromotionBatch {
            $batch = PromotionBatch::query()->create([
                'from_academic_year_id' => (int) $validated['from_academic_year_id'],
                'to_academic_year_id' => (int) $validated['to_academic_year_id'],
                'effective_date' => $validated['effective_date'],
                'status' => 'draft',
                'created_by' => (int) $request->user()->id,
            ]);

            foreach ($items as $item) {
                if (in_array($item['action'], ['promote', 'retain'], true) && empty($item['to_class_id'])) {
                    throw ValidationException::withMessages([
                        'items' => "to_class_id is required for action {$item['action']}.",
                    ]);
                }

                PromotionBatchStudent::query()->create([
                    'promotion_batch_id' => $batch->id,
                    'student_id' => (int) $item['student_id'],
                    'from_enrollment_id' => (int) $item['from_enrollment_id'],
                    'action' => $item['action'],
                    'to_class_id' => $item['to_class_id'] ?? null,
                    'reason' => $item['reason'] ?? null,
                    'is_applied' => false,
                ]);
            }

            return $batch;
        });

        return redirect()->route('master.promotions.show', $batch)
            ->with('success', 'Promotion batch created.');
    }

    /**
     * CSV header:
     * student_id,from_enrollment_id,action,to_class_id,reason
     *
     * @return array<int, array<string, mixed>>
     */
    private function itemsFromCsv(?UploadedFile $file): array
    {
        if (! $file) {
            return [];
        }

        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle) ?: [];
        $header = array_map(fn ($v) => strtolower(trim((string) $v)), $header);
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($header)) {
                continue;
            }

            $row = array_combine($header, $data);
            if (! is_array($row)) {
                continue;
            }

            $rows[] = [
                'student_id' => isset($row['student_id']) && $row['student_id'] !== '' ? (int) $row['student_id'] : null,
                'from_enrollment_id' => isset($row['from_enrollment_id']) && $row['from_enrollment_id'] !== '' ? (int) $row['from_enrollment_id'] : null,
                'action' => strtolower(trim((string) ($row['action'] ?? ''))),
                'to_class_id' => isset($row['to_class_id']) && $row['to_class_id'] !== '' ? (int) $row['to_class_id'] : null,
                'reason' => ($row['reason'] ?? null) ?: null,
            ];
        }

        fclose($handle);

        return $rows;
    }

    public function show(PromotionBatch $promotion): View
    {
        $promotion->load([
            'fromAcademicYear:id,code',
            'toAcademicYear:id,code',
            'creator:id,name',
            'approver:id,name',
            'applier:id,name',
            'items.student:id,name,nis',
            'items.fromEnrollment.schoolClass:id,name',
            'items.toClass:id,name',
        ]);

        return view('master.promotions.show', compact('promotion'));
    }

    public function approve(ApprovePromotionBatchRequest $request, PromotionBatch $promotion): RedirectResponse
    {
        if ($promotion->status !== 'draft') {
            return back()->withErrors(['promotion' => 'Only draft batches can be approved.']);
        }

        $promotion->update([
            'status' => 'approved',
            'approved_by' => (int) $request->user()->id,
        ]);

        return redirect()->route('master.promotions.show', $promotion)
            ->with('success', 'Promotion batch approved.');
    }

    public function apply(ApplyPromotionBatchRequest $request, PromotionBatch $promotion, PromotionService $service): RedirectResponse
    {
        $service->applyBatch((int) $promotion->id, (int) $request->user()->id);

        return redirect()->route('master.promotions.show', $promotion)
            ->with('success', 'Promotion batch applied.');
    }
}
