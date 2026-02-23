<?php

namespace App\Services;

use App\Events\TransactionCreated;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(
        private ReceiptService $receiptService,
    ) {}

    public function generateTransactionNumber(string $type): string
    {
        $prefix = $type === 'income' ? 'NF' : 'NK';
        $year = now()->year;

        $last = Transaction::withoutGlobalScope('unit')
            ->where('type', $type)
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('transaction_number');

        $sequence = 1;
        if ($last && preg_match('/(\d{6})$/', $last, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }

    public function createIncome(array $data, array $items, int $userId): Transaction
    {
        $transaction = DB::transaction(function () use ($data, $items, $userId) {
            // Phase 1: Atomic financial write
            $number = $this->generateTransactionNumber('income');

            $transaction = Transaction::create([
                'transaction_number' => $number,
                'transaction_date' => $data['transaction_date'],
                'type' => 'income',
                'student_id' => null,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'total_amount' => collect($items)->sum('amount'),
                'description' => $data['description'] ?? null,
                'status' => 'completed',
                'created_by' => $userId,
            ]);

            foreach ($items as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'fee_type_id' => $item['fee_type_id'],
                    'description' => $item['description'] ?? null,
                    'amount' => $item['amount'],
                    'month' => $item['month'] ?? null,
                    'year' => $item['year'] ?? null,
                ]);
            }

            if (config('features.accounting_engine_v2')) { AccountingEngine::fromEvent('payment.posted', ['unit_id' => $transaction->unit_id, 'source_type' => 'transaction', 'source_id' => $transaction->id, 'total_amount' => (float) $transaction->total_amount, 'effective_date' => $transaction->transaction_date?->toDateString() ?? now()->toDateString(), 'created_by' => $userId, 'idempotency_key' => 'payment.posted:transaction:'.$transaction->id]); }

            return $transaction;
        });

        // Phase 2: After-commit side effects
        DB::afterCommit(function () use ($transaction) {
            $this->receiptService->generate($transaction);
            TransactionCreated::dispatch($transaction);
        });

        return $transaction->load('items.feeType', 'student');
    }

    public function createExpense(array $data, array $items, int $userId): Transaction
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            $number = $this->generateTransactionNumber('expense');

            $transaction = Transaction::create([
                'transaction_number' => $number,
                'transaction_date' => $data['transaction_date'],
                'type' => 'expense',
                'student_id' => null,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'total_amount' => collect($items)->sum('amount'),
                'description' => $data['description'] ?? null,
                'proof_path' => $data['proof_path'] ?? null,
                'status' => 'completed',
                'created_by' => $userId,
            ]);

            foreach ($items as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'fee_type_id' => $item['fee_type_id'],
                    'description' => $item['description'] ?? null,
                    'amount' => $item['amount'],
                ]);
            }

            return $transaction->load('items.feeType');
        });
    }

    public function cancel(Transaction $transaction, int $userId, string $reason): Transaction
    {
        if ($transaction->isCancelled()) {
            throw new \RuntimeException(__('message.transaction_already_cancelled'));
        }

        $transaction->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $userId,
            'cancellation_reason' => $reason,
        ]);

        // Revert obligation payments
        if ($transaction->type === 'income') {
            $itemIds = $transaction->items->pluck('id');

            StudentObligation::whereIn('transaction_item_id', $itemIds)
                ->update([
                    'is_paid' => false,
                    'paid_amount' => 0,
                    'paid_at' => null,
                    'transaction_item_id' => null,
                ]);

            // Regenerate receipt with cancellation watermark
            DB::afterCommit(function () use ($transaction) {
                $this->receiptService->generateCancelled($transaction);
            });
        }

        return $transaction->fresh();
    }
}
