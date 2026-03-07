<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryV2 extends Model
{
    use BelongsToUnit, HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'journal_entries_v2';

    protected $fillable = [
        'unit_id',
        'accounting_event_id',
        'line_no',
        'entry_date',
        'account_id',
        'account_code',
        'description',
        'debit',
        'credit',
        'currency',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'line_no' => 'integer',
            'entry_date' => 'date',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('JournalEntryV2 is immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('JournalEntryV2 is immutable and cannot be deleted.');
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(AccountingEvent::class, 'accounting_event_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
