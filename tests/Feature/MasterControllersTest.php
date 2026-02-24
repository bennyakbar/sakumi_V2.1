<?php

namespace Tests\Feature;

use App\Models\FeeMatrix;
use App\Models\FeeType;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterControllersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);
        session(['current_unit_id' => $user->unit_id]);
    }

    public function test_master_pages_can_be_rendered(): void
    {
        $this->get(route('master.classes.index'))->assertOk();
        $this->get(route('master.categories.index'))->assertOk();
        $this->get(route('master.fee-types.index'))->assertOk();
        $this->get(route('master.fee-matrix.index'))->assertOk();
        $this->get(route('master.students.index'))->assertOk();
    }

    public function test_class_crud(): void
    {
        $this->post(route('master.classes.store'), [
            'name' => '1A',
            'level' => 1,
            'academic_year' => '2025/2026',
            'is_active' => true,
        ])->assertRedirect(route('master.classes.index'));

        $class = SchoolClass::query()->firstOrFail();

        $this->put(route('master.classes.update', $class), [
            'name' => '1A',
            'level' => 2,
            'academic_year' => '2025/2026',
            'is_active' => true,
        ])->assertRedirect(route('master.classes.index'));

        $this->delete(route('master.classes.destroy', $class))
            ->assertRedirect(route('master.classes.index'));

        $this->get(route('master.classes.index'))
            ->assertOk()
            ->assertSee(__('app.button.restore'));
    }

    public function test_category_crud(): void
    {
        $this->post(route('master.categories.store'), [
            'code' => 'reg',
            'name' => 'Regular',
            'discount_percentage' => 0,
        ])->assertRedirect(route('master.categories.index'));

        $category = StudentCategory::query()->firstOrFail();

        $this->put(route('master.categories.update', $category), [
            'code' => 'VIP',
            'name' => 'VIP',
            'discount_percentage' => 10,
        ])->assertRedirect(route('master.categories.index'));

        $this->delete(route('master.categories.destroy', $category))
            ->assertRedirect(route('master.categories.index'));

        $this->get(route('master.categories.index'))
            ->assertOk()
            ->assertSee(__('app.button.restore'));
    }

    public function test_fee_type_and_fee_matrix_crud(): void
    {
        $class = SchoolClass::query()->create([
            'name' => '2A',
            'level' => 2,
            'academic_year' => '2025/2026',
            'is_active' => true,
        ]);
        $category = StudentCategory::query()->create([
            'code' => 'REG',
            'name' => 'Regular',
            'discount_percentage' => 0,
        ]);

        $this->post(route('master.fee-types.store'), [
            'code' => 'SPP',
            'name' => 'SPP',
            'is_monthly' => true,
        ])->assertRedirect(route('master.fee-types.index'));

        $feeType = FeeType::query()->firstOrFail();

        $this->post(route('master.fee-matrix.store'), [
            'fee_type_id' => $feeType->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'amount' => 150000,
            'effective_from' => '2025-01-01',
            'is_active' => true,
        ])->assertRedirect(route('master.fee-matrix.index'));

        $feeMatrix = FeeMatrix::query()->firstOrFail();

        $this->put(route('master.fee-matrix.update', $feeMatrix), [
            'fee_type_id' => $feeType->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'amount' => 175000,
            'effective_from' => '2025-01-01',
            'is_active' => true,
        ])->assertRedirect(route('master.fee-matrix.index'));

        $this->delete(route('master.fee-matrix.destroy', $feeMatrix))
            ->assertRedirect(route('master.fee-matrix.index'));

        $this->get(route('master.fee-matrix.index'))
            ->assertOk()
            ->assertSee(__('app.button.restore'));
    }

    public function test_student_crud_and_show_page(): void
    {
        $class = SchoolClass::query()->create([
            'name' => '3A',
            'level' => 3,
            'academic_year' => '2025/2026',
            'is_active' => true,
        ]);
        $category = StudentCategory::query()->create([
            'code' => 'REG',
            'name' => 'Regular',
            'discount_percentage' => 0,
        ]);

        $this->post(route('master.students.store'), [
            'name' => 'Siti',
            'nis' => '10001',
            'nisn' => '990001',
            'class_id' => $class->id,
            'category_id' => $category->id,
            'gender' => 'P',
            'enrollment_date' => '2025-07-01',
            'status' => 'active',
        ])->assertRedirect(route('master.students.index'));

        $student = Student::query()->firstOrFail();

        $this->get(route('master.students.show', $student))->assertOk();

        $this->put(route('master.students.update', $student), [
            'name' => 'Siti Update',
            'nis' => '10001',
            'nisn' => '990001',
            'class_id' => $class->id,
            'category_id' => $category->id,
            'gender' => 'P',
            'enrollment_date' => '2025-07-01',
            'status' => 'graduated',
        ])->assertRedirect(route('master.students.index'));

        $this->delete(route('master.students.destroy', $student))
            ->assertRedirect(route('master.students.index'));
    }

    public function test_master_soft_deleted_records_can_be_restored(): void
    {
        $class = SchoolClass::query()->create([
            'name' => '4A',
            'level' => 4,
            'academic_year' => '2025/2026',
            'is_active' => true,
        ]);
        $category = StudentCategory::query()->create([
            'code' => 'RST',
            'name' => 'Restore Category',
            'discount_percentage' => 0,
        ]);
        $feeType = FeeType::query()->create([
            'code' => 'RST-FT',
            'name' => 'Restore Fee Type',
            'is_monthly' => true,
            'is_active' => true,
        ]);
        $feeMatrix = FeeMatrix::query()->create([
            'fee_type_id' => $feeType->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'amount' => 125000,
            'effective_from' => '2025-01-01',
            'is_active' => true,
        ]);

        $this->delete(route('master.fee-matrix.destroy', $feeMatrix))
            ->assertRedirect(route('master.fee-matrix.index'));
        $this->delete(route('master.fee-types.destroy', $feeType))
            ->assertRedirect(route('master.fee-types.index'));
        $this->delete(route('master.categories.destroy', $category))
            ->assertRedirect(route('master.categories.index'));
        $this->delete(route('master.classes.destroy', $class))
            ->assertRedirect(route('master.classes.index'));

        $this->assertSoftDeleted('fee_matrix', ['id' => $feeMatrix->id]);
        $this->assertSoftDeleted('fee_types', ['id' => $feeType->id]);
        $this->assertSoftDeleted('student_categories', ['id' => $category->id]);
        $this->assertSoftDeleted('classes', ['id' => $class->id]);

        $this->post(route('master.classes.restore', $class->id))
            ->assertRedirect(route('master.classes.index'));
        $this->post(route('master.categories.restore', $category->id))
            ->assertRedirect(route('master.categories.index'));
        $this->post(route('master.fee-types.restore', $feeType->id))
            ->assertRedirect(route('master.fee-types.index'));
        $this->post(route('master.fee-matrix.restore', $feeMatrix->id))
            ->assertRedirect(route('master.fee-matrix.index'));

        $this->assertDatabaseHas('classes', ['id' => $class->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('student_categories', ['id' => $category->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('fee_types', ['id' => $feeType->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('fee_matrix', ['id' => $feeMatrix->id, 'deleted_at' => null]);
    }
}
