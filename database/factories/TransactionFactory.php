<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['completed', 'completed', 'completed', 'cancelled']);
        $cancelledAt = $status === 'cancelled' ? fake()->dateTimeBetween('-6 months', 'now') : null;

        return [
            'unit_id' => Unit::factory(),
            'transaction_number' => 'TRX-'.fake()->unique()->numerify('##########'),
            'transaction_date' => fake()->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
            'type' => fake()->randomElement(['income', 'expense']),
            'student_id' => Student::query()->inRandomOrder()->value('id') ?? Student::factory(),
            'account_id' => Account::query()->inRandomOrder()->value('id') ?? Account::factory(),
            'category_id' => Category::query()->inRandomOrder()->value('id') ?? Category::factory(),
            'payment_method' => fake()->randomElement(['cash', 'transfer', 'qris']),
            'total_amount' => fake()->randomFloat(2, 10000, 3500000),
            'description' => fake()->sentence(6),
            'receipt_path' => null,
            'proof_path' => null,
            'status' => $status,
            'cancelled_at' => $cancelledAt,
            'cancelled_by' => $status === 'cancelled' ? (User::query()->inRandomOrder()->value('id') ?? User::factory()) : null,
            'cancellation_reason' => $status === 'cancelled' ? fake()->sentence(4) : null,
            'created_by' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
        ];
    }
}
