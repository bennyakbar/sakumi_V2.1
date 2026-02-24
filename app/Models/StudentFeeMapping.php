<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeeMapping extends Model
{
    use BelongsToUnit, HasFactory;

    protected $fillable = [
        'unit_id',
        'student_id',
        'fee_matrix_id',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeMatrix(): BelongsTo
    {
        return $this->belongsTo(FeeMatrix::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isActiveOn(Carbon $date): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->effective_from->gt($date)) {
            return false;
        }

        return $this->effective_to === null || $this->effective_to->gte($date);
    }
}

