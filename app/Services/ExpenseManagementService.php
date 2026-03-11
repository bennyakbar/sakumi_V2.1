<?php

namespace App\Services;

use App\Models\ExpenseBudget;
use App\Models\ExpenseEntry;
use App\Models\FeeType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExpenseManagementService
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly UnitContext $unitContext,
    ) {
    }

    /**
     * Check if creating this expense would exceed the monthly budget.
     *
     * Returns warning data array if over budget, null if within budget or no budget set.
     */
    public function checkBudget(int $subcategoryId, string $entryDate, float $amount): ?array
    {
        $date = Carbon::parse($entryDate);
        $unitId = $this->unitContext->id();

        $budget = ExpenseBudget::query()
            ->withoutGlobalScopes()
            ->where('unit_id', $unitId)
            ->where('year', $date->year)
            ->where('month', $date->month)
            ->where('expense_fee_subcategory_id', $subcategoryId)
            ->first();

        if (! $budget) {
            return null;
        }

        $spent = ExpenseEntry::query()
            ->withoutGlobalScopes()
            ->where('unit_id', $unitId)
            ->where('expense_fee_subcategory_id', $subcategoryId)
            ->whereIn('status', ['draft', 'approved', 'posted'])
            ->whereYear('entry_date', $date->year)
            ->whereMonth('entry_date', $date->month)
            ->sum('amount');

        $newTotal = (float) $spent + $amount;
        $budgetAmount = (float) $budget->budget_amount;

        if ($newTotal <= $budgetAmount) {
            return null;
        }

        return [
            'subcategory' => $budget->subcategory?->name ?? '-',
            'budget' => $budgetAmount,
            'spent' => (float) $spent,
            'remaining' => $budgetAmount - (float) $spent,
            'exceeds_by' => $newTotal - $budgetAmount,
            'percentage' => round(($newTotal / $budgetAmount) * 100, 1),
        ];
    }

    public function createDraft(array $data, int $userId): ExpenseEntry
    {
        $entryDate = Carbon::parse($data['entry_date']);

        return ExpenseEntry::create([
            'expense_fee_subcategory_id' => (int) $data['expense_fee_subcategory_id'],
            'fee_type_id' => (int) $data['fee_type_id'],
            'entry_date' => $data['entry_date'],
            'period_year' => $entryDate->year,
            'period_month' => $entryDate->month,
            'payment_method' => $data['payment_method'],
            'vendor_name' => $data['vendor_name'] ?? null,
            'amount' => $data['amount'],
            'estimated_amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'receipt_path' => $data['receipt_path'] ?? null,
            'supporting_doc_path' => $data['supporting_doc_path'] ?? null,
            'status' => 'draft',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function approveAndPost(ExpenseEntry $entry, int $userId): ExpenseEntry
    {
        if ($entry->status !== 'draft') {
            throw new \RuntimeException('Only draft entry can be approved.');
        }

        return DB::transaction(function () use ($entry, $userId) {
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
                'realized_amount' => $entry->amount,
                'approved_by' => $userId,
                'approved_at' => now(),
                'posted_transaction_id' => $transaction->id,
                'updated_by' => $userId,
            ]);

            return $entry->fresh(['postedTransaction', 'subcategory.category', 'feeType', 'approver']);
        });
    }
}
