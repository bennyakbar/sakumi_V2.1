<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use BelongsToUnit, HasFactory, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'student_id',
        'type',
        'message',
        'recipient_phone',
        'whatsapp_status',
        'whatsapp_sent_at',
        'whatsapp_response',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'whatsapp_sent_at' => 'datetime',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
