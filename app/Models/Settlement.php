<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Settlement extends Model
{
    use BelongsToUnit, HasFactory, LogsActivity;

    protected static function booted(): void
    {
        static::deleting(function (Settlement $settlement) {
            throw new \RuntimeException(__('message.hard_delete_not_allowed'));
        });
    }

    protected $fillable = [
        'unit_id',
        'settlement_number',
        'student_id',
        'payment_date',
        'payment_method',
        'total_amount',
        'allocated_amount',
        'reference_number',
        'notes',
        'status',
        'created_by',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'total_amount' => 'decimal:2',
            'allocated_amount' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'cancellation_reason', 'void_reason'])
            ->logOnlyDirty();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SettlementAllocation::class);
    }

    public function getUnallocatedAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->allocated_amount;
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isVoided(): bool
    {
        return $this->status === 'void';
    }
}
