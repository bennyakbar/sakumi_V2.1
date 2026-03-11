<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ExpenseBudget extends Model
{
    use BelongsToUnit, HasFactory, LogsActivity;

    protected $fillable = [
        'unit_id',
        'year',
        'month',
        'expense_fee_subcategory_id',
        'budget_amount',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'budget_amount', 'expense_fee_subcategory_id',
                'year', 'month', 'notes',
            ])
            ->logOnlyDirty();
    }

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'budget_amount' => 'decimal:2',
        ];
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseFeeSubcategory::class, 'expense_fee_subcategory_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
