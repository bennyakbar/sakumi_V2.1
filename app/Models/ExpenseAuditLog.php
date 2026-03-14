<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'expense_entry_id',
        'user_id',
        'event_type',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function expenseEntry(): BelongsTo
    {
        return $this->belongsTo(ExpenseEntry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
