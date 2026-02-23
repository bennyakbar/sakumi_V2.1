<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UnitSeeder::class,
            ChartOfAccountsSeeder::class,
            AccountMappingsSeeder::class,
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            UnitSchoolSettingsSeeder::class,
            CommonExpenseFeeTypeSeeder::class,
            FixedLoginSeeder::class,
        ]);
    }
}
