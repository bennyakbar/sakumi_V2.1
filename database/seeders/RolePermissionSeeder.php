<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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
            'expenses.view', 'expenses.create', 'expenses.approve', 'expenses.budget.manage', 'expenses.report.view',
            // Bank Reconciliation
            'bank-reconciliation.view', 'bank-reconciliation.manage', 'bank-reconciliation.close',
            // Receipts
            'receipts.view', 'receipts.print', 'receipts.reprint',
            // Invoices
            'invoices.view', 'invoices.create', 'invoices.generate', 'invoices.print', 'invoices.cancel',
            // Settlements
            'settlements.view', 'settlements.create', 'settlements.cancel', 'settlements.void',
            // Reports
            'reports.daily', 'reports.monthly', 'reports.arrears',
            'reports.ar-outstanding', 'reports.collection', 'reports.student-statement', 'reports.cash-book',
            'reports.export',
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
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Super Admin — full access
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions(Permission::all());

        // Bendahara — financial operations + reporting
        $bendahara = Role::firstOrCreate(['name' => 'bendahara']);
        $bendahara->syncPermissions([
            'dashboard.view',
            'master.students.view',
            'master.fee-types.view',
            'master.fee-matrix.view', 'master.fee-matrix.create', 'master.fee-matrix.edit', 'master.fee-matrix.delete',
            'master.student-fee-mappings.view', 'master.student-fee-mappings.create', 'master.student-fee-mappings.edit', 'master.student-fee-mappings.delete',
            'master.classes.view',
            'master.categories.view',
            'transactions.view', 'transactions.create', 'transactions.expense.create', 'transactions.cancel',
            'expenses.view', 'expenses.create', 'expenses.approve', 'expenses.budget.manage', 'expenses.report.view',
            'bank-reconciliation.view', 'bank-reconciliation.manage', 'bank-reconciliation.close',
            'receipts.view', 'receipts.print', 'receipts.reprint',
            'invoices.view', 'invoices.create', 'invoices.generate', 'invoices.print', 'invoices.cancel',
            'settlements.view', 'settlements.create', 'settlements.cancel', 'settlements.void',
            'reports.daily', 'reports.monthly', 'reports.arrears',
            'reports.ar-outstanding', 'reports.collection', 'reports.student-statement', 'reports.cash-book',
            'reports.export',
            'users.view',
            'settings.view',
            'audit.view',
        ]);

        // Kepala Sekolah — view-only + reporting
        $kepalaSekolah = Role::firstOrCreate(['name' => 'kepala_sekolah']);
        $kepalaSekolah->syncPermissions([
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
        $operatorTu = Role::firstOrCreate(['name' => 'operator_tu']);
        $operatorTu->syncPermissions([
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
            'dashboard.view',
            'master.students.view', 'master.students.create', 'master.students.edit', 'master.students.delete',
            'master.students.import', 'master.students.export',
            'master.classes.view', 'master.classes.create', 'master.classes.edit', 'master.classes.delete',
            'master.categories.view', 'master.categories.create', 'master.categories.edit', 'master.categories.delete',
            'master.fee-types.view', 'master.fee-types.create', 'master.fee-types.edit', 'master.fee-types.delete',
            'master.fee-matrix.view', 'master.fee-matrix.create', 'master.fee-matrix.edit', 'master.fee-matrix.delete',
            'master.student-fee-mappings.view', 'master.student-fee-mappings.create', 'master.student-fee-mappings.edit', 'master.student-fee-mappings.delete',
            'transactions.view', 'transactions.create', 'transactions.expense.create', 'transactions.cancel',
            'expenses.view', 'expenses.create', 'expenses.approve', 'expenses.budget.manage', 'expenses.report.view',
            'bank-reconciliation.view', 'bank-reconciliation.manage', 'bank-reconciliation.close',
            'receipts.view', 'receipts.print', 'receipts.reprint',
            'invoices.view', 'invoices.create', 'invoices.generate', 'invoices.print', 'invoices.cancel',
            'settlements.view', 'settlements.create', 'settlements.cancel',
            'reports.daily', 'reports.monthly', 'reports.arrears',
            'reports.ar-outstanding', 'reports.collection', 'reports.student-statement', 'reports.cash-book',
            'reports.export',
            'users.view',
            'settings.view',
            'audit.view',
            'notifications.view',
        ];
        Role::firstOrCreate(['name' => 'admin_tu'])->syncPermissions($adminTuUnitPermissions); // legacy compatibility
        Role::firstOrCreate(['name' => 'admin_tu_mi'])->syncPermissions($adminTuUnitPermissions);
        Role::firstOrCreate(['name' => 'admin_tu_ra'])->syncPermissions($adminTuUnitPermissions);
        Role::firstOrCreate(['name' => 'admin_tu_dta'])->syncPermissions($adminTuUnitPermissions);

        // Auditor — view-only all data, audit log
        $auditor = Role::firstOrCreate(['name' => 'auditor']);
        $auditor->syncPermissions([
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
        $cashier = Role::firstOrCreate(['name' => 'cashier']);
        $cashier->syncPermissions([
            'dashboard.view',
            'transactions.view', 'transactions.create',
            'receipts.view', 'receipts.print',
        ]);
    }
}
