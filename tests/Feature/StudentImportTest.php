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

    public function test_valid_csv_imports_students_with_all_fields()
    {
        SchoolClass::create(['name' => '1A', 'level' => 1, 'academic_year' => '2025/2026']);
        StudentCategory::create(['name' => 'Regular', 'code' => 'REG']);

        $header = 'name,class_name,category_name,gender,enrollment_date,status,nis,nisn,birth_place,birth_date,parent_name,parent_phone,address';
        $row = 'John Doe,1A,Regular,L,2025-01-01,Aktif,12345,0012345678,Jakarta,2015-01-01,Mr. Doe,08123456789,Address';

        $file = UploadedFile::fake()->createWithContent('students.csv', "$header\n$row");

        $response = $this->post(route('master.students.processImport'), ['file' => $file]);

        $response->assertRedirect(route('master.students.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('students', ['name' => 'John Doe', 'nis' => '12345']);
    }

    public function test_valid_csv_imports_students_with_mandatory_only()
    {
        SchoolClass::create(['name' => '1B', 'level' => 1, 'academic_year' => '2025/2026']);
        StudentCategory::create(['name' => 'Regular', 'code' => 'REG']);

        $header = 'name,class_name,category_name,gender,enrollment_date,status,nis,birth_place,birth_date,address,parent_name,parent_phone';
        $row = 'Siti Aminah,1B,Regular,P,2025-07-14,Aktif,,,,,,';

        $file = UploadedFile::fake()->createWithContent('students.csv', "$header\n$row");

        $response = $this->post(route('master.students.processImport'), ['file' => $file]);

        $response->assertRedirect(route('master.students.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('students', [
            'name' => 'Siti Aminah',
            'nis' => null,
            'birth_place' => null,
            'status' => 'active',
        ]);
    }

    public function test_invalid_rows_are_reported_per_row()
    {
        SchoolClass::create(['name' => '1A', 'level' => 1, 'academic_year' => '2025/2026']);
        StudentCategory::create(['name' => 'Regular', 'code' => 'REG']);

        $header = 'name,class_name,category_name,gender,enrollment_date,status';
        $validRow = 'Good Student,1A,Regular,L,2025-01-01,Aktif';
        $invalidRow = ',InvalidClass,InvalidCategory,,2025-01-01,Aktif'; // Missing name and gender

        $file = UploadedFile::fake()->createWithContent('students.csv', "$header\n$validRow\n$invalidRow");

        $response = $this->post(route('master.students.processImport'), ['file' => $file]);

        $response->assertRedirect(route('master.students.index'));
        $response->assertSessionHas('error_list');
        // Valid row should still be imported
        $this->assertDatabaseHas('students', ['name' => 'Good Student']);
    }

    public function test_students_can_be_exported()
    {
        $response = $this->get(route('master.students.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }
}
