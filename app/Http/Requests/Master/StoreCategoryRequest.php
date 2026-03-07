<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'code' => strtoupper((string) $this->input('code')),
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', $this->unitUnique('student_categories', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'discount_percentage' => ['required', 'numeric', 'between:0,100'],
        ];
    }
}
