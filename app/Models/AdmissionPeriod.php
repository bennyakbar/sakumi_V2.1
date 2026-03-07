<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AdmissionPeriod extends Model
{
    use BelongsToUnit, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'name',
        'academic_year',
        'registration_open',
        'registration_close',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'registration_open' => 'date',
            'registration_close' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty();
    }

    public function quotas(): HasMany
    {
        return $this->hasMany(AdmissionPeriodQuota::class);
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }
}
