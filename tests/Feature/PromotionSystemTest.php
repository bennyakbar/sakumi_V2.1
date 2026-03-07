<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeMatrix;
use App\Models\FeeType;
use App\Models\PromotionBatch;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentEnrollment;
use App\Models\StudentObligation;
use App\Models\User;
use App\Services\ArrearsService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PromotionSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['unit_id' => 1]);
        $user->assignRole('super_admin');
        $this->actingAs($user);
        session(['current_unit_id' => 1]);
    }

    public function test_batch_promote_retain_graduate_flow_through_endpoints(): void
    {
        $fromYear = AcademicYear::query()->create([
            'unit_id' => 1,
            'code' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'status' => 'active',
            'is_active' => true,
        ]);
        $toYear = AcademicYear::query()->create([
            'unit_id' => 1,
            'code' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'draft',
            'is_active' => false,
        ]);

        $fromClass = SchoolClass::query()->create([
            'unit_id' => 1,
            'name' => 'A 6',
            'level' => 6,
            'academic_year' => '2025/2026',
            'academic_year_id' => $fromYear->id,
            'is_active' => true,
        ]);
        $promotedClass = SchoolClass::query()->create([
            'unit_id' => 1,
            'name' => 'A 7',
            'level' => 7,
            'academic_year' => '2026/2027',
            'academic_year_id' => $toYear->id,
            'is_active' => true,
        ]);
        $retainedClass = SchoolClass::query()->create([
            'unit_id' => 1,
            'name' => 'B 6',
            'level' => 6,
            'academic_year' => '2026/2027',
            'academic_year_id' => $toYear->id,
            'is_active' => true,
        ]);

        $category = StudentCategory::query()->create([
            'unit_id' => 1,
            'code' => 'REG',
            'name' => 'Regular',
            'discount_percentage' => 0,
        ]);

        $s1 = Student::query()->create([
            'unit_id' => 1,
            'nis' => 'S001',
            'name' => 'Promoted Student',
            'class_id' => $fromClass->id,
            'category_id' => $category->id,
            'gender' => 'L',
            'status' => 'active',
            'enrollment_date' => '2025-07-01',
        ]);
        $s2 = Student::query()->create([
            'unit_id' => 1,
            'nis' => 'S002',
            'name' => 'Retained Student',
            'class_id' => $fromClass->id,
            'category_id' => $category->id,
            'gender' => 'P',
            'status' => 'active',
            'enrollment_date' => '2025-07-01',
        ]);
        $s3 = Student::query()->create([
            'unit_id' => 1,
            'nis' => 'S003',
            'name' => 'Graduated Student',
            'class_id' => $fromClass->id,
            'category_id' => $category->id,
            'gender' => 'L',
            'status' => 'active',
            'enrollment_date' => '2025-07-01',
        ]);

        $e1 = StudentEnrollment::query()->create([
            'unit_id' => 1,
            'student_id' => $s1->id,
            'academic_year_id' => $fromYear->id,
            'class_id' => $fromClass->id,
            'start_date' => '2025-07-01',
            'is_current' => true,
            'entry_status' => 'new',
        ]);
        $e2 = StudentEnrollment::query()->create([
            'unit_id' => 1,
            'student_id' => $s2->id,
            'academic_year_id' => $fromYear->id,
            'class_id' => $fromClass->id,
            'start_date' => '2025-07-01',
            'is_current' => true,
            'entry_status' => 'new',
        ]);
        $e3 = StudentEnrollment::query()->create([
            'unit_id' => 1,
            'student_id' => $s3->id,
            'academic_year_id' => $fromYear->id,
            'class_id' => $fromClass->id,
            'start_date' => '2025-07-01',
            'is_current' => true,
            'entry_status' => 'new',
        ]);

        $payload = [
            ['student_id' => $s1->id, 'from_enrollment_id' => $e1->id, 'action' => 'promote', 'to_class_id' => $promotedClass->id],
            ['student_id' => $s2->id, 'from_enrollment_id' => $e2->id, 'action' => 'retain', 'to_class_id' => $retainedClass->id],
            ['student_id' => $s3->id, 'from_enrollment_id' => $e3->id, 'action' => 'graduate'],
        ];

        $this->post(route('master.promotions.store'), [
            'from_academic_year_id' => $fromYear->id,
            'to_academic_year_id' => $toYear->id,
            'effective_date' => '2026-07-01',
            'items_json' => json_encode($payload, JSON_THROW_ON_ERROR),
        ])->assertRedirect();

        $batch = PromotionBatch::query()->firstOrFail();
        $this->post(route('master.promotions.approve', $batch))->assertRedirect();
        $this->post(route('master.promotions.apply', $batch))->assertRedirect();

        $this->assertDatabaseHas('promotion_batches', ['id' => $batch->id, 'status' => 'applied']);
        $this->assertDatabaseHas('promotion_batch_students', ['promotion_batch_id' => $batch->id, 'student_id' => $s1->id, 'is_applied' => true]);
        $this->assertDatabaseHas('promotion_batch_students', ['promotion_batch_id' => $batch->id, 'student_id' => $s2->id, 'is_applied' => true]);
        $this->assertDatabaseHas('promotion_batch_students', ['promotion_batch_id' => $batch->id, 'student_id' => $s3->id, 'is_applied' => true]);

        $this->assertDatabaseHas('student_enrollments', [
            'id' => $e1->id,
            'is_current' => false,
            'exit_status' => 'promoted',
        ]);
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $e2->id,
            'is_current' => false,
            'exit_status' => 'retained',
        ]);
        $this->assertDatabaseHas('student_enrollments', [
            'id' => $e3->id,
            'is_current' => false,
            'exit_status' => 'graduated',
        ]);

        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $s1->id,
            'academic_year_id' => $toYear->id,
            'class_id' => $promotedClass->id,
            'is_current' => true,
            'entry_status' => 'promoted',
        ]);
        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $s2->id,
            'academic_year_id' => $toYear->id,
            'class_id' => $retainedClass->id,
            'is_current' => true,
            'entry_status' => 'retained',
        ]);
        $this->assertDatabaseMissing('student_enrollments', [
            'student_id' => $s3->id,
            'academic_year_id' => $toYear->id,
        ]);

        $this->assertDatabaseHas('students', ['id' => $s1->id, 'class_id' => $promotedClass->id, 'status' => 'active']);
        $this->assertDatabaseHas('students', ['id' => $s2->id, 'class_id' => $retainedClass->id, 'status' => 'active']);
        $this->assertDatabaseHas('students', ['id' => $s3->id, 'status' => 'graduated']);
    }

    public function test_obligation_billing_snapshot_uses_enrollment_at_period_date(): void
    {
        $fromYear = AcademicYear::query()->create([
            'unit_id' => 1,
            'code' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'status' => 'active',
            'is_active' => true,
        ]);
        $toYear = AcademicYear::query()->create([
            'unit_id' => 1,
            'code' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'draft',
            'is_active' => false,
        ]);

        $classFrom = SchoolClass::query()->create([
            'unit_id' => 1,
            'name' => 'A 1',
            'level' => 1,
            'academic_year' => '2025/2026',
            'academic_year_id' => $fromYear->id,
            'is_active' => true,
        ]);
        $classTo = SchoolClass::query()->create([
            'unit_id' => 1,
            'name' => 'A 2',
            'level' => 2,
            'academic_year' => '2026/2027',
            'academic_year_id' => $toYear->id,
            'is_active' => true,
        ]);
        $category = StudentCategory::query()->create([
            'unit_id' => 1,
            'code' => 'REG',
            'name' => 'Regular',
            'discount_percentage' => 0,
        ]);

        $student = Student::query()->create([
            'unit_id' => 1,
            'nis' => 'S100',
            'name' => 'Snapshot Student',
            'class_id' => $classFrom->id,
            'category_id' => $category->id,
            'gender' => 'L',
            'status' => 'active',
            'enrollment_date' => '2025-07-01',
        ]);

        $enrollmentFrom = StudentEnrollment::query()->create([
            'unit_id' => 1,
            'student_id' => $student->id,
            'academic_year_id' => $fromYear->id,
            'class_id' => $classFrom->id,
            'start_date' => '2025-07-01',
            'end_date' => null,
            'is_current' => true,
            'entry_status' => 'new',
        ]);

        $feeType = FeeType::query()->create([
            'unit_id' => 1,
            'code' => 'SPP',
            'name' => 'SPP',
            'is_monthly' => true,
            'is_active' => true,
        ]);

        FeeMatrix::query()->create([
            'unit_id' => 1,
            'fee_type_id' => $feeType->id,
            'class_id' => $classFrom->id,
            'category_id' => null,
            'amount' => 100000,
            'effective_from' => '2025-01-01',
            'effective_to' => '2026-06-30',
            'is_active' => true,
        ]);
        FeeMatrix::query()->create([
            'unit_id' => 1,
            'fee_type_id' => $feeType->id,
            'class_id' => $classTo->id,
            'category_id' => null,
            'amount' => 200000,
            'effective_from' => '2026-07-01',
            'effective_to' => null,
            'is_active' => true,
        ]);

        app(ArrearsService::class)->generateMonthlyObligations(6, 2026);

        $enrollmentFrom->update([
            'is_current' => false,
            'end_date' => '2026-06-30',
            'exit_status' => 'promoted',
        ]);
        $enrollmentTo = StudentEnrollment::query()->create([
            'unit_id' => 1,
            'student_id' => $student->id,
            'academic_year_id' => $toYear->id,
            'class_id' => $classTo->id,
            'start_date' => '2026-07-01',
            'is_current' => true,
            'entry_status' => 'promoted',
            'previous_enrollment_id' => $enrollmentFrom->id,
        ]);
        $student->update(['class_id' => $classTo->id]);

        app(ArrearsService::class)->generateMonthlyObligations(8, 2026);

        $june = StudentObligation::query()
            ->where('student_id', $student->id)
            ->where('month', 6)
            ->where('year', 2026)
            ->firstOrFail();
        $august = StudentObligation::query()
            ->where('student_id', $student->id)
            ->where('month', 8)
            ->where('year', 2026)
            ->firstOrFail();

        $this->assertSame((int) $fromYear->id, (int) $june->academic_year_id);
        $this->assertSame((int) $enrollmentFrom->id, (int) $june->student_enrollment_id);
        $this->assertSame((int) $classFrom->id, (int) $june->class_id_snapshot);
        $this->assertSame('100000.00', (string) $june->amount);

        $this->assertSame((int) $toYear->id, (int) $august->academic_year_id);
        $this->assertSame((int) $enrollmentTo->id, (int) $august->student_enrollment_id);
        $this->assertSame((int) $classTo->id, (int) $august->class_id_snapshot);
        $this->assertSame('200000.00', (string) $august->amount);
    }

    public function test_promotion_batch_can_be_created_from_csv_upload(): void
    {
        $fromYear = AcademicYear::query()->create([
            'unit_id' => 1,
            'code' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'status' => 'active',
            'is_active' => true,
        ]);
        $toYear = AcademicYear::query()->create([
            'unit_id' => 1,
            'code' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'draft',
            'is_active' => false,
        ]);

        $fromClass = SchoolClass::query()->create([
            'unit_id' => 1,
            'name' => 'A 3',
            'level' => 3,
            'academic_year' => '2025/2026',
            'academic_year_id' => $fromYear->id,
            'is_active' => true,
        ]);
        $toClass = SchoolClass::query()->create([
            'unit_id' => 1,
            'name' => 'A 4',
            'level' => 4,
            'academic_year' => '2026/2027',
            'academic_year_id' => $toYear->id,
            'is_active' => true,
        ]);
        $category = StudentCategory::query()->create([
            'unit_id' => 1,
            'code' => 'REG',
            'name' => 'Regular',
            'discount_percentage' => 0,
        ]);
        $student = Student::query()->create([
            'unit_id' => 1,
            'nis' => 'S777',
            'name' => 'CSV Student',
            'class_id' => $fromClass->id,
            'category_id' => $category->id,
            'gender' => 'L',
            'status' => 'active',
            'enrollment_date' => '2025-07-01',
        ]);
        $enrollment = StudentEnrollment::query()->create([
            'unit_id' => 1,
            'student_id' => $student->id,
            'academic_year_id' => $fromYear->id,
            'class_id' => $fromClass->id,
            'start_date' => '2025-07-01',
            'is_current' => true,
            'entry_status' => 'new',
        ]);

        $csv = "student_id,from_enrollment_id,action,to_class_id,reason\n";
        $csv .= "{$student->id},{$enrollment->id},promote,{$toClass->id},CSV import\n";
        $file = UploadedFile::fake()->createWithContent('promotions.csv', $csv);

        $this->post(route('master.promotions.store'), [
            'from_academic_year_id' => $fromYear->id,
            'to_academic_year_id' => $toYear->id,
            'effective_date' => '2026-07-01',
            'items_csv' => $file,
        ])->assertRedirect();

        $batch = PromotionBatch::query()->firstOrFail();
        $this->assertDatabaseHas('promotion_batch_students', [
            'promotion_batch_id' => $batch->id,
            'student_id' => $student->id,
            'from_enrollment_id' => $enrollment->id,
            'action' => 'promote',
            'to_class_id' => $toClass->id,
        ]);
    }
}
