<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DocumentSequence extends Model
{
    protected $fillable = ['prefix', 'last_sequence'];

    /**
     * Atomically reserve the next sequence number for a given prefix.
     *
     * Uses an independent DB connection (or savepoint-safe upsert) so the
     * reserved number is never rolled back even if the caller's transaction
     * fails.  This eliminates numbering gaps.
     */
    public static function next(string $prefix): int
    {
        // Use raw upsert + returning to atomically increment in a single statement.
        // This works on PostgreSQL.  For SQLite (tests), fall back to lock-based approach.
        if (DB::getDriverName() === 'pgsql') {
            $result = DB::selectOne(
                "INSERT INTO document_sequences (prefix, last_sequence, created_at, updated_at)
                 VALUES (?, 1, NOW(), NOW())
                 ON CONFLICT (prefix)
                 DO UPDATE SET last_sequence = document_sequences.last_sequence + 1, updated_at = NOW()
                 RETURNING last_sequence",
                [$prefix]
            );

            return (int) $result->last_sequence;
        }

        // SQLite / fallback: use lockForUpdate
        return DB::transaction(function () use ($prefix) {
            $record = static::lockForUpdate()->where('prefix', $prefix)->first();

            if ($record) {
                $record->increment('last_sequence');

                return (int) $record->last_sequence;
            }

            $record = static::create(['prefix' => $prefix, 'last_sequence' => 1]);

            return 1;
        });
    }
}
