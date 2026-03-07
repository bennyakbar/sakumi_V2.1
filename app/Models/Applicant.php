<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Applicant extends Model
{
    use BelongsToUnit, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'admission_period_id',
        'registration_number',
        'name',
        'target_class_id',
        'category_id',
        'gender',
        'birth_date',
        'birth_place',
        'parent_name',
        'parent_phone',
        'parent_whatsapp',
        'address',
        'previous_school',
        'status',
        'rejection_reason',
        'status_changed_at',
        'status_changed_by',
        'student_id',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'status_changed_at' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty();
    }

    public function admissionPeriod(): BelongsTo
    {
        return $this->belongsTo(AdmissionPeriod::class);
    }

    public function targetClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'target_class_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StudentCategory::class, 'category_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusChanger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }
}
