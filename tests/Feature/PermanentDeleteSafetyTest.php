<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermanentDeleteSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_superadmin_can_permanently_delete_class_when_feature_enabled_and_no_dependencies(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin)->withSession(['current_unit_id' => $admin->unit_id]);
        Setting::set('dangerous_permanent_delete_enabled', true);

        $class = SchoolClass::query()->create([
            'unit_id' => $admin->unit_id,
            'name' => 'X-PD',
            'level' => 1,
            'academic_year' => '2025/2026',
            'is_active' => true,
        ]);

        $this->delete(route('master.classes.destroy', $class), [
            'permanent_delete' => 1,
            'confirm_text' => 'HAPUS PERMANEN',
        ])->assertRedirect(route('master.classes.index'));

        $this->assertDatabaseMissing('classes', ['id' => $class->id]);
    }

    public function test_permanent_delete_class_is_blocked_when_dependencies_exist(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin)->withSession(['current_unit_id' => $admin->unit_id]);
        Setting::set('dangerous_permanent_delete_enabled', true);

        $class = SchoolClass::query()->create([
            'unit_id' => $admin->unit_id,
            'name' => 'X-DEP',
            'level' => 1,
            'academic_year' => '2025/2026',
            'is_active' => true,
        ]);
        $category = StudentCategory::query()->create([
            'unit_id' => $admin->unit_id,
            'code' => 'REG',
            'name' => 'Regular',
            'discount_percentage' => 0,
        ]);
        Student::query()->create([
            'unit_id' => $admin->unit_id,
            'nis' => '111',
            'nisn' => '222',
            'name' => 'Dependent Student',
            'class_id' => $class->id,
            'category_id' => $category->id,
            'status' => 'active',
            'enrollment_date' => '2025-07-01',
        ]);

        $this->from(route('master.classes.index'))
            ->delete(route('master.classes.destroy', $class), [
                'permanent_delete' => 1,
                'confirm_text' => 'HAPUS PERMANEN',
            ])
            ->assertRedirect(route('master.classes.index'))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('classes', ['id' => $class->id]);
    }

    public function test_permanent_delete_user_is_blocked_when_feature_disabled(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $target = User::factory()->create(['unit_id' => $admin->unit_id]);
        $target->assignRole('cashier');
        $this->actingAs($admin)->withSession(['current_unit_id' => $admin->unit_id]);
        Setting::set('dangerous_permanent_delete_enabled', false);

        $this->from(route('users.index'))
            ->delete(route('users.destroy', $target), [
                'permanent_delete' => 1,
                'confirm_text' => 'HAPUS PERMANEN',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }
}
