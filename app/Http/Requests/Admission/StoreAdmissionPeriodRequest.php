<?php

namespace App\Http\Requests\Admission;

use App\Http\Requests\BaseRequest;

class StoreAdmissionPeriodRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'academic_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}\/\d{4}$/'],
            'registration_open' => ['required', 'date'],
            'registration_close' => ['required', 'date', 'after_or_equal:registration_open'],
            'status' => ['required', 'in:draft,open,closed'],
            'notes' => ['nullable', 'string'],
            'quotas' => ['nullable', 'array'],
            'quotas.*.class_id' => ['required_with:quotas', $this->unitExists('classes')],
            'quotas.*.quota' => ['required_with:quotas', 'integer', 'min:1'],
        ];
    }
}
