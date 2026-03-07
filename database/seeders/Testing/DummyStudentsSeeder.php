<?php

namespace Database\Seeders\Testing;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use Illuminate\Database\Eloquent\Factories\Sequence;

class DummyStudentsSeeder extends TestingSeeder
{
    public function run(): void
    {
        $this->ensureTestingEnvironment();

        $classIds = SchoolClass::query()->pluck('id')->all();
        $categoryIds = StudentCategory::query()->pluck('id')->all();

        if ($classIds === [] || $categoryIds === []) {
            throw new \RuntimeException('Reference data for students is missing.');
        }

        $unitId = session('current_unit_id');

        Student::factory()
            ->count(200)
            ->state(new Sequence(
                fn () => [
                    'unit_id' => $unitId,
                    'class_id' => $classIds[array_rand($classIds)],
                    'category_id' => $categoryIds[array_rand($categoryIds)],
                ]
            ))
            ->create();
    }
}
