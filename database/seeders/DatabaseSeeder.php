<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        /*
        |--------------------------------------------------------------------------
        | CORE SYSTEM
        |--------------------------------------------------------------------------
        */

                $this->call([

            // UNIT SYSTEM
            UnitSeeder::class,

            // ACADEMIC REFERENCES
            AcademicReferenceSeeder::class,

            // SYSTEM SETTINGS
            SettingsSeeder::class,
            UnitSchoolSettingsSeeder::class,

            // RBAC
            RolePermissionSeeder::class,

            // ACCOUNTING STRUCTURE
            ChartOfAccountsSeeder::class,
            AccountMappingsSeeder::class,

            // EXPENSE DEFAULTS
            CommonExpenseFeeTypeSeeder::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | LOCAL / DUMMY DATA ONLY
        |--------------------------------------------------------------------------
        */

        if (config('database.sakumi_mode') === 'dummy') {

            $this->call([
                // LOGIN USERS
                FixedLoginSeeder::class,
            ]);

        }
    }
}