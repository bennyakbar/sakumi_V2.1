<?php

namespace Database\Seeders;

use App\Models\BankReconciliationLine;
use App\Models\BankReconciliationLog;
use App\Models\BankReconciliationSession;
use App\Models\ExpenseBudget;
use App\Models\ExpenseEntry;
use App\Models\ExpenseFeeCategory;
use App\Models\ExpenseFeeSubcategory;
use App\Models\FeeType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ReconciliationStagingSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::query()->where('code', 'MI')->first() ?: Unit::query()->firstOrFail();
        session(['current_unit_id' => $unit->id]);

        $actorId = User::query()->where('unit_id', $unit->id)->value('id')
            ?: User::factory()->create([
                'unit_id' => $unit->id,
                'name' => 'Staging Seeder User',
                'email' => 'staging.recon@example.test',
            ])->id;

        $category = ExpenseFeeCategory::query()->firstOrCreate(
            ['unit_id' => $unit->id, 'code' => 'OPR'],
            ['name' => 'Operasional', 'sort_order' => 1, 'is_active' => true],
        );

        $subcategory = ExpenseFeeSubcategory::query()->firstOrCreate(
            ['unit_id' => $unit->id, 'code' => 'ATK'],
            [
                'expense_fee_category_id' => $category->id,
                'name' => 'ATK',
                'sort_order' => 1,
                'is_active' => true,
            ],
        );

        $feeType = FeeType::query()->firstOrCreate(
            ['unit_id' => $unit->id, 'code' => 'EXP-ATK-STG'],
            [
                'expense_fee_subcategory_id' => $subcategory->id,
                'name' => 'Belanja ATK (Staging)',
                'description' => 'Seed staging untuk expense terstruktur dan rekonsiliasi',
                'is_monthly' => false,
                'is_active' => true,
            ],
        );

        $year = (int) now()->year;
        $month = (int) now()->month;

        ExpenseBudget::query()->updateOrCreate(
            [
                'unit_id' => $unit->id,
                'year' => $year,
                'month' => $month,
                'expense_fee_subcategory_id' => $subcategory->id,
            ],
            [
                'budget_amount' => 1500000,
                'notes' => 'Budget staging bulan berjalan',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ],
        );

        $seedTransaction = Transaction::query()->firstOrCreate(
            ['transaction_number' => sprintf('NF-STG-%d%02d-0001', $year, $month)],
            [
                'unit_id' => $unit->id,
                'transaction_date' => now()->toDateString(),
                'type' => 'income',
                'student_id' => null,
                'payment_method' => 'transfer',
                'total_amount' => 450000,
                'description' => 'Setoran bank staging',
                'status' => 'completed',
                'created_by' => $actorId,
            ],
        );

        ExpenseEntry::query()->firstOrCreate(
            ['unit_id' => $unit->id, 'description' => 'Pembelian ATK staging'],
            [
                'expense_fee_subcategory_id' => $subcategory->id,
                'fee_type_id' => $feeType->id,
                'entry_date' => now()->toDateString(),
                'payment_method' => 'cash',
                'vendor_name' => 'Toko Staging',
                'amount' => 320000,
                'status' => 'posted',
                'posted_transaction_id' => null,
                'approved_by' => $actorId,
                'approved_at' => now(),
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ],
        );

        $session = BankReconciliationSession::query()->firstOrCreate(
            [
                'unit_id' => $unit->id,
                'bank_account_name' => 'BCA Operasional',
                'period_year' => $year,
                'period_month' => $month,
            ],
            [
                'opening_balance' => 2500000,
                'status' => 'in_review',
                'notes' => 'Seed staging untuk uji rekonsiliasi bulanan',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ],
        );

        $matchedLine = BankReconciliationLine::query()->firstOrCreate(
            [
                'bank_reconciliation_session_id' => $session->id,
                'reference' => 'NF-STG-REF-1',
            ],
            [
                'line_date' => now()->toDateString(),
                'description' => 'Mutasi setoran staging',
                'amount' => 450000,
                'type' => 'debit',
                'match_status' => 'matched',
                'matched_transaction_id' => $seedTransaction->id,
                'matched_by' => $actorId,
                'matched_at' => now(),
            ],
        );

        BankReconciliationLine::query()->firstOrCreate(
            [
                'bank_reconciliation_session_id' => $session->id,
                'reference' => 'UNMATCH-STG-1',
            ],
            [
                'line_date' => now()->toDateString(),
                'description' => 'Biaya admin bank',
                'amount' => 10000,
                'type' => 'credit',
                'match_status' => 'unmatched',
            ],
        );

        BankReconciliationLog::query()->firstOrCreate(
            [
                'bank_reconciliation_session_id' => $session->id,
                'action' => 'seed_staging',
            ],
            [
                'payload' => [
                    'matched_line_id' => $matchedLine->id,
                    'actor_id' => $actorId,
                ],
                'actor_id' => $actorId,
            ],
        );
    }
}
