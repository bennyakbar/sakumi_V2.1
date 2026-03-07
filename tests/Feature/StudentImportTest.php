<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentImportTest extends TestCase
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

    public function test_import_page_can_be_rendered()
    {
        $response = $this->get(route('master.students.import'));
        $response->assertStatus(200);
    }

    public function test_template_can_be_downloaded()
    {
        $response = $this->get(route('master.students.template'));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_valid_csv_imports_students()
    {
        $class = SchoolClass::create([
            'name' => '1A',
            'level' => 1,
            'academic_year' => '2025/2026'
        ]);
        $category = StudentCategory::create(['name' => 'Regular', 'code' => 'REG']);

        $header = 'name,nis,nisn,class_name,category_name,gender,birth_place,birth_date,parent_name,parent_phone,address,enrollment_date,status';
        $row1 = "John Doe,12345,0012345678,1A,Regular,L,Jakarta,2015-01-01,Mr. Doe,08123456789,Address,2025-01-01,active";

        $content = "$header\n$row1";

        $file = UploadedFile::fake()->createWithContent('students.csv', $content);

        $response = $this->post(route('master.students.processImport'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('master.students.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('students', ['name' => 'John Doe', 'nis' => '12345']);
    }

    public function test_invalid_rows_are_reported()
    {
        $header = 'name,nis,nisn,class_name,category_name,gender,birth_place,birth_date,parent_name,parent_phone,address,enrollment_date,status';
        $row1 = "Jane Doe,54321,8765432100,InvalidClass,InvalidCategory,P,,,,,,,,,active"; // Invalid class/category

        $content = "$header\n$row1";
        $file = UploadedFile::fake()->createWithContent('students.csv', $content);

        $response = $this->post(route('master.students.processImport'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('master.students.index'));
        $response->assertSessionHas('error_list');
    }

    public function test_students_can_be_exported()
    {
        $response = $this->get(route('master.students.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }
}
