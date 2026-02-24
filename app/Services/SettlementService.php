<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Settlement;
use App\Models\SettlementAllocation;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    public function generateSettlementNumber(): string
    {
        $year = now()->year;

        $last = Settlement::withoutGlobalScope('unit')
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('settlement_number');

        $sequence = 1;
        if ($last && preg_match('/(\d{6})$/', $last, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('STL-%s-%06d', $year, $sequence);
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
                throw new \RuntimeException(__('message.allocation_exceeds_settlement', ['allocated' => number_format($totalAllocated, 0, ',', '.'), 'total' => number_format($data['total_amount'], 0, ',', '.')]));
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
                $outstanding = max(0, (float) $invoice->total_amount - $settledAmount);
                if ($amount > $outstanding) {
                    throw new \RuntimeException(__('message.allocation_exceeds_outstanding', ['number' => $invoice->invoice_number, 'allocated' => number_format($amount, 0, ',', '.'), 'outstanding' => number_format($outstanding, 0, ',', '.')]));
                }
            }

            $settlement = Settlement::create([
                'settlement_number' => $number,
                'student_id' => $data['student_id'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'total_amount' => $data['total_amount'],
                'allocated_amount' => $totalAllocated,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
                'created_by' => $userId,
            ]);

            // Create allocations and update invoice statuses
            foreach ($allocations as $invoiceId => $amount) {
                if ($amount <= 0) {
                    continue;
                }

                SettlementAllocation::create([
                    'settlement_id' => $settlement->id,
                    'invoice_id' => $invoiceId,
                    'amount' => $amount,
                ]);

                $invoice = Invoice::find($invoiceId);
                $invoice->recalculateFromAllocations();
            }

            // Also update linked StudentObligations as paid
            $this->markObligationsFromAllocations($settlement);

            if (config('features.accounting_engine_v2')) { AccountingEngine::fromEvent('settlement.applied', ['unit_id' => $settlement->unit_id, 'source_type' => 'settlement', 'source_id' => $settlement->id, 'total_amount' => (float) $settlement->allocated_amount, 'effective_date' => $settlement->payment_date?->toDateString() ?? now()->toDateString(), 'created_by' => $userId, 'allocations' => collect($allocations)->map(fn ($amount, $invoiceId) => ['invoice_id' => (int) $invoiceId, 'amount' => (float) $amount])->values()->all(), 'idempotency_key' => 'settlement.applied:'.$settlement->id]); }

            return $settlement->load('allocations.invoice', 'student');
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

            if (config('features.accounting_engine_v2')) { AccountingEngine::fromEvent('reversal.posted', ['unit_id' => $settlement->unit_id, 'source_type' => 'settlement', 'source_id' => $settlement->id, 'effective_date' => now()->toDateString(), 'created_by' => $userId, 'reason' => $reason, 'idempotency_key' => 'settlement.void.reversal:'.$settlement->id]); }

            return $settlement->fresh();
        });
    }

    public function cancel(Settlement $settlement, int $userId, string $reason): Settlement
    {
        if ($settlement->isCancelled()) {
            throw new \RuntimeException(__('message.settlement_already_cancelled'));
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

            if (config('features.accounting_engine_v2')) { AccountingEngine::fromEvent('reversal.posted', ['unit_id' => $settlement->unit_id, 'source_type' => 'settlement', 'source_id' => $settlement->id, 'effective_date' => now()->toDateString(), 'created_by' => $userId, 'reason' => $reason, 'idempotency_key' => 'settlement.cancel.reversal:'.$settlement->id]); }

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
                            'transaction_item_id' => null,
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
                                'transaction_item_id' => null,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
