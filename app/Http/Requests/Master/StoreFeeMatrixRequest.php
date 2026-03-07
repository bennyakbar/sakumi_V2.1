<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\BaseRequest;

class StoreFeeMatrixRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fee_type_id' => ['required', $this->unitExists('fee_types')],
            'class_id' => ['nullable', $this->unitExists('classes')],
            'category_id' => ['nullable', $this->unitExists('student_categories')],
            'amount' => ['required', 'numeric', 'gt:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
