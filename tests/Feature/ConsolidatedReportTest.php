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

class ConsolidatedReportTest extends TestCase
{
    use RefreshDatabase;

    private Unit $mi;
    private Unit $ra;
    private User $superAdmin;
    private User $operatorTu;
    private User $kepalaSekolah;
    private Student $miStudent;
    private Student $raStudent;
    private Transaction $miTransaction;
    private Transaction $raTransaction;
    private Settlement $miSettlement;
    private Settlement $raSettlement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->mi = Unit::where('code', 'MI')->first();
        $this->ra = Unit::where('code', 'RA')->first();

        $this->superAdmin = User::factory()->create(['unit_id' => $this->mi->id]);
        $this->superAdmin->assignRole('super_admin');

        $this->operatorTu = User::factory()->create(['unit_id' => $this->mi->id]);
        $this->operatorTu->assignRole('operator_tu');

        $this->kepalaSekolah = User::factory()->create(['unit_id' => $this->mi->id]);
        $this->kepalaSekolah->assignRole('kepala_sekolah');

        // Create properly scoped students
        $miClass = SchoolClass::factory()->create(['unit_id' => $this->mi->id]);
        $miCat = StudentCategory::factory()->create(['unit_id' => $this->mi->id]);
        $this->miStudent = Student::factory()->create([
            'unit_id' => $this->mi->id,
            'class_id' => $miClass->id,
            'category_id' => $miCat->id,
        ]);

        $raClass = SchoolClass::factory()->create(['unit_id' => $this->ra->id]);
        $raCat = StudentCategory::factory()->create(['unit_id' => $this->ra->id]);
        $this->raStudent = Student::factory()->create([
            'unit_id' => $this->ra->id,
            'class_id' => $raClass->id,
            'category_id' => $raCat->id,
        ]);

        // Create transactions in both units with correct students
        $this->miTransaction = Transaction::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => null,
            'status' => 'completed',
            'type' => 'income',
            'transaction_date' => today(),
            'total_amount' => 100000,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->raTransaction = Transaction::factory()->create([
            'unit_id' => $this->ra->id,
            'student_id' => null,
            'status' => 'completed',
            'type' => 'income',
            'transaction_date' => today(),
            'total_amount' => 200000,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->miSettlement = Settlement::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $this->miStudent->id,
            'payment_date' => today()->toDateString(),
            'total_amount' => 100000,
            'allocated_amount' => 100000,
            'status' => 'completed',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->raSettlement = Settlement::factory()->create([
            'unit_id' => $this->ra->id,
            'student_id' => $this->raStudent->id,
            'payment_date' => today()->toDateString(),
            'total_amount' => 200000,
            'allocated_amount' => 200000,
            'status' => 'completed',
            'created_by' => $this->superAdmin->id,
        ]);
    }

    private function actAsUnit(User $user, Unit $unit): self
    {
        return $this->actingAs($user)->withSession(['current_unit_id' => $unit->id]);
    }

    // ─── Daily Report ────────────────────────────────────────────

    public function test_super_admin_daily_report_consolidated_sees_all_units(): void
    {
        $this->actAsUnit($this->superAdmin, $this->mi)
            ->get(route('reports.daily', ['scope' => 'all', 'date' => today()->toDateString()]))
            ->assertOk()
            ->assertSee($this->miSettlement->settlement_number)
            ->assertSee($this->raSettlement->settlement_number)
            ->assertSee($this->miTransaction->transaction_number)
            ->assertSee($this->raTransaction->transaction_number)
            ->assertSee(__('app.unit.all'));
    }

    public function test_super_admin_daily_report_default_scope_sees_own_unit(): void
    {
        $this->actAsUnit($this->superAdmin, $this->mi)
            ->get(route('reports.daily', ['date' => today()->toDateString()]))
            ->assertOk()
            ->assertSee($this->miSettlement->settlement_number)
            ->assertSee($this->miTransaction->transaction_number)
            ->assertDontSee($this->raSettlement->settlement_number)
            ->assertDontSee($this->raTransaction->transaction_number);
    }

    public function test_non_super_admin_daily_report_scope_all_ignored(): void
    {
        $this->actAsUnit($this->operatorTu, $this->mi)
            ->get(route('reports.daily', ['scope' => 'all', 'date' => today()->toDateString()]))
            ->assertOk()
            ->assertSee($this->miSettlement->settlement_number)
            ->assertSee($this->miTransaction->transaction_number)
            ->assertDontSee($this->raSettlement->settlement_number)
            ->assertDontSee($this->raTransaction->transaction_number)
            ->assertDontSee('All Units');
    }

    public function test_daily_report_includes_completed_settlements_and_transactions(): void
    {
        $invoice = Invoice::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $this->miStudent->id,
            'total_amount' => 150000,
            'created_by' => $this->superAdmin->id,
        ]);

        $settlement = Settlement::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $this->miStudent->id,
            'payment_date' => today()->toDateString(),
            'total_amount' => 150000,
            'allocated_amount' => 150000,
            'created_by' => $this->superAdmin->id,
            'status' => 'completed',
        ]);

        SettlementAllocation::create([
            'settlement_id' => $settlement->id,
            'invoice_id' => $invoice->id,
            'amount' => 150000,
        ]);

        // Total: miSettlement (100k) + new settlement (150k) + miTransaction (100k) = 350k
        $this->actAsUnit($this->superAdmin, $this->mi)
            ->get(route('reports.daily', ['date' => today()->toDateString()]))
            ->assertOk()
            ->assertSee($settlement->settlement_number)
            ->assertSee($invoice->invoice_number)
            ->assertSee($this->miTransaction->transaction_number)
            ->assertSee('Settlement')
            ->assertSee('Transaksi Langsung')
            ->assertSee('Rp 350.000');
    }

    // ─── Monthly Report ──────────────────────────────────────────

    public function test_super_admin_monthly_report_consolidated_sees_all_units(): void
    {
        $this->actAsUnit($this->superAdmin, $this->mi)
            ->get(route('reports.monthly', [
                'scope' => 'all',
                'month' => today()->month,
                'year' => today()->year,
            ]))
            ->assertOk()
            ->assertSee($this->miTransaction->transaction_number)
            ->assertSee($this->raTransaction->transaction_number)
            ->assertSee($this->miSettlement->settlement_number)
            ->assertSee($this->raSettlement->settlement_number)
            ->assertSee(__('app.unit.all'));
    }

    public function test_non_super_admin_monthly_report_scope_all_ignored(): void
    {
        $this->actAsUnit($this->operatorTu, $this->mi)
            ->get(route('reports.monthly', [
                'scope' => 'all',
                'month' => today()->month,
                'year' => today()->year,
            ]))
            ->assertOk()
            ->assertSee($this->miTransaction->transaction_number)
            ->assertSee($this->miSettlement->settlement_number)
            ->assertDontSee($this->raTransaction->transaction_number)
            ->assertDontSee($this->raSettlement->settlement_number);
    }

    // ─── Arrears Report ──────────────────────────────────────────

    public function test_super_admin_arrears_report_consolidated_sees_all_units(): void
    {
        Invoice::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $this->miStudent->id,
            'total_amount' => 500000,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'due_date' => today()->subDay()->toDateString(),
            'created_by' => $this->superAdmin->id,
        ]);

        Invoice::factory()->create([
            'unit_id' => $this->ra->id,
            'student_id' => $this->raStudent->id,
            'total_amount' => 600000,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'due_date' => today()->subDay()->toDateString(),
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actAsUnit($this->superAdmin, $this->mi)
            ->get(route('reports.arrears', [
                'scope' => 'all',
            ]))
            ->assertOk()
            ->assertSee($this->miStudent->name)
            ->assertSee($this->raStudent->name)
            ->assertSee(__('app.unit.all'));
    }

    public function test_non_super_admin_arrears_report_scope_all_ignored(): void
    {
        Invoice::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $this->miStudent->id,
            'total_amount' => 400000,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'due_date' => today()->subDay()->toDateString(),
            'created_by' => $this->superAdmin->id,
        ]);

        Invoice::factory()->create([
            'unit_id' => $this->ra->id,
            'student_id' => $this->raStudent->id,
            'total_amount' => 450000,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'due_date' => today()->subDay()->toDateString(),
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actAsUnit($this->operatorTu, $this->mi)
            ->get(route('reports.arrears', [
                'scope' => 'all',
            ]))
            ->assertOk()
            ->assertSee($this->miStudent->name)
            ->assertDontSee($this->raStudent->name);
    }

    public function test_view_only_role_does_not_see_pay_now_link_in_arrears_report(): void
    {
        Invoice::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $this->miStudent->id,
            'total_amount' => 300000,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'due_date' => today()->subDay()->toDateString(),
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actAsUnit($this->kepalaSekolah, $this->mi)
            ->get(route('reports.arrears'))
            ->assertOk()
            ->assertSee($this->miStudent->name)
            ->assertDontSee('Pay Now');
    }

    public function test_arrears_export_csv_downloads_file(): void
    {
        Invoice::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $this->miStudent->id,
            'total_amount' => 123000,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'due_date' => today()->subDay()->toDateString(),
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actAsUnit($this->superAdmin, $this->mi)
            ->get(route('reports.arrears.export', [
                'format' => 'csv',
            ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('.csv', (string) $response->headers->get('content-disposition'));
    }

    public function test_end_to_end_arrears_pay_now_partial_then_full_payment(): void
    {
        $invoice = Invoice::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $this->miStudent->id,
            'invoice_number' => 'INV-E2E-000001',
            'total_amount' => 1000000,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'due_date' => today()->subDay()->toDateString(),
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actAsUnit($this->superAdmin, $this->mi)
            ->post(route('settlements.store'), [
                'student_id' => $this->miStudent->id,
                'invoice_id' => $invoice->id,
                'payment_date' => today()->toDateString(),
                'payment_method' => 'cash',
                'amount' => 300000,
            ])
            ->assertRedirect();

        $this->actAsUnit($this->superAdmin, $this->mi)
            ->get(route('reports.daily', ['date' => today()->toDateString()]))
            ->assertOk()
            ->assertSee('Rp 300.000');

        $this->actAsUnit($this->superAdmin, $this->mi)
            ->get(route('reports.arrears'))
            ->assertOk()
            ->assertSee('INV-E2E-000001')
            ->assertSee('Rp 700.000');

        $this->actAsUnit($this->superAdmin, $this->mi)
            ->post(route('settlements.store'), [
                'student_id' => $this->miStudent->id,
                'invoice_id' => $invoice->id,
                'payment_date' => today()->toDateString(),
                'payment_method' => 'cash',
                'amount' => 700000,
            ])
            ->assertRedirect();

        $this->actAsUnit($this->superAdmin, $this->mi)
            ->get(route('reports.arrears'))
            ->assertOk()
            ->assertDontSee('INV-E2E-000001');
    }

    public function test_settlement_create_allows_non_overdue_invoice_prefill(): void
    {
        $invoice = Invoice::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $this->miStudent->id,
            'invoice_number' => 'INV-FUTURE-000001',
            'total_amount' => 800000,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'due_date' => today()->addDays(10)->toDateString(),
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actAsUnit($this->superAdmin, $this->mi)
            ->get(route('settlements.create', [
                'student_id' => $this->miStudent->id,
                'invoice_id' => $invoice->id,
            ]))
            ->assertOk()
            ->assertSee('INV-FUTURE-000001');
    }

    // ─── Dashboard ───────────────────────────────────────────────

    public function test_super_admin_dashboard_consolidated_sees_all_units(): void
    {
        $this->actAsUnit($this->superAdmin, $this->mi)
            ->get(route('dashboard', ['scope' => 'all']))
            ->assertOk()
            ->assertSee(__('dashboard.all_units'))
            ->assertSee(__('dashboard.breakdown'))
            ->assertSee(__('dashboard.current_unit'));
    }

    public function test_non_super_admin_dashboard_scope_all_ignored(): void
    {
        $this->actAsUnit($this->operatorTu, $this->mi)
            ->get(route('dashboard', ['scope' => 'all']))
            ->assertOk()
            ->assertDontSee(__('dashboard.all_units'))
            ->assertDontSee(__('dashboard.breakdown'));
    }

    public function test_super_admin_dashboard_default_scope_no_breakdown(): void
    {
        $this->actAsUnit($this->superAdmin, $this->mi)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Per-Unit Breakdown');
    }

    // ─── Toggle preserves filters ────────────────────────────────

    public function test_daily_toggle_preserves_date_filter(): void
    {
        $response = $this->actAsUnit($this->superAdmin, $this->mi)
            ->get(route('reports.daily', ['date' => '2026-01-15', 'scope' => 'all']))
            ->assertOk();

        $response->assertSee('scope=unit');
        $response->assertSee('date=2026-01-15');
    }

    public function test_monthly_toggle_preserves_month_year_filter(): void
    {
        $response = $this->actAsUnit($this->superAdmin, $this->mi)
            ->get(route('reports.monthly', ['month' => 3, 'year' => 2026, 'scope' => 'all']))
            ->assertOk();

        $response->assertSee('scope=unit');
        $response->assertSee('month=3');
        $response->assertSee('year=2026');
    }
}
