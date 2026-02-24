<?php

namespace App\Services;

use App\Models\AccountMapping;
use App\Models\AccountingEvent;
use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\JournalEntryV2;
use App\Models\PaymentAllocationV2;
use App\Models\Reversal;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountingEngine
{
    public static function fromEvent(string $eventType, array $payload): void
    {
        app(self::class)->post($eventType, $payload);
    }

    public function post(string $eventType, array $payload): void
    {
        DB::transaction(function () use ($eventType, $payload): void {
            $idempotencyKey = $payload['idempotency_key'] ?? null;
            if ($idempotencyKey) {
                $exists = AccountingEvent::query()->where('idempotency_key', $idempotencyKey)->exists();
                if ($exists) {
                    return;
                }
            }

            $unitId = (int) ($payload['unit_id'] ?? session('current_unit_id'));
            if ($unitId <= 0) {
                throw new \RuntimeException('Unit context is required to post accounting events.');
            }

            $effectiveDate = CarbonImmutable::parse($payload['effective_date'] ?? now()->toDateString())->toDateString();
            $period = $this->resolveOpenPeriod($unitId, $effectiveDate);

            if ($eventType === 'reversal.posted') {
                $this->postReversal($unitId, $period->id, $effectiveDate, $payload);
                return;
            }

            $event = AccountingEvent::query()->create([
                'unit_id' => $unitId,
                'event_uuid' => (string) Str::uuid(),
                'event_type' => $eventType,
                'source_type' => $payload['source_type'] ?? null,
                'source_id' => isset($payload['source_id']) ? (int) $payload['source_id'] : null,
                'idempotency_key' => $idempotencyKey,
                'effective_date' => $effectiveDate,
                'occurred_at' => CarbonImmutable::parse($payload['occurred_at'] ?? now()),
                'fiscal_period_id' => $period->id,
                'status' => 'posted',
                'created_by' => isset($payload['created_by']) ? (int) $payload['created_by'] : null,
                'payload' => $payload,
            ]);

            $lineAmounts = $this->extractLineAmounts($eventType, $payload);
            $entryRows = $this->mapToEntries($unitId, $eventType, $lineAmounts);
            $this->assertBalanced($entryRows);
            $this->insertJournalEntries($event, $effectiveDate, $entryRows, $payload);
            $this->storePaymentAllocations($event, $payload);
        });
    }

    private function resolveOpenPeriod(int $unitId, string $effectiveDate): FiscalPeriod
    {
        $this->assertWithinAcademicYear($effectiveDate);

        $period = FiscalPeriod::query()
            ->where('unit_id', $unitId)
            ->whereDate('starts_on', '<=', $effectiveDate)
            ->whereDate('ends_on', '>=', $effectiveDate)
            ->lockForUpdate()
            ->first();

        if (! $period) {
            $date = CarbonImmutable::parse($effectiveDate);
            $period = FiscalPeriod::query()->create([
                'unit_id' => $unitId,
                'period_key' => $date->format('Y-m'),
                'starts_on' => $date->startOfMonth()->toDateString(),
                'ends_on' => $date->endOfMonth()->toDateString(),
                'is_locked' => false,
            ]);
        }

        if ($period->is_locked) {
            throw new \RuntimeException("Fiscal period {$period->period_key} is locked.");
        }

        return $period;
    }

    private function assertWithinAcademicYear(string $effectiveDate): void
    {
        $academicYear = (string) Setting::get('academic_year_current', '');
        if (!preg_match('/^(\d{4})\/(\d{4})$/', $academicYear, $matches)) {
            throw new \RuntimeException('Setting academic_year_current must be configured in YYYY/YYYY format before posting accounting events.');
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];
        if ($endYear !== $startYear + 1) {
            throw new \RuntimeException("Setting academic_year_current is invalid: {$academicYear}. Expected consecutive years like 2025/2026.");
        }

        $start = CarbonImmutable::create($startYear, 7, 1)->startOfDay();
        $end = CarbonImmutable::create($endYear, 6, 30)->endOfDay();
        $date = CarbonImmutable::parse($effectiveDate)->startOfDay();

        if ($date->lt($start) || $date->gt($end)) {
            throw new \RuntimeException("Effective date {$effectiveDate} is outside active academic year {$academicYear}.");
        }
    }

    private function postReversal(int $unitId, int $periodId, string $effectiveDate, array $payload): void
    {
        $originalEventId = (int) ($payload['original_event_id'] ?? 0);
        if ($originalEventId > 0) {
            $original = AccountingEvent::query()
                ->where('unit_id', $unitId)
                ->with('entries')
                ->findOrFail($originalEventId);
        } else {
            $sourceType = $payload['source_type'] ?? null;
            $sourceId = isset($payload['source_id']) ? (int) $payload['source_id'] : 0;
            $original = AccountingEvent::query()
                ->where('unit_id', $unitId)
                ->where('is_reversal', false)
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->with('entries')
                ->latest('id')
                ->first();

            if (! $original) {
                throw new \RuntimeException('Unable to resolve original accounting event for reversal.');
            }
        }

        if (Reversal::query()->where('original_accounting_event_id', $original->id)->exists()) {
            return;
        }

        $event = AccountingEvent::query()->create([
            'unit_id' => $unitId,
            'event_uuid' => (string) Str::uuid(),
            'event_type' => 'reversal.posted',
            'source_type' => $payload['source_type'] ?? $original->source_type,
            'source_id' => isset($payload['source_id']) ? (int) $payload['source_id'] : $original->source_id,
            'idempotency_key' => $payload['idempotency_key'] ?? null,
            'effective_date' => $effectiveDate,
            'occurred_at' => CarbonImmutable::parse($payload['occurred_at'] ?? now()),
            'fiscal_period_id' => $periodId,
            'is_reversal' => true,
            'reversal_of_event_id' => $original->id,
            'status' => 'posted',
            'created_by' => isset($payload['created_by']) ? (int) $payload['created_by'] : null,
            'payload' => $payload,
        ]);

        $entries = [];
        foreach ($original->entries as $line) {
            $entries[] = [
                'account_id' => $line->account_id,
                'account_code' => $line->account_code,
                'description' => 'REVERSAL: ' . ($line->description ?? 'Auto reversal'),
                'debit' => (float) $line->credit,
                'credit' => (float) $line->debit,
                'meta' => ['reversed_from_journal_entry_id' => $line->id],
            ];
        }

        $this->assertBalanced($entries);
        $this->insertJournalEntries($event, $effectiveDate, $entries, $payload);

        Reversal::query()->create([
            'unit_id' => $unitId,
            'original_accounting_event_id' => $original->id,
            'reversal_accounting_event_id' => $event->id,
            'reason' => $payload['reason'] ?? null,
            'reversed_by' => isset($payload['created_by']) ? (int) $payload['created_by'] : null,
            'reversed_at' => CarbonImmutable::parse($payload['occurred_at'] ?? now()),
        ]);
    }

    private function extractLineAmounts(string $eventType, array $payload): array
    {
        $amount = (float) ($payload['total_amount'] ?? 0);
        if ($amount <= 0) {
            throw new \RuntimeException("total_amount must be > 0 for {$eventType}.");
        }

        return match ($eventType) {
            'invoice.created' => [
                'receivable' => $amount,
                'revenue' => $amount,
            ],
            'payment.posted', 'settlement.applied' => [
                'cash' => $amount,
                'receivable' => $amount,
            ],
            'payment.direct.posted' => [
                'cash' => $amount,
                'revenue' => $amount,
            ],
            'expense.posted' => [
                'expense' => $amount,
                'cash' => $amount,
            ],
            default => throw new \RuntimeException("Unsupported accounting event type: {$eventType}."),
        };
    }

    private function mapToEntries(int $unitId, string $eventType, array $lineAmounts): array
    {
        $mappings = AccountMapping::query()
            ->where('unit_id', $unitId)
            ->where('event_type', $eventType)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        if ($mappings->isEmpty()) {
            throw new \RuntimeException("No account mapping configured for event type {$eventType}.");
        }

        $entries = [];
        foreach ($mappings as $mapping) {
            $amount = (float) ($lineAmounts[$mapping->line_key] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $account = ChartOfAccount::query()
                ->where('unit_id', $unitId)
                ->where('code', $mapping->account_code)
                ->where('is_active', true)
                ->first();

            if (! $account) {
                throw new \RuntimeException("Mapped account code {$mapping->account_code} is not configured in chart_of_accounts.");
            }

            $entries[] = [
                'account_id' => $account->id,
                'account_code' => $account->code,
                'description' => $mapping->description,
                'debit' => $mapping->entry_side === 'debit' ? $amount : 0.0,
                'credit' => $mapping->entry_side === 'credit' ? $amount : 0.0,
                'meta' => [
                    'event_type' => $eventType,
                    'line_key' => $mapping->line_key,
                    'mapping_id' => $mapping->id,
                ],
            ];
        }

        if (empty($entries)) {
            throw new \RuntimeException("No journal entries generated from mappings for event type {$eventType}.");
        }

        return $entries;
    }

    private function assertBalanced(array $entries): void
    {
        $debit = round(array_sum(array_map(fn (array $row): float => (float) ($row['debit'] ?? 0), $entries)), 2);
        $credit = round(array_sum(array_map(fn (array $row): float => (float) ($row['credit'] ?? 0), $entries)), 2);

        if ($debit !== $credit) {
            throw new \RuntimeException("Journal imbalance detected: debit {$debit} != credit {$credit}.");
        }
    }

    private function insertJournalEntries(AccountingEvent $event, string $entryDate, array $entries, array $payload): void
    {
        $now = now();
        $rows = [];

        foreach (array_values($entries) as $idx => $row) {
            $rows[] = [
                'unit_id' => $event->unit_id,
                'accounting_event_id' => $event->id,
                'line_no' => $idx + 1,
                'entry_date' => $entryDate,
                'account_id' => $row['account_id'],
                'account_code' => $row['account_code'],
                'description' => $row['description'] ?? null,
                'debit' => (float) ($row['debit'] ?? 0),
                'credit' => (float) ($row['credit'] ?? 0),
                'currency' => $payload['currency'] ?? 'IDR',
                'meta' => json_encode($row['meta'] ?? [], JSON_THROW_ON_ERROR),
                'created_at' => $now,
            ];
        }

        JournalEntryV2::query()->insert($rows);
    }

    private function storePaymentAllocations(AccountingEvent $event, array $payload): void
    {
        $allocations = $payload['allocations'] ?? null;
        if (! is_array($allocations) || empty($allocations)) {
            return;
        }

        $sourceType = (string) ($payload['source_type'] ?? 'payment');
        $sourceId = (int) ($payload['source_id'] ?? 0);

        foreach ($allocations as $allocation) {
            $amount = (float) ($allocation['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            PaymentAllocationV2::query()->create([
                'unit_id' => $event->unit_id,
                'accounting_event_id' => $event->id,
                'payment_source_type' => $sourceType,
                'payment_source_id' => $sourceId,
                'invoice_id' => (int) $allocation['invoice_id'],
                'allocated_amount' => $amount,
                'meta' => $allocation,
                'created_at' => now(),
            ]);
        }
    }
}
