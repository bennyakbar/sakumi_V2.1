<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    protected $model = SchoolClass::class;

    public function definition(): array
    {
        $level = fake()->numberBetween(1, 6);
        $startYear = fake()->numberBetween(2022, 2026);

        return [
            'unit_id' => Unit::factory(),
            'name' => fake()->randomElement(['A', 'B', 'C']).' '.$level,
            'level' => $level,
            'academic_year' => sprintf('%d/%d', $startYear, $startYear + 1),
            'is_active' => fake()->boolean(90),
        ];
    }
}
