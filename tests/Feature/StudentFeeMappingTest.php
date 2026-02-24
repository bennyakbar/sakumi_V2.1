<?php

namespace Tests\Feature;

use App\Models\FeeMatrix;
use App\Models\FeeType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentFeeMapping;
use App\Models\Unit;
use App\Models\User;
use App\Services\ArrearsService;
use App\Services\InvoiceService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFeeMappingTest extends TestCase
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

    private function actAsUnit(): self
    {
        return $this->actingAs($this->superAdmin)->withSession(['current_unit_id' => $this->unit->id]);
    }

    public function test_generate_monthly_obligations_prioritizes_student_mapping_over_global_matrix(): void
    {
        $class = SchoolClass::factory()->create(['unit_id' => $this->unit->id]);
        $category = StudentCategory::factory()->create(['unit_id' => $this->unit->id]);
        $student = Student::factory()->create([
            'unit_id' => $this->unit->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $feeType = FeeType::factory()->create(['unit_id' => $this->unit->id, 'is_monthly' => true, 'is_active' => true]);

        FeeMatrix::query()->create([
            'unit_id' => $this->unit->id,
            'fee_type_id' => $feeType->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'amount' => 100000,
            'effective_from' => now()->subMonths(3)->toDateString(),
            'effective_to' => null,
            'is_active' => true,
        ]);

        $mappedMatrix = FeeMatrix::query()->create([
            'unit_id' => $this->unit->id,
            'fee_type_id' => $feeType->id,
            'class_id' => null,
            'category_id' => null,
            'amount' => 150000,
            'effective_from' => now()->subMonths(2)->toDateString(),
            'effective_to' => null,
            'is_active' => true,
        ]);

        StudentFeeMapping::query()->create([
            'unit_id' => $this->unit->id,
            'student_id' => $student->id,
            'fee_matrix_id' => $mappedMatrix->id,
            'effective_from' => now()->subMonth()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $this->actAsUnit();
        app(ArrearsService::class)->generateMonthlyObligations((int) now()->month, (int) now()->year);

        $this->assertDatabaseHas('student_obligations', [
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'month' => (int) now()->month,
            'year' => (int) now()->year,
            'amount' => 150000,
        ]);
    }

    public function test_generate_monthly_obligations_falls_back_to_global_matrix_when_no_mapping_exists(): void
    {
        $class = SchoolClass::factory()->create(['unit_id' => $this->unit->id]);
        $category = StudentCategory::factory()->create(['unit_id' => $this->unit->id]);
        $student = Student::factory()->create([
            'unit_id' => $this->unit->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $feeType = FeeType::factory()->create(['unit_id' => $this->unit->id, 'is_monthly' => true, 'is_active' => true]);
        FeeMatrix::query()->create([
            'unit_id' => $this->unit->id,
            'fee_type_id' => $feeType->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'amount' => 120000,
            'effective_from' => now()->subMonths(3)->toDateString(),
            'effective_to' => null,
            'is_active' => true,
        ]);

        $this->actAsUnit();
        app(ArrearsService::class)->generateMonthlyObligations((int) now()->month, (int) now()->year);

        $this->assertDatabaseHas('student_obligations', [
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'month' => (int) now()->month,
            'year' => (int) now()->year,
            'amount' => 120000,
        ]);
    }

    public function test_invoice_generation_triggers_obligation_generation_with_student_mapping_priority(): void
    {
        $class = SchoolClass::factory()->create(['unit_id' => $this->unit->id]);
        $category = StudentCategory::factory()->create(['unit_id' => $this->unit->id]);
        $student = Student::factory()->create([
            'unit_id' => $this->unit->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $feeType = FeeType::factory()->create(['unit_id' => $this->unit->id, 'is_monthly' => true, 'is_active' => true]);

        FeeMatrix::query()->create([
            'unit_id' => $this->unit->id,
            'fee_type_id' => $feeType->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'amount' => 90000,
            'effective_from' => now()->subMonths(2)->toDateString(),
            'effective_to' => null,
            'is_active' => true,
        ]);

        $mappedMatrix = FeeMatrix::query()->create([
            'unit_id' => $this->unit->id,
            'fee_type_id' => $feeType->id,
            'class_id' => null,
            'category_id' => null,
            'amount' => 175000,
            'effective_from' => now()->subMonths(2)->toDateString(),
            'effective_to' => null,
            'is_active' => true,
        ]);

        StudentFeeMapping::query()->create([
            'unit_id' => $this->unit->id,
            'student_id' => $student->id,
            'fee_matrix_id' => $mappedMatrix->id,
            'effective_from' => now()->subMonth()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $this->actAsUnit();
        app(InvoiceService::class)->generateInvoices(
            periodType: 'monthly',
            periodIdentifier: now()->format('Y-m'),
            userId: $this->superAdmin->id,
            classId: $class->id,
            categoryId: $category->id,
            dueDate: now()->addDays(14)->toDateString(),
        );

        $this->assertDatabaseHas('student_obligations', [
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'amount' => 175000,
        ]);
        $this->assertDatabaseHas('invoices', [
            'student_id' => $student->id,
            'total_amount' => 175000,
        ]);
    }

    public function test_store_mapping_rejects_overlap_for_same_fee_type(): void
    {
        $class = SchoolClass::factory()->create(['unit_id' => $this->unit->id]);
        $category = StudentCategory::factory()->create(['unit_id' => $this->unit->id]);
        $student = Student::factory()->create([
            'unit_id' => $this->unit->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $feeType = FeeType::factory()->create(['unit_id' => $this->unit->id, 'is_monthly' => true, 'is_active' => true]);
        $matrixA = FeeMatrix::query()->create([
            'unit_id' => $this->unit->id,
            'fee_type_id' => $feeType->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'amount' => 100000,
            'effective_from' => now()->subMonths(3)->toDateString(),
            'effective_to' => null,
            'is_active' => true,
        ]);

        $matrixB = FeeMatrix::query()->create([
            'unit_id' => $this->unit->id,
            'fee_type_id' => $feeType->id,
            'class_id' => null,
            'category_id' => null,
            'amount' => 110000,
            'effective_from' => now()->subMonths(2)->toDateString(),
            'effective_to' => null,
            'is_active' => true,
        ]);

        StudentFeeMapping::query()->create([
            'unit_id' => $this->unit->id,
            'student_id' => $student->id,
            'fee_matrix_id' => $matrixA->id,
            'effective_from' => now()->subMonth()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $this->actAsUnit()
            ->post(route('master.students.fee-mappings.store', $student), [
                'fee_matrix_id' => $matrixB->id,
                'effective_from' => now()->subDays(7)->toDateString(),
                'effective_to' => null,
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('effective_from');
    }

    public function test_generate_monthly_obligations_updates_amount_on_rerun_if_unpaid_and_not_invoiced(): void
    {
        $class = SchoolClass::factory()->create(['unit_id' => $this->unit->id]);
        $category = StudentCategory::factory()->create(['unit_id' => $this->unit->id]);
        $student = Student::factory()->create([
            'unit_id' => $this->unit->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $feeType = FeeType::factory()->create(['unit_id' => $this->unit->id, 'is_monthly' => true, 'is_active' => true]);
        $matrix = FeeMatrix::query()->create([
            'unit_id' => $this->unit->id,
            'fee_type_id' => $feeType->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'amount' => 100000,
            'effective_from' => now()->subMonths(3)->toDateString(),
            'effective_to' => null,
            'is_active' => true,
        ]);

        $this->actAsUnit();
        app(ArrearsService::class)->generateMonthlyObligations((int) now()->month, (int) now()->year);
        $this->assertDatabaseHas('student_obligations', [
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'amount' => 100000,
        ]);

        $matrix->update(['amount' => 150000]);
        app(ArrearsService::class)->generateMonthlyObligations((int) now()->month, (int) now()->year);

        $this->assertDatabaseHas('student_obligations', [
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'month' => (int) now()->month,
            'year' => (int) now()->year,
            'amount' => 150000,
        ]);
    }

    public function test_generate_monthly_obligations_keeps_amount_if_obligation_already_invoiced(): void
    {
        $class = SchoolClass::factory()->create(['unit_id' => $this->unit->id]);
        $category = StudentCategory::factory()->create(['unit_id' => $this->unit->id]);
        $student = Student::factory()->create([
            'unit_id' => $this->unit->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $feeType = FeeType::factory()->create(['unit_id' => $this->unit->id, 'is_monthly' => true, 'is_active' => true]);
        $matrix = FeeMatrix::query()->create([
            'unit_id' => $this->unit->id,
            'fee_type_id' => $feeType->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'amount' => 100000,
            'effective_from' => now()->subMonths(3)->toDateString(),
            'effective_to' => null,
            'is_active' => true,
        ]);

        $this->actAsUnit();
        app(ArrearsService::class)->generateMonthlyObligations((int) now()->month, (int) now()->year);

        $obligation = \App\Models\StudentObligation::query()
            ->where('student_id', $student->id)
            ->where('fee_type_id', $feeType->id)
            ->where('month', (int) now()->month)
            ->where('year', (int) now()->year)
            ->firstOrFail();

        $invoice = Invoice::factory()->create([
            'unit_id' => $this->unit->id,
            'student_id' => $student->id,
            'created_by' => $this->superAdmin->id,
            'total_amount' => 100000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        InvoiceItem::query()->create([
            'invoice_id' => $invoice->id,
            'student_obligation_id' => $obligation->id,
            'fee_type_id' => $feeType->id,
            'description' => $feeType->name,
            'amount' => 100000,
            'month' => (int) now()->month,
            'year' => (int) now()->year,
        ]);

        $matrix->update(['amount' => 175000]);
        app(ArrearsService::class)->generateMonthlyObligations((int) now()->month, (int) now()->year);

        $obligation->refresh();
        $this->assertSame(100000.0, (float) $obligation->amount);
    }
}
