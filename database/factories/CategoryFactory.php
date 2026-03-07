<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['income', 'expense']);
        $name = $type === 'income'
            ? fake()->randomElement(['SPP Bulanan', 'Dana BOS', 'Donasi', 'Seragam', 'Kegiatan'])
            : fake()->randomElement(['Listrik', 'Air', 'ATK', 'Internet', 'Kebersihan', 'Kegiatan Siswa']);

        return [
            'unit_id' => Unit::factory(),
            'code' => strtoupper(substr($type, 0, 3)).'-'.fake()->unique()->numerify('###'),
            'name' => $name,
            'type' => $type,
            'description' => fake()->sentence(),
            'is_active' => fake()->boolean(95),
        ];
    }
}
