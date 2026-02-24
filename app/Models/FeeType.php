<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FeeType extends Model
{
    use BelongsToUnit, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'expense_fee_subcategory_id',
        'code',
        'name',
        'description',
        'is_monthly',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_monthly' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty();
    }

    public function feeMatrix(): HasMany
    {
        return $this->hasMany(FeeMatrix::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function obligations(): HasMany
    {
        return $this->hasMany(StudentObligation::class);
    }

    public function expenseFeeSubcategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseFeeSubcategory::class, 'expense_fee_subcategory_id');
    }
}
