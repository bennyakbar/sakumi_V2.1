<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreStudentFeeMappingRequest;
use App\Http\Requests\Master\UpdateStudentFeeMappingRequest;
use App\Models\Student;
use App\Models\StudentFeeMapping;
use Illuminate\Http\RedirectResponse;

class StudentFeeMappingController extends Controller
{
    public function store(StoreStudentFeeMappingRequest $request, Student $student): RedirectResponse
    {
        StudentFeeMapping::create([
            'student_id' => $student->id,
            'fee_matrix_id' => (int) $request->integer('fee_matrix_id'),
            'effective_from' => (string) $request->input('effective_from'),
            'effective_to' => $request->input('effective_to') ?: null,
            'is_active' => $request->boolean('is_active', true),
            'notes' => $request->input('notes'),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('master.students.show', $student)
            ->with('success', __('message.student_fee_mapping_created'));
    }

    public function update(UpdateStudentFeeMappingRequest $request, Student $student, StudentFeeMapping $studentFeeMapping): RedirectResponse
    {
        abort_unless($studentFeeMapping->student_id === $student->id, 404);

        $studentFeeMapping->update([
            'effective_from' => (string) $request->input('effective_from'),
            'effective_to' => $request->input('effective_to') ?: null,
            'is_active' => $request->boolean('is_active'),
            'notes' => $request->input('notes'),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('master.students.show', $student)
            ->with('success', __('message.student_fee_mapping_updated'));
    }

    public function destroy(Student $student, StudentFeeMapping $studentFeeMapping): RedirectResponse
    {
        abort_unless($studentFeeMapping->student_id === $student->id, 404);

        $studentFeeMapping->update([
            'is_active' => false,
            'effective_to' => $studentFeeMapping->effective_to ?: now()->toDateString(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('master.students.show', $student)
            ->with('success', __('message.student_fee_mapping_deactivated'));
    }
}
