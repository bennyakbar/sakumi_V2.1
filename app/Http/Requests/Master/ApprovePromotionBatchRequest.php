<?php

namespace App\Http\Requests\Master;

use App\Http\Requests\BaseRequest;

class ApprovePromotionBatchRequest extends BaseRequest
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
