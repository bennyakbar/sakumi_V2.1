namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([

            // UNIT SYSTEM
            UnitSeeder::class,

            // RBAC
            RolePermissionSeeder::class,

            // DEFAULT USERS
            FixedLoginSeeder::class,

            // SYSTEM SETTINGS
            SettingsSeeder::class,
            UnitSchoolSettingsSeeder::class,

            // ACCOUNTING STRUCTURE
            ChartOfAccountsSeeder::class,
            AccountMappingsSeeder::class,

            // EXPENSE DEFAULTS
            CommonExpenseFeeTypeSeeder::class,
        ]);
    }
}
