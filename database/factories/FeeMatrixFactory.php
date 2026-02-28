<?php

namespace Database\Factories;

use App\Models\FeeMatrix;
use App\Models\FeeType;
use App\Models\SchoolClass;
use App\Models\StudentCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeeMatrix>
 */
class FeeMatrixFactory extends Factory
{
    protected $model = FeeMatrix::class;

    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'fee_type_id' => FeeType::factory(),
            'class_id' => SchoolClass::factory(),
            'category_id' => StudentCategory::factory(),
            'amount' => $this->faker->numberBetween(50000, 500000),
            'effective_from' => now()->subMonth()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
            'notes' => $this->faker->sentence(),
        ];
    }
}
