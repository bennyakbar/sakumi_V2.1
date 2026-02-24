<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\BaseRequest;
use App\Models\StudentFeeMapping;
use Illuminate\Validation\Validator;

class UpdateStudentFeeMappingRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var StudentFeeMapping|null $mapping */
            $mapping = $this->route('studentFeeMapping');
            $student = $this->route('student');
            if (!$mapping || !$student) {
                return;
            }

            $from = $this->date('effective_from');
            $to = $this->date('effective_to');

            $overlapExists = StudentFeeMapping::query()
                ->join('fee_matrix', 'fee_matrix.id', '=', 'student_fee_mappings.fee_matrix_id')
                ->where('student_fee_mappings.student_id', $student->id)
                ->where('student_fee_mappings.id', '!=', $mapping->id)
                ->where('student_fee_mappings.is_active', true)
                ->where('fee_matrix.fee_type_id', $mapping->feeMatrix->fee_type_id)
                ->whereDate('student_fee_mappings.effective_from', '<=', ($to ?? $from)->toDateString())
                ->where(function ($q) use ($from) {
                    $q->whereNull('student_fee_mappings.effective_to')
                        ->orWhereDate('student_fee_mappings.effective_to', '>=', $from->toDateString());
                })
                ->exists();

            if ($overlapExists) {
                $validator->errors()->add('effective_from', __('message.student_fee_mapping_overlap'));
            }
        });
    }
}
