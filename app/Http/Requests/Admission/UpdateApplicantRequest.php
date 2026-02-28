<?php

namespace App\Http\Requests\Admission;

use App\Http\Requests\BaseRequest;

class UpdateApplicantRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admission_period_id' => ['required', $this->unitExists('admission_periods')],
            'name' => ['required', 'string', 'max:255'],
            'target_class_id' => ['required', $this->unitExists('classes')],
            'category_id' => ['required', $this->unitExists('student_categories')],
            'gender' => ['required', 'in:L,P'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:20'],
            'parent_whatsapp' => ['nullable', 'regex:/^628\d{7,15}$/'],
            'address' => ['nullable', 'string'],
            'previous_school' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
