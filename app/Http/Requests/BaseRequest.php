<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

abstract class BaseRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(
            collect($this->all())
                ->map(fn ($value) => is_string($value) ? trim(strip_tags($value)) : $value)
                ->all()
        );
    }

    protected function unitId(): ?int
    {
        return session('current_unit_id');
    }

    protected function unitExists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where('unit_id', $this->unitId());
    }

    protected function unitUnique(string $table, string $column): Unique
    {
        return Rule::unique($table, $column)->where('unit_id', $this->unitId());
    }
}
