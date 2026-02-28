<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    protected $fillable = [
        'transaction_id',
        'invoice_id',
        'settlement_id',
        'issued_at',
        'printed_at',
        'verification_code',
        'print_count',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'printed_at' => 'datetime',
            'print_count' => 'integer',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function printLogs(): HasMany
    {
        return $this->hasMany(ReceiptPrintLog::class);
    }
}

