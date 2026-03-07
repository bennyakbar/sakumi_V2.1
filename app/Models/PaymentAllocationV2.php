<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocationV2 extends Model
{
    use BelongsToUnit, HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'payment_allocations_v2';

    protected $fillable = [
        'unit_id',
        'accounting_event_id',
        'payment_source_type',
        'payment_source_id',
        'invoice_id',
        'allocated_amount',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(AccountingEvent::class, 'accounting_event_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
