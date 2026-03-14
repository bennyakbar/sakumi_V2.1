<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        $registrar = app()[\Spatie\Permission\PermissionRegistrar::class];
        $registrar->forgetCachedPermissions();

        // ── Clean rebuild: wipe stale/duplicate permissions and re-create ──
        // This is safe: only permission definitions and role↔permission pivots
        // are reset. User↔role assignments (model_has_roles) are NOT touched.
        \DB::statement('TRUNCATE TABLE model_has_permissions, role_has_permissions, permissions CASCADE');

        // Normalise guard_name on all existing roles.
        \DB::table('roles')->whereNot('guard_name', $guard)->update(['guard_name' => $guard]);

        // Merge legacy 'superadmin' role into 'super_admin'.
        $legacySuperadmin = \DB::table('roles')->where('name', 'superadmin')->first();
        $canonicalSuperAdmin = \DB::table('roles')->where('name', 'super_admin')->first();

        if ($legacySuperadmin && $canonicalSuperAdmin) {
            \DB::table('model_has_roles')
                ->where('role_id', $legacySuperadmin->id)
                ->update(['role_id' => $canonicalSuperAdmin->id]);
            \DB::table('roles')->where('id', $legacySuperadmin->id)->delete();
        }

        // Force Spatie to reload from a clean state.
        $registrar->forgetCachedPermissions();

        $permissions = [
            // Master Data
            'master.classes.view', 'master.classes.create', 'master.classes.edit', 'master.classes.delete',
            'master.categories.view', 'master.categories.create', 'master.categories.edit', 'master.categories.delete',
            'master.fee-types.view', 'master.fee-types.create', 'master.fee-types.edit', 'master.fee-types.delete',
            'master.fee-matrix.view', 'master.fee-matrix.create', 'master.fee-matrix.edit', 'master.fee-matrix.delete',
            'master.student-fee-mappings.view', 'master.student-fee-mappings.create', 'master.student-fee-mappings.edit', 'master.student-fee-mappings.delete',
            'master.students.view', 'master.students.create', 'master.students.edit', 'master.students.delete',
            'master.students.import', 'master.students.export',
            // Transactions
            'transactions.view', 'transactions.create', 'transactions.expense.create', 'transactions.cancel',
            // Expense v2
            'expenses.view', 'expenses.create', 'expenses.approve', 'expenses.budget.manage', 'expenses.budget-override', 'expenses.report.view',
            // Bank Reconciliation
            'bank-reconciliation.view', 'bank-reconciliation.manage', 'bank-reconciliation.close',
            // Receipts
            'receipts.view', 'receipts.print', 'receipts.reprint',
            // Invoices
            'invoices.view', 'invoices.create', 'invoices.generate', 'invoices.print', 'invoices.cancel', 'invoices.cancel_paid', 'invoices.approve',
            // Settlements
            'settlements.view', 'settlements.create', 'settlements.cancel', 'settlements.void', 'settlements.approve',
            // Reports
            'reports.daily', 'reports.monthly', 'reports.arrears',
            'reports.ar-outstanding', 'reports.collection', 'reports.student-statement', 'reports.cash-book',
            'reports.export',
            // Admission (PSB)
            'admission.periods.view', 'admission.periods.create', 'admission.periods.edit', 'admission.periods.delete',
            'admission.applicants.view', 'admission.applicants.create', 'admission.applicants.edit', 'admission.applicants.delete',
            'admission.applicants.review', 'admission.applicants.accept', 'admission.applicants.reject', 'admission.applicants.enroll',
            // Dashboard
            'dashboard.view',
            // Users & Roles
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.manage-roles',
            // Settings
            'settings.view', 'settings.edit',
            // Backup
            'backup.view', 'backup.create',
            // Audit Log
            'audit.view',
            // Notifications
            'notifications.view', 'notifications.retry',
            // Health
            'health.view',
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        // Super Admin — full access
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
        $superAdmin->syncPermissions(Permission::query()->where('guard_name', $guard)->get());

        // Bendahara — financial operations + reporting
        $bendahara = Role::firstOrCreate(['name' => 'bendahara', 'guard_name' => $guard]);
        $bendahara->syncPermissions([
            'admission.periods.view', 'admission.applicants.view',
            'dashboard.view',
            'master.students.view',
            'master.fee-types.view',
            'master.fee-matrix.view', 'master.fee-matrix.create', 'master.fee-matrix.edit', 'master.fee-matrix.delete',
            'master.student-fee-mappings.view', 'master.student-fee-mappings.create', 'master.student-fee-mappings.edit', 'master.student-fee-mappings.delete',
            'master.classes.view',
            'master.categories.view',
            'transactions.view', 'transactions.create', 'transactions.expense.create', 'transactions.cancel',
            'expenses.view', 'expenses.create', 'expenses.approve', 'expenses.budget.manage', 'expenses.budget-override', 'expenses.report.view',
            'bank-reconciliation.view', 'bank-reconciliation.manage', 'bank-reconciliation.close',
            'receipts.view', 'receipts.print', 'receipts.reprint',
            'invoices.view', 'invoices.create', 'invoices.generate', 'invoices.print', 'invoices.cancel', 'invoices.cancel_paid', 'invoices.approve',
            'settlements.view', 'settlements.create', 'settlements.cancel', 'settlements.void', 'settlements.approve',
            'reports.daily', 'reports.monthly', 'reports.arrears',
            'reports.ar-outstanding', 'reports.collection', 'reports.student-statement', 'reports.cash-book',
            'reports.export',
            'users.view',
            'settings.view',
            'audit.view',
        ]);

        // Kepala Sekolah — view-only + reporting
        $kepalaSekolah = Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => $guard]);
        $kepalaSekolah->syncPermissions([
            'admission.periods.view', 'admission.applicants.view',
            'dashboard.view',
            'master.students.view',
            'master.classes.view',
            'master.categories.view',
            'master.fee-types.view',
            'master.fee-matrix.view',
            'transactions.view',
            'expenses.view', 'expenses.report.view',
            'bank-reconciliation.view',
            'receipts.view',
            'invoices.view', 'invoices.print',
            'settlements.view',
            'reports.daily', 'reports.monthly', 'reports.arrears',
            'reports.ar-outstanding', 'reports.collection', 'reports.student-statement', 'reports.cash-book',
            'reports.export',
            'users.view',
            'settings.view',
            'audit.view',
        ]);

        // Operator TU — operations (master data + create financial docs, no cancel)
        $operatorTu = Role::firstOrCreate(['name' => 'operator_tu', 'guard_name' => $guard]);
        $operatorTu->syncPermissions([
            'admission.periods.view', 'admission.periods.create', 'admission.periods.edit', 'admission.periods.delete',
            'admission.applicants.view', 'admission.applicants.create', 'admission.applicants.edit', 'admission.applicants.delete',
            'admission.applicants.review', 'admission.applicants.accept', 'admission.applicants.reject', 'admission.applicants.enroll',
            'dashboard.view',
            'master.students.view', 'master.students.create', 'master.students.edit', 'master.students.delete',
            'master.students.import', 'master.students.export',
            'master.classes.view', 'master.classes.create', 'master.classes.edit', 'master.classes.delete',
            'master.categories.view', 'master.categories.create', 'master.categories.edit', 'master.categories.delete',
            'master.fee-types.view',
            'master.fee-matrix.view',
            'master.student-fee-mappings.view',
            'transactions.view', 'transactions.create',
            'expenses.view',
            'receipts.view', 'receipts.print',
            'invoices.view', 'invoices.create', 'invoices.generate', 'invoices.print',
            'settlements.view', 'settlements.create',
            'reports.daily', 'reports.monthly', 'reports.arrears',
            'reports.ar-outstanding', 'reports.collection', 'reports.student-statement', 'reports.cash-book',
            'users.view',
            'settings.view',
        ]);

        // Admin TU per unit (MI/RA/DTA) — granular operational access in own unit.
        $adminTuUnitPermissions = [
            'admission.periods.view', 'admission.periods.create', 'admission.periods.edit', 'admission.periods.delete',
            'admission.applicants.view', 'admission.applicants.create', 'admission.applicants.edit', 'admission.applicants.delete',
            'admission.applicants.review', 'admission.applicants.accept', 'admission.applicants.reject', 'admission.applicants.enroll',
            'dashboard.view',
            'master.students.view', 'master.students.create', 'master.students.edit', 'master.students.delete',
            'master.students.import', 'master.students.export',
            'master.classes.view', 'master.classes.create', 'master.classes.edit', 'master.classes.delete',
            'master.categories.view', 'master.categories.create', 'master.categories.edit', 'master.categories.delete',
            'master.fee-types.view', 'master.fee-types.create', 'master.fee-types.edit', 'master.fee-types.delete',
            'master.fee-matrix.view', 'master.fee-matrix.create', 'master.fee-matrix.edit', 'master.fee-matrix.delete',
            'master.student-fee-mappings.view', 'master.student-fee-mappings.create', 'master.student-fee-mappings.edit', 'master.student-fee-mappings.delete',
            'transactions.view', 'transactions.create', 'transactions.expense.create', 'transactions.cancel',
            'expenses.view', 'expenses.create', 'expenses.approve', 'expenses.budget.manage', 'expenses.budget-override', 'expenses.report.view',
            'bank-reconciliation.view', 'bank-reconciliation.manage', 'bank-reconciliation.close',
            'receipts.view', 'receipts.print', 'receipts.reprint',
            'invoices.view', 'invoices.create', 'invoices.generate', 'invoices.print', 'invoices.cancel', 'invoices.approve',
            'settlements.view', 'settlements.create', 'settlements.cancel', 'settlements.approve',
            'reports.daily', 'reports.monthly', 'reports.arrears',
            'reports.ar-outstanding', 'reports.collection', 'reports.student-statement', 'reports.cash-book',
            'reports.export',
            'users.view',
            'settings.view',
            'audit.view',
            'notifications.view',
        ];
        Role::firstOrCreate(['name' => 'admin_tu', 'guard_name' => $guard])->syncPermissions($adminTuUnitPermissions); // legacy compatibility
        Role::firstOrCreate(['name' => 'admin_tu_mi', 'guard_name' => $guard])->syncPermissions($adminTuUnitPermissions);
        Role::firstOrCreate(['name' => 'admin_tu_ra', 'guard_name' => $guard])->syncPermissions($adminTuUnitPermissions);
        Role::firstOrCreate(['name' => 'admin_tu_dta', 'guard_name' => $guard])->syncPermissions($adminTuUnitPermissions);

        // Auditor — view-only all data, audit log
        $auditor = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => $guard]);
        $auditor->syncPermissions([
            'admission.periods.view', 'admission.applicants.view',
            'dashboard.view',
            'master.students.view',
            'master.classes.view',
            'master.categories.view',
            'master.fee-types.view',
            'master.fee-matrix.view',
            'master.student-fee-mappings.view',
            'transactions.view',
            'expenses.view', 'expenses.report.view',
            'bank-reconciliation.view',
            'receipts.view',
            'invoices.view', 'invoices.print',
            'settlements.view',
            'reports.daily', 'reports.monthly', 'reports.arrears',
            'reports.ar-outstanding', 'reports.collection', 'reports.student-statement', 'reports.cash-book',
            'reports.export',
            'audit.view',
        ]);

        // Cashier — can print only first-time (reprint is guarded in service)
        $cashier = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => $guard]);
        $cashier->syncPermissions([
            'dashboard.view',
            'transactions.view', 'transactions.create',
            'receipts.view', 'receipts.print',
        ]);
    }
}
