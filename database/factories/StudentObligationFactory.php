<?php

namespace Database\Factories;

use App\Models\FeeType;
use App\Models\Student;
use App\Models\StudentObligation;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentObligation>
 */
class StudentObligationFactory extends Factory
{
    protected $model = StudentObligation::class;

    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'student_id' => Student::factory(),
            'fee_type_id' => FeeType::factory(),
            'month' => fake()->numberBetween(1, 12),
            'year' => fake()->numberBetween(2025, 2026),
            'amount' => fake()->randomFloat(2, 50000, 500000),
            'is_paid' => false,
            'paid_amount' => 0,
            'paid_at' => null,
            'transaction_item_id' => null,
        ];
    }
}
