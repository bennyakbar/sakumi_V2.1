<?php

namespace App\Services;

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
        return ExpenseEntry::create([
            'expense_fee_subcategory_id' => (int) $data['expense_fee_subcategory_id'],
            'fee_type_id' => (int) $data['fee_type_id'],
            'entry_date' => $data['entry_date'],
            'payment_method' => $data['payment_method'],
            'vendor_name' => $data['vendor_name'] ?? null,
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
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
                'approved_by' => $userId,
                'approved_at' => now(),
                'posted_transaction_id' => $transaction->id,
                'updated_by' => $userId,
            ]);

            return $entry->fresh(['postedTransaction', 'subcategory.category', 'feeType', 'approver']);
        });
    }
}
