<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StorePromotionBatchRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->filled('items_json') && ! $this->has('items')) {
            $decoded = json_decode((string) $this->input('items_json'), true);
            if (is_array($decoded)) {
                $this->merge(['items' => $decoded]);
            }
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_academic_year_id' => ['required', $this->unitExists('academic_years')],
            'to_academic_year_id' => ['required', 'different:from_academic_year_id', $this->unitExists('academic_years')],
            'effective_date' => ['required', 'date'],
            'items' => ['nullable', 'array', 'min:1', 'required_without:items_csv'],
            'items_csv' => ['nullable', 'file', 'mimes:csv,txt', 'required_without:items'],
            'items.*.student_id' => ['required_with:items', $this->unitExists('students')],
            'items.*.from_enrollment_id' => ['required_with:items', $this->unitExists('student_enrollments')],
            'items.*.action' => ['required_with:items', Rule::in(['promote', 'retain', 'graduate'])],
            'items.*.to_class_id' => ['nullable', $this->unitExists('classes')],
            'items.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
