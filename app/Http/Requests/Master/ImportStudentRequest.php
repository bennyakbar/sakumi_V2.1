<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\BaseRequest;

class ImportStudentRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ];
    }
}
