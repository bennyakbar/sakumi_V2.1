<?php

namespace App\Models\Concerns;

use App\Models\Unit;
use App\Services\UnitContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToUnit
{
    public static function bootBelongsToUnit(): void
    {
        static::addGlobalScope('unit', function (Builder $builder): void {
            $unitId = app(UnitContext::class)->id();

            if ($unitId) {
                $builder->where(
                    $builder->getModel()->qualifyColumn('unit_id'),
                    $unitId
                );

                return;
            }

            // Fail-closed: if no unit context is set and we're in an HTTP
            // request (not CLI/queue), force an impossible WHERE so that
            // no records are returned.  This prevents cross-unit data leakage
            // when sessions expire or middleware is bypassed.
            if (! app()->runningInConsole()) {
                $builder->whereRaw('1 = 0');
            }
        });

        static::creating(function (Model $model): void {
            if (! $model->unit_id) {
                $unitId = app(UnitContext::class)->id();
                if ($unitId) {
                    $model->unit_id = $unitId;
                }
            }
        });
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
