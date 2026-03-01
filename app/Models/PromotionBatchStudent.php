<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionBatchStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_batch_id',
        'student_id',
        'from_enrollment_id',
        'action',
        'to_class_id',
        'reason',
        'is_applied',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'is_applied' => 'boolean',
            'applied_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PromotionBatch::class, 'promotion_batch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'from_enrollment_id');
    }

    public function toClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'to_class_id');
    }
}
