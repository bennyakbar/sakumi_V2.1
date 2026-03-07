<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Student;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'invoice_number' => 'INV-' . now()->year . '-' . str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'student_id' => Student::factory(),
            'period_type' => 'monthly',
            'period_identifier' => now()->format('Y-m'),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => $this->faker->numberBetween(100000, 500000),
            'paid_amount' => 0,
            'status' => 'unpaid',
            'created_by' => User::factory(),
        ];
    }
}
