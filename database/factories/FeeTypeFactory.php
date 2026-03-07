<?php

namespace Database\Factories;

use App\Models\FeeType;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeeType>
 */
class FeeTypeFactory extends Factory
{
    protected $model = FeeType::class;

    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'code' => strtoupper($this->faker->unique()->lexify('FT-???')),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'is_monthly' => true,
            'is_active' => true,
        ];
    }
}
