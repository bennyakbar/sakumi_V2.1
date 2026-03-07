<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'nis' => fake()->unique()->numerify('25########'),
            'nisn' => fake()->unique()->numerify('##########'),
            'name' => fake()->name(),
            'class_id' => SchoolClass::query()->inRandomOrder()->value('id') ?? SchoolClass::factory(),
            'category_id' => StudentCategory::query()->inRandomOrder()->value('id') ?? StudentCategory::factory(),
            'gender' => fake()->randomElement(['L', 'P']),
            'birth_date' => fake()->dateTimeBetween('-13 years', '-6 years')->format('Y-m-d'),
            'birth_place' => fake()->city(),
            'parent_name' => fake()->name(),
            'parent_phone' => fake()->numerify('08##########'),
            'parent_whatsapp' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'status' => fake()->randomElement(['active', 'active', 'active', 'graduated', 'transferred']),
            'enrollment_date' => fake()->dateTimeBetween('-6 years', 'now')->format('Y-m-d'),
        ];
    }
}
