<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reversal extends Model
{
    use BelongsToUnit, HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'unit_id',
        'original_accounting_event_id',
        'reversal_accounting_event_id',
        'reason',
        'reversed_by',
        'reversed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'reversed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function originalEvent(): BelongsTo
    {
        return $this->belongsTo(AccountingEvent::class, 'original_accounting_event_id');
    }

    public function reversalEvent(): BelongsTo
    {
        return $this->belongsTo(AccountingEvent::class, 'reversal_accounting_event_id');
    }
}
