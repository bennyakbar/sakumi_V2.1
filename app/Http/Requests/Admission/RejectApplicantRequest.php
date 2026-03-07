<?php

namespace App\Http\Requests\Admission;

use App\Http\Requests\BaseRequest;

class RejectApplicantRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
