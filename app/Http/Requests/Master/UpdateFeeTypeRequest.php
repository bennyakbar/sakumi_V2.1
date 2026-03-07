<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateFeeTypeRequest extends BaseRequest
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
        $feeTypeId = $this->route('fee_type')?->id ?? $this->route('fee_type');

        return [
            'code' => ['required', 'string', 'max:20', $this->unitUnique('fee_types', 'code')->ignore($feeTypeId)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_monthly' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
