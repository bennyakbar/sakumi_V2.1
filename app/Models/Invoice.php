<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use BelongsToUnit, HasFactory, LogsActivity;

    protected static function booted(): void
    {
        static::deleting(function (Invoice $invoice) {
            throw new \RuntimeException(__('message.hard_delete_not_allowed'));
        });
    }

    protected $fillable = [
        'unit_id',
        'academic_year_id',
        'invoice_number',
        'student_id',
        'student_enrollment_id',
        'period_type',
        'period_identifier',
        'invoice_date',
        'due_date',
        'total_amount',
        'paid_amount',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'paid_amount', 'total_amount', 'student_id',
                'invoice_date', 'due_date', 'notes',
            ])
            ->logOnlyDirty();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SettlementAllocation::class);
    }

    public function getOutstandingAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->paid_amount;
    }

    /**
     * Recalculate paid_amount from allocations and update status accordingly.
     */
    public function recalculateFromAllocations(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        $paidAmount = $this->allocations()
            ->whereHas('settlement', fn ($q) => $q->where('status', 'completed'))
            ->sum('amount');

        $this->paid_amount = $paidAmount;

        if ($paidAmount <= 0) {
            $this->status = 'unpaid';
        } elseif ($paidAmount < (float) $this->total_amount) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'paid';
        }

        $this->save();
    }
}
