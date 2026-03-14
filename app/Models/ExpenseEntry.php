<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseEntry extends Model
{
    use BelongsToUnit, HasFactory;

    protected $fillable = [
        'unit_id',
        'expense_fee_subcategory_id',
        'fee_type_id',
        'entry_date',
        'payment_method',
        'vendor_name',
        'amount',
        'description',
        'internal_notes',
        'status',
        'posted_transaction_id',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseFeeSubcategory::class, 'expense_fee_subcategory_id');
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class, 'fee_type_id');
    }

    public function postedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'posted_transaction_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ExpenseAttachment::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ExpenseAuditLog::class);
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['posted', 'reversed', 'cancelled']);
    }
}
