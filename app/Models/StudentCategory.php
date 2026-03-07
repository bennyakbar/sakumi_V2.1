<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentCategory extends Model
{
    use BelongsToUnit, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'code',
        'name',
        'description',
        'discount_percentage',
    ];

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty();
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'category_id');
    }

    public function feeMatrix(): HasMany
    {
        return $this->hasMany(FeeMatrix::class, 'category_id');
    }
}
