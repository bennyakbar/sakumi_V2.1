<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ExpenseEntry extends Model
{
    use BelongsToUnit, HasFactory, LogsActivity;

    protected $fillable = [
        'unit_id',
        'expense_fee_subcategory_id',
        'fee_type_id',
        'entry_date',
        'period_year',
        'period_month',
        'payment_method',
        'vendor_name',
        'amount',
        'estimated_amount',
        'realized_amount',
        'description',
        'receipt_path',
        'supporting_doc_path',
        'status',
        'posted_transaction_id',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'amount', 'estimated_amount', 'realized_amount',
                'vendor_name', 'description', 'payment_method', 'entry_date',
                'approved_by', 'expense_fee_subcategory_id', 'fee_type_id',
            ])
            ->logOnlyDirty();
    }

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'decimal:2',
            'estimated_amount' => 'decimal:2',
            'realized_amount' => 'decimal:2',
            'period_year' => 'integer',
            'period_month' => 'integer',
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
}
