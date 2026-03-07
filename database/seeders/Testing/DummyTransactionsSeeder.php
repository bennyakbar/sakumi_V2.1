<?php

namespace Database\Seeders\Testing;

use App\Models\Account;
use App\Models\Category;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;

class DummyTransactionsSeeder extends TestingSeeder
{
    public function run(): void
    {
        $this->ensureTestingEnvironment();

        $studentIds = Student::query()->pluck('id')->all();
        $userIds = User::query()->pluck('id')->all();
        $accountIds = Account::query()->pluck('id')->all();
        $categoryIds = Category::query()->pluck('id')->all();

        if ($studentIds === [] || $userIds === [] || $accountIds === [] || $categoryIds === []) {
            throw new \RuntimeException('Reference data for transactions is missing.');
        }

        $unitId = session('current_unit_id');

        Transaction::factory()
            ->count(1000)
            ->state(new Sequence(
                fn () => [
                    'unit_id' => $unitId,
                    'student_id' => $studentIds[array_rand($studentIds)],
                    'account_id' => $accountIds[array_rand($accountIds)],
                    'category_id' => $categoryIds[array_rand($categoryIds)],
                    'created_by' => $userIds[array_rand($userIds)],
                    'cancelled_by' => fake()->boolean(15) ? $userIds[array_rand($userIds)] : null,
                ]
            ))
            ->create();
    }
}
