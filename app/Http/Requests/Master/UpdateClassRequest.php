<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateClassRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $classId = $this->route('class')?->id ?? $this->route('class');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                $this->unitUnique('classes', 'name')->ignore($classId)->where('academic_year', $this->input('academic_year')),
            ],
            'level' => ['required', 'integer', 'between:1,6'],
            'academic_year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
