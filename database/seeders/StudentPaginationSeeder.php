<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use Illuminate\Database\Seeder;

class StudentPaginationSeeder extends Seeder
{
    public function run(): void
    {
        $rows = (int) env('SEED_DATAX_ROWS', 100);

        $class = SchoolClass::query()->firstOrCreate(
            ['name' => 'PAG-1', 'academic_year' => '2025/2026'],
            ['level' => 1, 'is_active' => true]
        );

        $category = StudentCategory::query()->firstOrCreate(
            ['code' => 'PAG'],
            ['name' => 'Pagination Demo', 'discount_percentage' => 0]
        );

        $start = (int) Student::query()->count() + 1;
        $end = $start + $rows - 1;

        for ($index = $start; $index <= $end; $index++) {
            Student::query()->create([
                'nis' => 'PAG'.str_pad((string) $index, 6, '0', STR_PAD_LEFT),
                'nisn' => null,
                'name' => 'Student '.$index,
                'class_id' => $class->id,
                'category_id' => $category->id,
                'gender' => $index % 2 === 0 ? 'L' : 'P',
                'birth_date' => now()->subYears(10)->toDateString(),
                'birth_place' => 'City',
                'parent_name' => 'Parent '.$index,
                'parent_phone' => '08123'.str_pad((string) $index, 7, '0', STR_PAD_LEFT),
                'parent_whatsapp' => '62812'.str_pad((string) $index, 8, '0', STR_PAD_LEFT),
                'address' => 'Address '.$index,
                'status' => 'active',
                'enrollment_date' => now()->subYear()->toDateString(),
            ]);
        }
    }
}
