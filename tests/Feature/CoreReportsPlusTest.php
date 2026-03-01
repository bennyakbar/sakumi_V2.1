<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Settlement;
use App\Models\SettlementAllocation;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreReportsPlusTest extends TestCase
{
    use RefreshDatabase;

    private Unit $unit;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->unit = Unit::query()->where('code', 'MI')->firstOrFail();
        $this->superAdmin = User::factory()->create(['unit_id' => $this->unit->id]);
        $this->superAdmin->assignRole('super_admin');
    }

    private function actAsUnit(?User $user = null): self
    {
        $target = $user ?: $this->superAdmin;

        return $this->actingAs($target)->withSession(['current_unit_id' => $this->unit->id]);
    }

    public function test_ar_outstanding_respects_class_filter_and_supports_export(): void
    {
        $classA = SchoolClass::factory()->create(['unit_id' => $this->unit->id, 'name' => 'A']);
        $classB = SchoolClass::factory()->create(['unit_id' => $this->unit->id, 'name' => 'B']);
        $category = StudentCategory::factory()->create(['unit_id' => $this->unit->id]);
        $studentA = Student::factory()->create(['unit_id' => $this->unit->id, 'class_id' => $classA->id, 'category_id' => $category->id]);
        $studentB = Student::factory()->create(['unit_id' => $this->unit->id, 'class_id' => $classB->id, 'category_id' => $category->id]);

        $invoiceA = Invoice::factory()->create([
            'unit_id' => $this->unit->id,
            'student_id' => $studentA->id,
            'invoice_number' => 'INV-A',
            'due_date' => now()->subDays(3)->toDateString(),
            'total_amount' => 200000,
            'created_by' => $this->superAdmin->id,
        ]);
        Invoice::factory()->create([
            'unit_id' => $this->unit->id,
            'student_id' => $studentB->id,
            'invoice_number' => 'INV-B',
            'due_date' => now()->subDays(3)->toDateString(),
            'total_amount' => 200000,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actAsUnit()
            ->get(route('reports.ar-outstanding', [
                'date_from' => now()->subDays(7)->toDateString(),
                'date_to' => now()->toDateString(),
                'class_id' => $classA->id,
            ]))
            ->assertOk()
            ->assertSee('INV-A')
            ->assertDontSee('INV-B');

        $export = $this->actAsUnit()
            ->get(route('reports.ar-outstanding.export', [
                'format' => 'csv',
                'date_from' => now()->subDays(7)->toDateString(),
                'date_to' => now()->toDateString(),
            ]));
        $export->assertOk();
        $export->assertHeader('content-disposition');
        $this->assertStringContainsString('.csv', (string) $export->headers->get('content-disposition'));

        // Prevent unused warning in strict static checks
        $this->assertNotNull($invoiceA->id);
    }

    public function test_collection_report_filters_by_method_and_cashier(): void
    {
        $cashierOne = User::factory()->create(['unit_id' => $this->unit->id, 'name' => 'Cashier One']);
        $cashierTwo = User::factory()->create(['unit_id' => $this->unit->id, 'name' => 'Cashier Two']);

        $settlementCash = Settlement::factory()->create([
            'unit_id' => $this->unit->id,
            'settlement_number' => 'STL-CASH-1',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'allocated_amount' => 100000,
            'status' => 'completed',
            'created_by' => $cashierOne->id,
        ]);

        $settlementTransfer = Settlement::factory()->create([
            'unit_id' => $this->unit->id,
            'settlement_number' => 'STL-TRF-2',
            'payment_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'allocated_amount' => 200000,
            'status' => 'completed',
            'created_by' => $cashierTwo->id,
        ]);

        $response = $this->actAsUnit()
            ->get(route('reports.collection', [
                'date_from' => now()->subDays(1)->toDateString(),
                'date_to' => now()->toDateString(),
                'payment_method' => 'cash',
                'cashier_id' => $cashierOne->id,
            ]))
            ->assertOk();

        $response->assertSee('value="cash" selected', false);
        $response->assertSee('value="' . $cashierOne->id . '" selected', false);
    }

    public function test_student_statement_shows_opening_and_closing_balance(): void
    {
        $class = SchoolClass::factory()->create(['unit_id' => $this->unit->id]);
        $category = StudentCategory::factory()->create(['unit_id' => $this->unit->id]);
        $student = Student::factory()->create(['unit_id' => $this->unit->id, 'class_id' => $class->id, 'category_id' => $category->id]);

        $invoiceBefore = Invoice::factory()->create([
            'unit_id' => $this->unit->id,
            'student_id' => $student->id,
            'invoice_number' => 'INV-OPEN',
            'invoice_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDays(9)->toDateString(),
            'total_amount' => 300000,
            'created_by' => $this->superAdmin->id,
        ]);

        $settlementBefore = Settlement::factory()->create([
            'unit_id' => $this->unit->id,
            'student_id' => $student->id,
            'payment_date' => now()->subDays(9)->toDateString(),
            'status' => 'completed',
            'allocated_amount' => 100000,
            'created_by' => $this->superAdmin->id,
        ]);

        SettlementAllocation::create([
            'settlement_id' => $settlementBefore->id,
            'invoice_id' => $invoiceBefore->id,
            'amount' => 100000,
        ]);

        $invoiceDuring = Invoice::factory()->create([
            'unit_id' => $this->unit->id,
            'student_id' => $student->id,
            'invoice_number' => 'INV-CURR',
            'invoice_date' => now()->subDays(2)->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total_amount' => 50000,
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actAsUnit()
            ->get(route('reports.student-statement', [
                'student_id' => $student->id,
                'date_from' => now()->subDays(5)->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('INV-CURR');

        $response->assertSee('Opening Balance');
        $response->assertSee('Rp 200.000,00');

        $this->assertNotNull($invoiceDuring->id);
    }

    public function test_cash_book_uses_cash_movements_only(): void
    {
        Transaction::factory()->create([
            'unit_id' => $this->unit->id,
            'type' => 'income',
            'student_id' => null,
            'payment_method' => 'cash',
            'transaction_date' => now()->subDay()->toDateString(),
            'total_amount' => 100000,
            'status' => 'completed',
            'created_by' => $this->superAdmin->id,
        ]);

        Transaction::factory()->create([
            'unit_id' => $this->unit->id,
            'type' => 'income',
            'student_id' => null,
            'payment_method' => 'transfer',
            'transaction_date' => now()->toDateString(),
            'total_amount' => 999999,
            'status' => 'completed',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actAsUnit()
            ->get(route('reports.cash-book', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Opening Balance')
            ->assertSee('Rp 100.000,00')
            ->assertDontSee('999.999');
    }

    public function test_user_without_report_permission_cannot_access_new_reports(): void
    {
        $cashier = User::factory()->create(['unit_id' => $this->unit->id]);
        $cashier->assignRole('cashier');

        $this->actAsUnit($cashier)->get(route('reports.ar-outstanding'))->assertForbidden();
        $this->actAsUnit($cashier)->get(route('reports.collection'))->assertForbidden();
        $this->actAsUnit($cashier)->get(route('reports.student-statement'))->assertForbidden();
        $this->actAsUnit($cashier)->get(route('reports.cash-book'))->assertForbidden();
    }

    public function test_daily_report_supports_export_for_finance_columns(): void
    {
        Transaction::factory()->create([
            'unit_id' => $this->unit->id,
            'type' => 'expense',
            'student_id' => null,
            'payment_method' => 'cash',
            'transaction_date' => now()->toDateString(),
            'total_amount' => 125000,
            'status' => 'completed',
            'created_by' => $this->superAdmin->id,
        ]);

        $export = $this->actAsUnit()
            ->get(route('reports.daily.export', [
                'format' => 'csv',
                'date' => now()->toDateString(),
            ]));

        $export->assertOk();
        $export->assertHeader('content-disposition');
        $this->assertStringContainsString('.csv', (string) $export->headers->get('content-disposition'));
    }

    public function test_monthly_report_supports_export_for_finance_columns(): void
    {
        Transaction::factory()->create([
            'unit_id' => $this->unit->id,
            'type' => 'income',
            'student_id' => null,
            'payment_method' => 'cash',
            'transaction_date' => now()->toDateString(),
            'total_amount' => 250000,
            'status' => 'completed',
            'created_by' => $this->superAdmin->id,
        ]);

        $export = $this->actAsUnit()
            ->get(route('reports.monthly.export', [
                'format' => 'csv',
                'month' => now()->month,
                'year' => now()->year,
            ]));

        $export->assertOk();
        $export->assertHeader('content-disposition');
        $this->assertStringContainsString('.csv', (string) $export->headers->get('content-disposition'));
    }
}
