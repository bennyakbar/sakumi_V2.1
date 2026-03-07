<?php

namespace Database\Factories;

use App\Models\Settlement;
use App\Models\Student;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Settlement>
 */
class SettlementFactory extends Factory
{
    protected $model = Settlement::class;

    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'settlement_number' => 'STL-' . now()->year . '-' . str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'student_id' => Student::factory(),
            'payment_date' => now()->toDateString(),
            'payment_method' => $this->faker->randomElement(['cash', 'transfer', 'qris']),
            'total_amount' => $this->faker->numberBetween(100000, 500000),
            'allocated_amount' => 0,
            'status' => 'completed',
            'created_by' => User::factory(),
        ];
    }
}
