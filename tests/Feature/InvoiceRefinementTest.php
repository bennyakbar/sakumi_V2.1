<?php

namespace Tests\Feature;

use App\Models\AccountingEvent;
use App\Models\FeeType;
use App\Models\FiscalPeriod;
use App\Models\Invoice;
use App\Models\JournalEntryV2;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Settlement;
use App\Models\SettlementAllocation;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentObligation;
use App\Models\Unit;
use App\Models\User;
use App\Services\InvoiceService;
use Database\Seeders\AccountMappingsSeeder;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceRefinementTest extends TestCase
{
    use RefreshDatabase;

    private Unit $mi;
    private Unit $ra;
    private User $miAdmin;
    private User $raAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);
        $this->seed(AccountMappingsSeeder::class);
        Setting::set('academic_year_current', '2025/2026');

        $this->mi = Unit::query()->where('code', 'MI')->firstOrFail();
        $this->ra = Unit::query()->where('code', 'RA')->firstOrFail();

        $this->miAdmin = User::factory()->create(['unit_id' => $this->mi->id]);
        $this->miAdmin->assignRole('super_admin');
        $this->raAdmin = User::factory()->create(['unit_id' => $this->ra->id]);
        $this->raAdmin->assignRole('super_admin');
    }

    private function actAsUnit(User $user, Unit $unit): self
    {
        return $this->actingAs($user)->withSession(['current_unit_id' => $unit->id]);
    }

    private function createStudentWithObligation(Unit $unit, User $creator): array
    {
        $class = SchoolClass::factory()->create(['unit_id' => $unit->id]);
        $category = StudentCategory::factory()->create(['unit_id' => $unit->id]);
        $student = Student::factory()->create([
            'unit_id' => $unit->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);
        $feeType = FeeType::factory()->create([
            'unit_id' => $unit->id,
            'is_monthly' => true,
            'is_active' => true,
        ]);
        $obligation = StudentObligation::query()->create([
            'unit_id' => $unit->id,
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'month' => (int) now()->month,
            'year' => (int) now()->year,
            'amount' => 100000,
            'is_paid' => false,
            'paid_amount' => 0,
        ]);

        return [$student, $obligation, $creator];
    }

    public function test_invoice_numbering_is_sequential_per_unit(): void
    {
        $this->actAsUnit($this->miAdmin, $this->mi);
        [$miStudent, $miObligation] = $this->createStudentWithObligation($this->mi, $this->miAdmin);

        $miInvoice = app(InvoiceService::class)->createInvoice(
            studentId: $miStudent->id,
            obligationIds: [$miObligation->id],
            data: [
                'due_date' => now()->addDays(7)->toDateString(),
            ],
            userId: $this->miAdmin->id,
        );

        $this->actAsUnit($this->raAdmin, $this->ra);
        [$raStudent, $raObligation] = $this->createStudentWithObligation($this->ra, $this->raAdmin);

        $raInvoice = app(InvoiceService::class)->createInvoice(
            studentId: $raStudent->id,
            obligationIds: [$raObligation->id],
            data: [
                'due_date' => now()->addDays(7)->toDateString(),
            ],
            userId: $this->raAdmin->id,
        );

        $this->assertStringContainsString('INV-MI-'.now()->year.'-000001', $miInvoice->invoice_number);
        $this->assertStringContainsString('INV-RA-'.now()->year.'-000001', $raInvoice->invoice_number);
    }

    public function test_destroy_invoice_voids_partially_paid_invoice_with_single_settlement_allocation(): void
    {
        $this->actAsUnit($this->miAdmin, $this->mi);

        $class = SchoolClass::factory()->create(['unit_id' => $this->mi->id]);
        $category = StudentCategory::factory()->create(['unit_id' => $this->mi->id]);
        $student = Student::factory()->create([
            'unit_id' => $this->mi->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $invoice = Invoice::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $student->id,
            'total_amount' => 200000,
            'paid_amount' => 100000,
            'status' => 'partially_paid',
            'created_by' => $this->miAdmin->id,
        ]);

        $settlement = Settlement::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $student->id,
            'status' => 'completed',
            'total_amount' => 100000,
            'allocated_amount' => 100000,
            'created_by' => $this->miAdmin->id,
        ]);

        SettlementAllocation::query()->create([
            'settlement_id' => $settlement->id,
            'invoice_id' => $invoice->id,
            'amount' => 100000,
        ]);

        // Create the original accounting event so the reversal engine can find it
        $period = FiscalPeriod::query()->create([
            'unit_id' => $this->mi->id,
            'period_key' => now()->format('Y-m'),
            'starts_on' => now()->startOfMonth()->toDateString(),
            'ends_on' => now()->endOfMonth()->toDateString(),
            'is_locked' => false,
        ]);

        $event = AccountingEvent::query()->create([
            'unit_id' => $this->mi->id,
            'event_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'event_type' => 'settlement.applied',
            'source_type' => 'settlement',
            'source_id' => $settlement->id,
            'effective_date' => now()->toDateString(),
            'occurred_at' => now(),
            'fiscal_period_id' => $period->id,
            'status' => 'posted',
            'created_by' => $this->miAdmin->id,
            'payload' => [],
        ]);

        // Create balanced journal entries for the original event
        $cashAccount = \App\Models\ChartOfAccount::query()->where('code', '110200')->first();
        $receivableAccount = \App\Models\ChartOfAccount::query()->where('code', '110100')->first();

        JournalEntryV2::query()->create([
            'accounting_event_id' => $event->id,
            'account_id' => $cashAccount->id,
            'account_code' => '110200',
            'line_no' => 1,
            'entry_date' => now()->toDateString(),
            'debit' => 100000,
            'credit' => 0,
            'description' => 'Cash from settlement',
        ]);
        JournalEntryV2::query()->create([
            'accounting_event_id' => $event->id,
            'account_id' => $receivableAccount->id,
            'account_code' => '110100',
            'line_no' => 2,
            'entry_date' => now()->toDateString(),
            'debit' => 0,
            'credit' => 100000,
            'description' => 'Receivable from settlement',
        ]);

        $this->delete(route('invoices.destroy', $invoice), [
            'cancellation_reason' => 'Data entry correction',
        ])->assertRedirect(route('invoices.index'));

        $invoice->refresh();
        $settlement->refresh();

        $this->assertSame('cancelled', $invoice->status);
        $this->assertStringContainsString('VOID:', (string) $invoice->notes);
        $this->assertSame('void', $settlement->status);
    }
}
