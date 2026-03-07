<?php

namespace App\Http\Requests\Admission;

use App\Http\Requests\BaseRequest;

class EnrollApplicantRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nis' => ['nullable', 'string', 'max:20', $this->unitUnique('students', 'nis')],
        ];
    }
}
