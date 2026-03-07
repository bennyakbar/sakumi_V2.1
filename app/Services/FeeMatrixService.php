<?php

namespace App\Services;

use App\Models\FeeMatrix;
use Carbon\CarbonInterface;

class FeeMatrixService
{
    public function getFeeMatrix(int $feeTypeId, ?int $classId, ?int $categoryId, ?CarbonInterface $date = null): ?FeeMatrix
    {
        $effectiveDate = ($date ?? now())->toDateString();

        return FeeMatrix::query()
            ->where('fee_type_id', $feeTypeId)
            ->where('is_active', true)
            ->where(function ($query) use ($classId) {
                if ($classId === null) {
                    $query->whereNull('class_id');

                    return;
                }

                $query->where('class_id', $classId)
                    ->orWhereNull('class_id');
            })
            ->where(function ($query) use ($categoryId) {
                if ($categoryId === null) {
                    $query->whereNull('category_id');

                    return;
                }

                $query->where('category_id', $categoryId)
                    ->orWhereNull('category_id');
            })
            ->whereDate('effective_from', '<=', $effectiveDate)
            ->where(function ($query) use ($effectiveDate) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $effectiveDate);
            })
            ->orderByRaw('CASE WHEN class_id IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByRaw('CASE WHEN category_id IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByDesc('effective_from')
            ->first();
    }
}
