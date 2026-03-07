<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingEvent extends Model
{
    use BelongsToUnit, HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'unit_id',
        'event_uuid',
        'event_type',
        'source_type',
        'source_id',
        'idempotency_key',
        'effective_date',
        'occurred_at',
        'fiscal_period_id',
        'is_reversal',
        'reversal_of_event_id',
        'status',
        'created_by',
        'payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'occurred_at' => 'datetime',
            'is_reversal' => 'boolean',
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('AccountingEvent is immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('AccountingEvent is immutable and cannot be deleted.');
        });
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class, 'fiscal_period_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(JournalEntryV2::class, 'accounting_event_id');
    }
}
