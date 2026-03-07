<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\BaseRequest;

class ApplyPromotionBatchRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
