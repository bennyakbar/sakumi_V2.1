<?php

namespace App\Services;

use App\Models\ExpenseAuditLog;
use App\Models\ExpenseBudget;
use App\Models\ExpenseEntry;
use App\Models\FeeType;
use Illuminate\Support\Facades\DB;

class ExpenseManagementService
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {
    }

    public function createDraft(array $data, int $userId): ExpenseEntry
    {
        $entry = ExpenseEntry::create([
            'expense_fee_subcategory_id' => (int) $data['expense_fee_subcategory_id'],
            'fee_type_id' => (int) $data['fee_type_id'],
            'entry_date' => $data['entry_date'],
            'payment_method' => $data['payment_method'],
            'vendor_name' => $data['vendor_name'] ?? null,
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'status' => 'draft',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $this->log($entry, $userId, 'expense_created', [
            'amount' => (float) $data['amount'],
            'fee_type_id' => (int) $data['fee_type_id'],
        ]);

        return $entry;
    }

    public function cancelDraft(ExpenseEntry $entry, int $userId): ExpenseEntry
    {
        if ($entry->status !== 'draft') {
            throw new \RuntimeException('Only draft entries can be cancelled.');
        }

        $entry->update([
            'status' => 'cancelled',
            'updated_by' => $userId,
        ]);

        $this->log($entry, $userId, 'expense_cancelled', [
            'previous_status' => 'draft',
        ]);

        return $entry->fresh();
    }

    public function cancelPosted(ExpenseEntry $entry, int $userId, string $reason): ExpenseEntry
    {
        if ($entry->status !== 'posted') {
            throw new \RuntimeException('Only posted entries can be reversed.');
        }

        return DB::transaction(function () use ($entry, $userId, $reason) {
            $this->transactionService->cancel($entry->postedTransaction, $userId, $reason);

            $entry->update([
                'status' => 'reversed',
                'updated_by' => $userId,
            ]);

            $this->log($entry, $userId, 'expense_reversed', [
                'reason' => $reason,
                'transaction_id' => $entry->posted_transaction_id,
            ]);

            return $entry->fresh(['postedTransaction', 'subcategory.category', 'feeType']);
        });
    }

    public function approveAndPost(ExpenseEntry $entry, int $userId): ExpenseEntry
    {
        if ($entry->status !== 'draft') {
            throw new \RuntimeException('Only draft entry can be approved.');
        }

        // Maker-Checker: creator cannot approve own expense
        if ($entry->created_by === $userId) {
            throw new \RuntimeException(__('message.expense_maker_checker_violation'));
        }

        // Budget validation
        $budgetWarning = $this->checkBudget($entry);

        return DB::transaction(function () use ($entry, $userId, $budgetWarning) {
            $feeType = FeeType::query()->findOrFail($entry->fee_type_id);

            $transaction = $this->transactionService->createExpense(
                data: [
                    'transaction_date' => $entry->entry_date->toDateString(),
                    'payment_method' => $entry->payment_method,
                    'description' => trim(($entry->vendor_name ? $entry->vendor_name . ' - ' : '') . ($entry->description ?? 'Structured expense')),
                ],
                items: [[
                    'fee_type_id' => $feeType->id,
                    'amount' => (float) $entry->amount,
                    'description' => $entry->description,
                ]],
                userId: $userId,
            );

            $entry->update([
                'status' => 'posted',
                'approved_by' => $userId,
                'approved_at' => now(),
                'posted_transaction_id' => $transaction->id,
                'updated_by' => $userId,
            ]);

            $this->log($entry, $userId, 'expense_approved', [
                'transaction_id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'budget_warning' => $budgetWarning,
            ]);

            $this->log($entry, $userId, 'expense_posted', [
                'transaction_id' => $transaction->id,
            ]);

            return $entry->fresh(['postedTransaction', 'subcategory.category', 'feeType', 'approver']);
        });
    }

    /**
     * Check if expense amount exceeds remaining budget for the period.
     * Returns warning string if over-budget, null if within budget or no budget set.
     */
    public function checkBudget(ExpenseEntry $entry): ?string
    {
        $month = $entry->entry_date->month;
        $year = $entry->entry_date->year;

        $budget = ExpenseBudget::query()
            ->where('expense_fee_subcategory_id', $entry->expense_fee_subcategory_id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (!$budget) {
            return null; // No budget set — no constraint
        }

        $realized = (float) ExpenseEntry::query()
            ->where('expense_fee_subcategory_id', $entry->expense_fee_subcategory_id)
            ->whereIn('status', ['approved', 'posted'])
            ->whereMonth('entry_date', $month)
            ->whereYear('entry_date', $year)
            ->where('id', '!=', $entry->id)
            ->sum('amount');

        $remaining = (float) $budget->budget_amount - $realized;
        $amount = (float) $entry->amount;

        if ($amount > $remaining) {
            $overBy = $amount - $remaining;
            return __('message.expense_budget_exceeded', [
                'remaining' => number_format($remaining, 0, ',', '.'),
                'over' => number_format($overBy, 0, ',', '.'),
            ]);
        }

        return null;
    }

    public function logAttachmentUploaded(ExpenseEntry $entry, int $userId, string $filename): void
    {
        $this->log($entry, $userId, 'attachment_uploaded', [
            'filename' => $filename,
        ]);
    }

    public function logAttachmentDeleted(ExpenseEntry $entry, int $userId, string $filename): void
    {
        $this->log($entry, $userId, 'attachment_deleted', [
            'filename' => $filename,
        ]);
    }

    private function log(ExpenseEntry $entry, int $userId, string $eventType, array $metadata = []): void
    {
        ExpenseAuditLog::create([
            'expense_entry_id' => $entry->id,
            'user_id' => $userId,
            'event_type' => $eventType,
            'metadata' => $metadata ?: null,
        ]);
    }
}
