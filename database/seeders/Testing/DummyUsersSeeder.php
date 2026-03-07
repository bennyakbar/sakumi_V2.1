<?php

namespace Database\Seeders\Testing;

use App\Models\User;

class DummyUsersSeeder extends TestingSeeder
{
    public function run(): void
    {
        $this->ensureTestingEnvironment();

        $unitId = session('current_unit_id');

        User::factory()->count(10)->state(['unit_id' => $unitId])->create();
    }
}
