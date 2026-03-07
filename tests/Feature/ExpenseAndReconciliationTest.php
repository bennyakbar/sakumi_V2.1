<?php

namespace Tests\Feature;

use App\Models\BankReconciliationLine;
use App\Models\BankReconciliationSession;
use App\Models\ExpenseFeeCategory;
use App\Models\ExpenseFeeSubcategory;
use App\Models\ExpenseEntry;
use App\Models\FeeType;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\AccountMappingsSeeder;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ExpenseAndReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private Unit $unit;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);
        $this->seed(AccountMappingsSeeder::class);
        Setting::set('academic_year_current', '2025/2026');

        $this->unit = Unit::query()->where('code', 'MI')->firstOrFail();
        $this->superAdmin = User::factory()->create(['unit_id' => $this->unit->id]);
        $this->superAdmin->assignRole('super_admin');
    }

    private function actAsUnit(): self
    {
        return $this->actingAs($this->superAdmin)->withSession(['current_unit_id' => $this->unit->id]);
    }

    /**
     * @return array{0: ExpenseFeeCategory, 1: ExpenseFeeSubcategory, 2: FeeType}
     */
    private function makeExpenseReferenceData(): array
    {
        $category = ExpenseFeeCategory::query()->create([
            'unit_id' => $this->unit->id,
            'code' => 'CAT'.strtoupper(substr(sha1((string) microtime(true)), 0, 6)),
            'name' => 'Operasional',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $subcategory = ExpenseFeeSubcategory::query()->create([
            'unit_id' => $this->unit->id,
            'expense_fee_category_id' => $category->id,
            'code' => 'SUB'.strtoupper(substr(sha1((string) hrtime(true)), 0, 6)),
            'name' => 'ATK',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $feeType = FeeType::query()->create([
            'unit_id' => $this->unit->id,
            'expense_fee_subcategory_id' => $subcategory->id,
            'code' => 'EXP'.strtoupper(substr(sha1((string) rand()), 0, 6)),
            'name' => 'Belanja ATK',
            'description' => 'Pengeluaran perlengkapan',
            'is_monthly' => false,
            'is_active' => true,
        ]);

        return [$category, $subcategory, $feeType];
    }

    public function test_expense_draft_can_be_approved_and_posted_to_transaction(): void
    {
        [, $subcategory, $feeType] = $this->makeExpenseReferenceData();

        $this->actAsUnit()
            ->post(route('expenses.store'), [
                'fee_type_id' => $feeType->id,
                'entry_date' => now()->toDateString(),
                'payment_method' => 'cash',
                'vendor_name' => 'Toko Sinar',
                'amount' => 125000,
                'description' => 'Pembelian ATK',
            ])
            ->assertRedirect(route('expenses.index'));

        $entry = ExpenseEntry::query()->where('expense_fee_subcategory_id', $subcategory->id)->firstOrFail();
        $this->assertSame('draft', $entry->status);

        $this->actAsUnit()
            ->post(route('expenses.approve', $entry))
            ->assertRedirect(route('expenses.index'));

        $entry->refresh();
        $this->assertSame('posted', $entry->status);
        $this->assertNotNull($entry->posted_transaction_id);

        $this->assertDatabaseHas('transactions', [
            'id' => $entry->posted_transaction_id,
            'unit_id' => $this->unit->id,
            'type' => 'expense',
            'status' => 'completed',
            'total_amount' => 125000,
        ]);
    }

    public function test_budget_vs_realization_report_shows_expected_variance(): void
    {
        [, $subcategory, $feeType] = $this->makeExpenseReferenceData();

        $this->actAsUnit()
            ->post(route('expenses.budgets.store'), [
                'year' => (int) now()->year,
                'month' => (int) now()->month,
                'expense_fee_subcategory_id' => $subcategory->id,
                'budget_amount' => 100000,
                'notes' => 'Budget bulanan',
            ])
            ->assertRedirect();

        $this->actAsUnit()->post(route('expenses.store'), [
            'fee_type_id' => $feeType->id,
            'entry_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'vendor_name' => 'Toko A',
            'amount' => 60000,
            'description' => 'Tahap 1',
        ]);
        $entryA = ExpenseEntry::query()->latest('id')->firstOrFail();
        $this->actAsUnit()->post(route('expenses.approve', $entryA));

        $this->actAsUnit()->post(route('expenses.store'), [
            'fee_type_id' => $feeType->id,
            'entry_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'vendor_name' => 'Toko B',
            'amount' => 20000,
            'description' => 'Tahap 2',
        ]);
        $entryB = ExpenseEntry::query()->latest('id')->firstOrFail();
        $this->actAsUnit()->post(route('expenses.approve', $entryB));

        $this->actAsUnit()
            ->get(route('expenses.budget-report', [
                'month' => (int) now()->month,
                'year' => (int) now()->year,
            ]))
            ->assertOk()
            ->assertSee('ATK')
            ->assertSee('100.000,00')
            ->assertSee('80.000,00')
            ->assertSee('20.000,00');
    }

    public function test_bank_reconciliation_lifecycle_requires_resolved_lines_before_close(): void
    {
        $periodMonth = (int) now()->month;
        $periodYear = (int) now()->year;

        $this->actAsUnit()
            ->post(route('bank-reconciliation.store'), [
                'bank_account_name' => 'BCA Operasional',
                'period_month' => $periodMonth,
                'period_year' => $periodYear,
                'opening_balance' => 500000,
            ])
            ->assertRedirect();

        $session = BankReconciliationSession::query()->firstOrFail();
        $this->assertSame('draft', $session->status);

        $csv = "date,description,reference,amount,type\n";
        $csv .= now()->toDateString().",Setoran Kas,NF-001,70000,debit\n";
        $file = UploadedFile::fake()->createWithContent('mutasi.csv', $csv);

        $this->actAsUnit()
            ->post(route('bank-reconciliation.import', $session), ['file' => $file])
            ->assertRedirect(route('bank-reconciliation.show', $session));

        $session->refresh();
        $this->assertSame('in_review', $session->status);
        $line = BankReconciliationLine::query()->firstOrFail();
        $this->assertSame('unmatched', $line->match_status);

        $this->actAsUnit()
            ->post(route('bank-reconciliation.close', $session))
            ->assertSessionHas('error');
        $session->refresh();
        $this->assertSame('in_review', $session->status);

        $transaction = Transaction::factory()->create([
            'unit_id' => $this->unit->id,
            'transaction_number' => 'NF-TEST-000001',
            'transaction_date' => now()->toDateString(),
            'type' => 'income',
            'student_id' => null,
            'payment_method' => 'cash',
            'total_amount' => 70000,
            'status' => 'completed',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actAsUnit()
            ->post(route('bank-reconciliation.match', [$session, $line]), [
                'transaction_id' => $transaction->id,
            ])
            ->assertRedirect(route('bank-reconciliation.show', $session));

        $line->refresh();
        $this->assertSame('matched', $line->match_status);

        $this->actAsUnit()
            ->post(route('bank-reconciliation.close', $session))
            ->assertRedirect(route('bank-reconciliation.show', $session));

        $session->refresh();
        $this->assertSame('closed', $session->status);

        $this->actAsUnit()
            ->post(route('bank-reconciliation.unmatch', [$session, $line]))
            ->assertSessionHas('error');
        $line->refresh();
        $this->assertSame('matched', $line->match_status);

        $this->assertDatabaseHas('bank_reconciliation_logs', [
            'bank_reconciliation_session_id' => $session->id,
            'action' => 'create_session',
        ]);
        $this->assertDatabaseHas('bank_reconciliation_logs', [
            'bank_reconciliation_session_id' => $session->id,
            'action' => 'import_csv',
        ]);
        $this->assertDatabaseHas('bank_reconciliation_logs', [
            'bank_reconciliation_session_id' => $session->id,
            'action' => 'match_line',
        ]);
        $this->assertDatabaseHas('bank_reconciliation_logs', [
            'bank_reconciliation_session_id' => $session->id,
            'action' => 'close_session',
        ]);
    }
}
