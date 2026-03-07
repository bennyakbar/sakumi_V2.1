<?php

namespace App\Services;

/**
 * Request-scoped unit context.
 *
 * Replaces direct session('current_unit_id') reads with a container-bound
 * service.  This decouples tenant resolution from PHP sessions, enabling:
 *   - Horizontal scaling without sticky sessions (use Redis/cookie sessions)
 *   - Stateless API auth (resolve from JWT claim or header)
 *   - Queue workers (set explicitly before dispatching)
 *   - CLI commands (set explicitly per unit iteration)
 *
 * Registered as a scoped singleton in AppServiceProvider so it resets per request.
 */
class UnitContext
{
    private ?int $unitId = null;

    /**
     * Resolve the current unit ID.
     *
     * Priority: explicit set > session > null (fail-closed in BelongsToUnit).
     */
    public function id(): ?int
    {
        return $this->unitId ?? session('current_unit_id');
    }

    /**
     * Explicitly set the unit context (middleware, CLI, queue).
     */
    public function set(?int $unitId): void
    {
        $this->unitId = $unitId;

        // Keep session in sync for backward compatibility with views/middleware
        // that still read session directly.
        if ($unitId !== null) {
            session(['current_unit_id' => $unitId]);
        }
    }

    /**
     * Clear the unit context.
     */
    public function clear(): void
    {
        $this->unitId = null;
        session()->forget('current_unit_id');
    }
}
