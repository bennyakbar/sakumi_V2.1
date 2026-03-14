<?php

namespace App\Services;

use App\Models\FinancialEventLog;
use Illuminate\Database\Eloquent\Model;

class FinancialEventLogger
{
    /**
     * Record a financial business event.
     *
     * Lightweight and non-blocking: failures are silently caught
     * so financial operations are never interrupted by logging issues.
     */
    public function record(Model|string $entity, string $event, array $metadata = [], ?int $userId = null): void
    {
        try {
            $entityType = $entity instanceof Model
                ? $this->resolveEntityType($entity)
                : (string) $entity;

            $entityId = $entity instanceof Model
                ? (int) $entity->getKey()
                : (int) ($metadata['entity_id'] ?? 0);

            FinancialEventLog::create([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'event' => $event,
                'user_id' => $userId ?? (int) auth()->id(),
                'metadata' => $metadata ?: null,
                'ip_address' => request()?->ip(),
            ]);
        } catch (\Throwable) {
            // Silent catch — logging must never block financial operations.
            // In production, this could forward to error monitoring (Sentry, etc.)
        }
    }

    private function resolveEntityType(Model $entity): string
    {
        return match (true) {
            $entity instanceof \App\Models\ExpenseEntry => 'expense',
            $entity instanceof \App\Models\Invoice => 'invoice',
            $entity instanceof \App\Models\Settlement => 'settlement',
            $entity instanceof \App\Models\Transaction => 'transaction',
            default => strtolower(class_basename($entity)),
        };
    }
}
