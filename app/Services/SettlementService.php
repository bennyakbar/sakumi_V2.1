<?php

namespace App\Services;

use App\Models\DocumentSequence;
use App\Models\Invoice;
use App\Models\Settlement;
use App\Models\SettlementAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettlementService
{
    public function __construct(
        private readonly FinancialEventLogger $financialEventLogger,
    ) {
    }

    public function generateSettlementNumber(): string
    {
        $year = now()->year;
        $seqPrefix = "STL-{$year}";

        $this->syncSettlementSequenceFloor($seqPrefix, $year);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $sequence = DocumentSequence::next($seqPrefix);
            $candidate = sprintf('STL-%s-%06d', $year, $sequence);

            // Guard against stale sequence state after migrations/backfills.
            if (! Settlement::query()->where('settlement_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Failed to reserve unique settlement number after retries.');
    }

    /**
     * Create a settlement with allocations to invoices.
     *
     * @param  array  $data  Settlement data (student_id, payment_date, payment_method, total_amount, reference_number, notes)
     * @param  array  $allocations  Array of [invoice_id => amount]
     */
    public function createSettlement(array $data, array $allocations, int $userId): Settlement
    {
        // Phase 2: Early validation — at least one allocation with amount > 0
        if (empty($allocations) || array_sum($allocations) <= 0) {
            throw new \InvalidArgumentException(__('message.settlement_min_allocation'));
        }

        return DB::transaction(function () use ($data, $allocations, $userId) {
            $number = $this->generateSettlementNumber();

            $totalAllocated = array_sum($allocations);

            // BR-06: Total allocation must not exceed settlement amount
            if ($totalAllocated > (float) $data['total_amount']) {
                throw new \RuntimeException(__('message.allocation_exceeds_settlement', ['allocated' => formatRupiah($totalAllocated), 'total' => formatRupiah($data['total_amount'])]));
            }

            // Validate each allocation
            foreach ($allocations as $invoiceId => $amount) {
                if ($amount <= 0) {
                    continue;
                }

                // Phase 1: lockForUpdate() prevents concurrent over-allocation
                $invoice = Invoice::lockForUpdate()
                    ->where('id', $invoiceId)
                    ->where('student_id', $data['student_id']) // BR-07: same student only
                    ->where('status', '!=', 'cancelled')
                    ->first();

                if (!$invoice) {
                    throw new \RuntimeException(__('message.invoice_not_found', ['id' => $invoiceId]));
                }

                // BR-06: Allocation must not exceed outstanding (recalculated from settlements)
                $settledAmount = (float) $invoice->allocations()
                    ->whereHas('settlement', fn ($q) => $q->where('status', 'completed'))
                    ->sum('amount');
                $outstanding = (float) $invoice->total_amount - $settledAmount;
                if ($outstanding < 0) {
                    Log::warning('Over-settled invoice detected', [
                        'invoice_id' => $invoice->id,
                        'total_amount' => $invoice->total_amount,
                        'settled_amount' => $settledAmount,
                    ]);
                    $outstanding = 0;
                }
                if ($amount > $outstanding) {
                    throw new \RuntimeException(__('message.allocation_exceeds_outstanding', ['number' => $invoice->invoice_number, 'allocated' => formatRupiah($amount), 'outstanding' => formatRupiah($outstanding)]));
                }
            }

            $requiresApproval = (bool) getSetting('maker_checker.settlements_enabled', false);
            $initialStatus = $requiresApproval ? 'pending_approval' : 'completed';

            $settlement = Settlement::create([
                'settlement_number' => $number,
                'student_id' => $data['student_id'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'total_amount' => $data['total_amount'],
                'allocated_amount' => $totalAllocated,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $initialStatus,
                'created_by' => $userId,
            ]);

            // Create allocations
            foreach ($allocations as $invoiceId => $amount) {
                if ($amount <= 0) {
                    continue;
                }

                SettlementAllocation::create([
                    'settlement_id' => $settlement->id,
                    'invoice_id' => $invoiceId,
                    'amount' => $amount,
                ]);

                // Only update invoice status if settlement is completed (not pending)
                if (!$requiresApproval) {
                    $invoice = Invoice::find($invoiceId);
                    $invoice->recalculateFromAllocations();
                }
            }

            // Only mark obligations if settlement is completed
            if (!$requiresApproval) {
                $this->markObligationsFromAllocations($settlement);

                AccountingEngine::fromEvent('settlement.applied', [
                    'unit_id' => $settlement->unit_id,
                    'source_type' => 'settlement',
                    'source_id' => $settlement->id,
                    'total_amount' => (float) $settlement->allocated_amount,
                    'effective_date' => $settlement->payment_date?->toDateString() ?? now()->toDateString(),
                    'created_by' => $userId,
                    'allocations' => collect($allocations)
                        ->map(fn ($amount, $invoiceId) => ['invoice_id' => (int) $invoiceId, 'amount' => (float) $amount])
                        ->values()
                        ->all(),
                    'idempotency_key' => 'settlement.applied:'.$settlement->id,
                ]);
            }

            $this->financialEventLogger->record($settlement, 'settlement_created', [
                'settlement_number' => $settlement->settlement_number,
                'student_id' => $settlement->student_id,
                'total_amount' => (float) $settlement->total_amount,
                'allocated_amount' => (float) $settlement->allocated_amount,
                'payment_method' => $settlement->payment_method,
                'allocation_count' => count(array_filter($allocations, fn ($a) => $a > 0)),
                'requires_approval' => $requiresApproval,
            ], $userId);

            return $settlement->load('allocations.invoice', 'student');
        });
    }

    /**
     * Approve a pending settlement (maker-checker).
     */
    public function approveSettlement(Settlement $settlement, int $userId): Settlement
    {
        if ($settlement->status !== 'pending_approval') {
            throw new \RuntimeException(__('message.settlement_not_pending'));
        }

        if ($settlement->created_by === $userId) {
            throw new \RuntimeException(__('message.settlement_maker_checker_violation'));
        }

        return DB::transaction(function () use ($settlement, $userId) {
            $settlement->update([
                'status' => 'completed',
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            // Now apply the settlement effects: update invoices, mark obligations, post accounting
            $allocations = $settlement->allocations()->get();
            foreach ($allocations as $allocation) {
                $invoice = Invoice::find($allocation->invoice_id);
                if ($invoice) {
                    $invoice->recalculateFromAllocations();
                }
            }

            $this->markObligationsFromAllocations($settlement);

            $allocationData = $allocations
                ->map(fn ($a) => ['invoice_id' => (int) $a->invoice_id, 'amount' => (float) $a->amount])
                ->values()
                ->all();

            AccountingEngine::fromEvent('settlement.applied', [
                'unit_id' => $settlement->unit_id,
                'source_type' => 'settlement',
                'source_id' => $settlement->id,
                'total_amount' => (float) $settlement->allocated_amount,
                'effective_date' => $settlement->payment_date?->toDateString() ?? now()->toDateString(),
                'created_by' => $userId,
                'allocations' => $allocationData,
                'idempotency_key' => 'settlement.applied:'.$settlement->id,
            ]);

            $this->financialEventLogger->record($settlement, 'settlement_approved', [
                'settlement_number' => $settlement->settlement_number,
                'approved_by' => $userId,
            ], $userId);

            return $settlement->fresh('allocations.invoice', 'student');
        });
    }

    public function void(Settlement $settlement, int $userId, string $reason): Settlement
    {
        if ($settlement->isVoided()) {
            throw new \RuntimeException(__('message.settlement_already_void'));
        }

        if ($settlement->status !== 'completed') {
            throw new \RuntimeException(__('message.settlement_not_active', ['status' => $settlement->status]));
        }

        return DB::transaction(function () use ($settlement, $userId, $reason) {
            $settlement->update([
                'status' => 'void',
                'voided_at' => now(),
                'voided_by' => $userId,
                'void_reason' => $reason,
            ]);

            // Recalculate all affected invoices
            $invoiceIds = $settlement->allocations()->pluck('invoice_id')->unique();
            foreach ($invoiceIds as $invoiceId) {
                $invoice = Invoice::find($invoiceId);
                if ($invoice) {
                    $invoice->recalculateFromAllocations();
                }
            }

            // Revert obligation payments linked to this settlement's invoices
            $this->revertObligationsFromAllocations($settlement);

            // Keep denormalized column in sync after status change
            $settlement->recalculateAllocatedAmount();

            AccountingEngine::fromEvent('reversal.posted', [
                'unit_id' => $settlement->unit_id,
                'source_type' => 'settlement',
                'source_id' => $settlement->id,
                'effective_date' => now()->toDateString(),
                'created_by' => $userId,
                'reason' => $reason,
                'idempotency_key' => 'settlement.void.reversal:'.$settlement->id,
            ]);

            $this->financialEventLogger->record($settlement, 'settlement_voided', [
                'settlement_number' => $settlement->settlement_number,
                'reason' => $reason,
                'affected_invoices' => $invoiceIds->values()->all(),
            ], $userId);

            return $settlement->fresh();
        });
    }

    public function cancel(Settlement $settlement, int $userId, string $reason): Settlement
    {
        if ($settlement->isCancelled()) {
            throw new \RuntimeException(__('message.settlement_already_cancelled'));
        }

        if ($settlement->status !== 'completed') {
            throw new \RuntimeException(__('message.settlement_not_active', ['status' => $settlement->status]));
        }

        return DB::transaction(function () use ($settlement, $userId, $reason) {
            $settlement->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
                'cancellation_reason' => $reason,
            ]);

            // Recalculate all affected invoices (their allocations from this settlement are now void)
            $invoiceIds = $settlement->allocations()->pluck('invoice_id')->unique();
            foreach ($invoiceIds as $invoiceId) {
                $invoice = Invoice::find($invoiceId);
                if ($invoice) {
                    $invoice->recalculateFromAllocations();
                }
            }

            // Revert obligation payments linked to this settlement's invoices
            $this->revertObligationsFromAllocations($settlement);

            // Keep denormalized column in sync after status change
            $settlement->recalculateAllocatedAmount();

            AccountingEngine::fromEvent('reversal.posted', [
                'unit_id' => $settlement->unit_id,
                'source_type' => 'settlement',
                'source_id' => $settlement->id,
                'effective_date' => now()->toDateString(),
                'created_by' => $userId,
                'reason' => $reason,
                'idempotency_key' => 'settlement.cancel.reversal:'.$settlement->id,
            ]);

            $this->financialEventLogger->record($settlement, 'settlement_cancelled', [
                'settlement_number' => $settlement->settlement_number,
                'reason' => $reason,
                'affected_invoices' => $invoiceIds->values()->all(),
            ], $userId);

            return $settlement->fresh();
        });
    }

    /**
     * Mark StudentObligations as paid when their invoices are fully paid.
     */
    private function markObligationsFromAllocations(Settlement $settlement): void
    {
        $allocations = $settlement->allocations()->with('invoice.items.studentObligation')->get();

        foreach ($allocations as $allocation) {
            $invoice = $allocation->invoice;
            if ($invoice->status === 'paid') {
                // Mark all obligations on this invoice as paid
                foreach ($invoice->items as $item) {
                    if ($item->studentObligation && !$item->studentObligation->is_paid) {
                        $item->studentObligation->update([
                            'is_paid' => true,
                            'paid_amount' => $item->amount,
                            'paid_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Revert obligation payments when a settlement is cancelled.
     */
    private function revertObligationsFromAllocations(Settlement $settlement): void
    {
        $allocations = $settlement->allocations()->with('invoice.items.studentObligation')->get();

        foreach ($allocations as $allocation) {
            $invoice = $allocation->invoice;
            // If the invoice is no longer paid, revert obligations
            if ($invoice->status !== 'paid') {
                foreach ($invoice->items as $item) {
                    if ($item->studentObligation && $item->studentObligation->is_paid) {
                        // Check if there are other completed settlements covering this invoice
                        $stillPaid = $invoice->paid_amount >= $invoice->total_amount;
                        if (!$stillPaid) {
                            $item->studentObligation->update([
                                'is_paid' => false,
                                'paid_amount' => 0,
                                'paid_at' => null,
                            ]);
                        }
                    }
                }
            }
        }
    }

    private function syncSettlementSequenceFloor(string $seqPrefix, int $year): void
    {
        $latestNumber = Settlement::query()
            ->where('settlement_number', 'like', "STL-{$year}-%")
            ->orderByDesc('settlement_number')
            ->value('settlement_number');

        $maxSequence = 0;
        if (is_string($latestNumber) && preg_match('/(\d{6})$/', $latestNumber, $matches)) {
            $maxSequence = (int) $matches[1];
        }

        if ($maxSequence <= 0) {
            return;
        }

        DB::transaction(function () use ($seqPrefix, $maxSequence): void {
            $record = DocumentSequence::query()
                ->lockForUpdate()
                ->where('prefix', $seqPrefix)
                ->first();

            if (! $record) {
                DocumentSequence::query()->create([
                    'prefix' => $seqPrefix,
                    'last_sequence' => $maxSequence,
                ]);

                return;
            }

            if ((int) $record->last_sequence < $maxSequence) {
                $record->update(['last_sequence' => $maxSequence]);
            }
        });
    }
}
