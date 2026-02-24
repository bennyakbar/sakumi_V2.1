<?php

namespace App\Http\Controllers\Master;

use App\Exports\StudentExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ImportStudentRequest;
use App\Http\Requests\Master\StoreStudentRequest;
use App\Http\Requests\Master\UpdateStudentRequest;
use App\Imports\StudentImport;
use App\Models\SchoolClass;
use App\Models\FeeMatrix;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Services\PermanentDeleteService;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function index(): View
    {
        $sort = request('sort', 'latest');

        $studentsQuery = Student::query()
            ->with(['schoolClass:id,name', 'category:id,name'])
            ->when(request('search'), function ($query, string $search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('nis', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when(request('class_id'), fn ($query, $classId) => $query->where('class_id', $classId))
            ->when(request('category_id'), fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when(request('status'), fn ($query, $status) => $query->where('status', $status));

        match ($sort) {
            'oldest' => $studentsQuery->oldest(),
            'name_asc' => $studentsQuery->orderBy('name'),
            'name_desc' => $studentsQuery->orderByDesc('name'),
            'nis_asc' => $studentsQuery->orderBy('nis'),
            'nis_desc' => $studentsQuery->orderByDesc('nis'),
            'status_asc' => $studentsQuery->orderBy('status')->orderBy('name'),
            'status_desc' => $studentsQuery->orderByDesc('status')->orderBy('name'),
            default => $studentsQuery->latest(),
        };

        $students = $studentsQuery
            ->paginate(15)
            ->withQueryString();

        $classes = SchoolClass::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $categories = StudentCategory::query()->orderBy('name')->get(['id', 'name']);

        return view('master.students.index', compact('students', 'classes', 'categories', 'sort'));
    }

    public function import(): View
    {
        return view('master.students.import');
    }

    public function downloadTemplate()
    {
        return response()->download(base_path('database/schema/student-import-template.csv'));
    }

    public function processImport(ImportStudentRequest $request): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $import = new StudentImport();

        if (in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'], true)) {
            $rows = $this->rowsFromCsv($file);
            $import->collection($rows);
        } else {
            $import->import($file);
        }

        return redirect()->route('master.students.index')
            ->with('success', __('message.student_import_success'))
            ->with('error_list', $import->errors);
    }

    public function export()
    {
        return Excel::download(new StudentExport(), 'students.xlsx');
    }

    public function create(): View
    {
        $classes = SchoolClass::query()->where('is_active', true)->orderBy('name')->get();
        $categories = StudentCategory::query()->orderBy('name')->get();

        return view('master.students.create', compact('classes', 'categories'));
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        Student::create($request->validated());

        return redirect()->route('master.students.index')
            ->with('success', __('message.student_created'));
    }

    public function show(Student $student): View
    {
        $student->load([
            'schoolClass:id,name',
            'category:id,name',
            'feeMappings.feeMatrix.feeType',
            'feeMappings.feeMatrix.schoolClass',
            'feeMappings.feeMatrix.category',
        ]);
        $feeMatrices = FeeMatrix::query()
            ->with(['feeType', 'schoolClass', 'category'])
            ->where('is_active', true)
            ->orderByDesc('effective_from')
            ->limit(200)
            ->get();

        return view('master.students.show', compact('student', 'feeMatrices'));
    }

    public function edit(Student $student): View
    {
        $classes = SchoolClass::query()->where('is_active', true)->orderBy('name')->get();
        $categories = StudentCategory::query()->orderBy('name')->get();

        return view('master.students.edit', compact('student', 'classes', 'categories'));
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $student->update($request->validated());

        return redirect()->route('master.students.index')
            ->with('success', __('message.student_updated'));
    }

    public function destroy(Request $request, Student $student): RedirectResponse
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
                $permanentDelete->dependencyCounts(PermanentDeleteService::ENTITY_STUDENT, (int) $student->id)
            );
            if (!empty($blocking)) {
                $permanentDelete->logSnapshot($actor, PermanentDeleteService::ENTITY_STUDENT, $student, $blocking, 'blocked');
                return back()->withErrors([
                    'delete' => __('message.permanent_delete_blocked_dependencies', [
                        'details' => $permanentDelete->formatDependencies($blocking),
                    ]),
                ]);
            }

            try {
                $permanentDelete->logSnapshot($actor, PermanentDeleteService::ENTITY_STUDENT, $student, [], 'attempt');
                $student->forceDelete();
            } catch (QueryException $e) {
                $permanentDelete->logSnapshot($actor, PermanentDeleteService::ENTITY_STUDENT, $student, [], 'failed', $e->getMessage());
                return back()->withErrors(['delete' => __('message.permanent_delete_failed_fk')]);
            }

            return redirect()->route('master.students.index')
                ->with('success', __('message.student_permanently_deleted'));
        }

        $student->delete();

        return redirect()->route('master.students.index')
            ->with('success', __('message.student_deleted'));
    }

    private function rowsFromCsv(UploadedFile $file): Collection
    {
        $rows = collect();
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            return $rows;
        }

        $header = fgetcsv($handle) ?: [];

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($header)) {
                continue;
            }

            $rows->push(collect(array_combine($header, $data)));
        }

        fclose($handle);

        return $rows;
    }
}
