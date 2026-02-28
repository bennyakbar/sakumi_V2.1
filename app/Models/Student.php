<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Student extends Model
{
    use BelongsToUnit, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'nis',
        'nisn',
        'name',
        'class_id',
        'category_id',
        'gender',
        'birth_date',
        'birth_place',
        'parent_name',
        'parent_phone',
        'parent_whatsapp',
        'address',
        'status',
        'enrollment_date',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'enrollment_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty();
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StudentCategory::class, 'category_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function obligations(): HasMany
    {
        return $this->hasMany(StudentObligation::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function feeMappings(): HasMany
    {
        return $this->hasMany(StudentFeeMapping::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function applicant(): HasOne
    {
        return $this->hasOne(Applicant::class);
    }
}
