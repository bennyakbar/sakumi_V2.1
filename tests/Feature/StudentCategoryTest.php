<?php

namespace Tests\Feature;

use App\Models\StudentCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCategoryTest extends TestCase
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

    public function test_index_page_can_be_rendered()
    {
        $response = $this->get(route('master.categories.index'));
        $response->assertStatus(200);
    }

    public function test_create_category()
    {
        $response = $this->post(route('master.categories.store'), [
            'code' => 'TEST',
            'name' => 'Test Category',
            'description' => 'Description',
            'discount_percentage' => 10,
        ]);

        $response->assertRedirect(route('master.categories.index'));
        $this->assertDatabaseHas('student_categories', [
            'code' => 'TEST',
            'name' => 'Test Category',
        ]);
    }

    public function test_update_category()
    {
        $category = StudentCategory::create([
            'code' => 'OLD',
            'name' => 'Old Name',
            'discount_percentage' => 0,
        ]);

        $response = $this->put(route('master.categories.update', $category), [
            'code' => 'NEW',
            'name' => 'New Name',
            'description' => 'New Description',
            'discount_percentage' => 20,
        ]);

        $response->assertRedirect(route('master.categories.index'));
        $this->assertDatabaseHas('student_categories', [
            'id' => $category->id,
            'code' => 'NEW',
            'name' => 'New Name',
        ]);
    }

    public function test_delete_category()
    {
        $category = StudentCategory::create([
            'code' => 'DEL',
            'name' => 'To Delete',
            'discount_percentage' => 0,
        ]);

        $response = $this->delete(route('master.categories.destroy', $category));

        $response->assertRedirect(route('master.categories.index'));
        $this->assertSoftDeleted('student_categories', ['id' => $category->id]);
    }

    public function test_unique_code_validation()
    {
        StudentCategory::create([
            'code' => 'UNIQUE',
            'name' => 'First',
            'discount_percentage' => 0,
        ]);

        $response = $this->post(route('master.categories.store'), [
            'code' => 'UNIQUE', // Duplicate code
            'name' => 'Second',
            'discount_percentage' => 0,
        ]);

        $response->assertSessionHasErrors('code');
    }
}
