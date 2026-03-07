<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $types = ['asset', 'liability', 'equity', 'income', 'expense'];
        $type = fake()->randomElement($types);
        $opening = fake()->randomFloat(2, 0, 50000000);

        return [
            'unit_id' => Unit::factory(),
            'code' => 'ACC-'.fake()->unique()->numerify('#####'),
            'name' => fake()->company().' '.ucfirst($type),
            'type' => $type,
            'opening_balance' => $opening,
            'current_balance' => $opening + fake()->randomFloat(2, -5000000, 15000000),
            'is_active' => fake()->boolean(95),
        ];
    }
}
