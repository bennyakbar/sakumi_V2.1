<?php

namespace Database\Factories;

use App\Models\FeeMatrix;
use App\Models\Student;
use App\Models\StudentFeeMapping;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentFeeMapping>
 */
class StudentFeeMappingFactory extends Factory
{
    protected $model = StudentFeeMapping::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'fee_matrix_id' => FeeMatrix::factory(),
            'effective_from' => now()->subMonth()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
            'notes' => $this->faker->sentence(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
