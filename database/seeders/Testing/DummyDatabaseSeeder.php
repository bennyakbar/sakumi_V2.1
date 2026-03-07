<?php

namespace Database\Seeders\Testing;

use Database\Seeders\FixedLoginSeeder;
use Database\Seeders\CommonExpenseFeeTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\UnitSeeder;
use Database\Seeders\UnitSchoolSettingsSeeder;

class DummyDatabaseSeeder extends TestingSeeder
{
    public function run(): void
    {
        $this->ensureTestingEnvironment();

        $this->call([
            UnitSeeder::class,
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            UnitSchoolSettingsSeeder::class,
            CommonExpenseFeeTypeSeeder::class,
            FixedLoginSeeder::class,
            DummyReferenceSeeder::class,
            DummyUsersSeeder::class,
            DummyStudentsSeeder::class,
            DummyObligationsSeeder::class,
            DummyTransactionsSeeder::class,
        ]);
    }
}
