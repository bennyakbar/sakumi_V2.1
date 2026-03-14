<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FixedLoginSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        // Ensure all permissions/roles exist before assigning them to users.
        $this->call(RolePermissionSeeder::class);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $defaultUnitId = Unit::query()->where('code', 'MI')->value('id')
            ?? Unit::query()->orderBy('id')->value('id');

        $unitIds = [
            'MI' => Unit::query()->where('code', 'MI')->value('id') ?? $defaultUnitId,
            'RA' => Unit::query()->where('code', 'RA')->value('id') ?? $defaultUnitId,
            'DTA' => Unit::query()->where('code', 'DTA')->value('id') ?? $defaultUnitId,
        ];

        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => $guard]);
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
        $adminTuMiRole = Role::firstOrCreate(['name' => 'admin_tu_mi', 'guard_name' => $guard]);
        $adminTuRaRole = Role::firstOrCreate(['name' => 'admin_tu_ra', 'guard_name' => $guard]);
        $adminTuDtaRole = Role::firstOrCreate(['name' => 'admin_tu_dta', 'guard_name' => $guard]);

        // Admin TU role permissions are managed centrally in RolePermissionSeeder.

        // Staff: standard operational access (view/input) without destructive controls.
        $staffRole->syncPermissions([
            'dashboard.view',
            'master.students.view',
            'master.students.create',
            'master.students.edit',
            'master.classes.view',
            'master.categories.view',
            'master.fee-types.view',
            'master.fee-matrix.view',
            'transactions.view',
            'transactions.create',
            'receipts.view',
            'reports.daily',
            'reports.monthly',
            'reports.arrears',
            'reports.ar-outstanding',
            'reports.collection',
            'reports.student-statement',
            'reports.cash-book',
        ]);

        User::updateOrCreate(
            ['email' => 'superadmin@sakumi.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('SuperAdmin#2026'),
                'is_active' => true,
                'unit_id' => $defaultUnitId,
            ]
        )->syncRoles([$superAdminRole->name]);

        User::updateOrCreate(
            ['email' => 'admin.tu.mi@sakumi.local'],
            [
                'name' => 'Admin TU MI',
                'password' => Hash::make('AdminTU-MI#2026'),
                'is_active' => true,
                'unit_id' => $unitIds['MI'],
            ]
        )->syncRoles([$adminTuMiRole->name]);

        User::updateOrCreate(
            ['email' => 'admin.tu.ra@sakumi.local'],
            [
                'name' => 'Admin TU RA',
                'password' => Hash::make('AdminTU-RA#2026'),
                'is_active' => true,
                'unit_id' => $unitIds['RA'],
            ]
        )->syncRoles([$adminTuRaRole->name]);

        User::updateOrCreate(
            ['email' => 'admin.tu.dta@sakumi.local'],
            [
                'name' => 'Admin TU DTA',
                'password' => Hash::make('AdminTU-DTA#2026'),
                'is_active' => true,
                'unit_id' => $unitIds['DTA'],
            ]
        )->syncRoles([$adminTuDtaRole->name]);

        // Retire deprecated legacy login without breaking historical foreign-key references.
        User::query()
            ->where('email', 'admin.tu@sakumi.local')
            ->update([
                'email' => 'archived.admin.tu@sakumi.local',
                'name' => 'Archived Admin TU (Legacy)',
                'password' => Hash::make('disabled-legacy-account'),
                'is_active' => false,
            ]);

        $staffUser = User::updateOrCreate(
            ['email' => 'staff@sakumi.local'],
            [
                'name' => 'Staff',
                'password' => Hash::make('Staff#2026'),
                'is_active' => true,
                'unit_id' => $defaultUnitId,
            ]
        );
        $staffUser->syncRoles([$staffRole->name, 'operator_tu']);

        $bendaharaUser = User::updateOrCreate(
            ['email' => 'bendahara@sakumi.local'],
            [
                'name' => 'Bendahara',
                'password' => Hash::make('Bendahara#2026'),
                'is_active' => true,
                // Primary unit is MI; cross-unit access (MI/RA/DTA) is granted by role policy.
                'unit_id' => $unitIds['MI'],
            ]
        );
        $bendaharaUser->syncRoles(['bendahara']);

        $kepalaSekolahUser = User::updateOrCreate(
            ['email' => 'kepala.sekolah@sakumi.local'],
            [
                'name' => 'Kepala Sekolah',
                'password' => Hash::make('KepalaSekolah#2026'),
                'is_active' => true,
                'unit_id' => $defaultUnitId,
            ]
        );
        $kepalaSekolahUser->syncRoles(['kepala_sekolah']);
    }
}
