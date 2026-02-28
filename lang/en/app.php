<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global Shared UI Strings
    |--------------------------------------------------------------------------
    */

    // Buttons & Actions
    'button' => [
        'save'           => 'Save',
        'cancel'         => 'Cancel',
        'filter'         => 'Filter',
        'reset'          => 'Reset',
        'back'           => 'Back',
        'edit'           => 'Edit',
        'delete'         => 'Delete',
        'search'         => 'Search',
        'detail'         => 'Detail',
        'print'          => 'Print',
        'close'          => 'Close',
        'confirm'        => 'Confirm',
        'confirm_cancel' => 'Confirm Cancel',
        'export'         => 'Export',
        'export_xlsx'    => 'Export XLSX',
        'export_csv'     => 'Export CSV',
        'import'         => 'Import',
        'create'         => 'Create',
        'view'           => 'View',
        'remove'         => 'Remove',
        'restore'        => 'Restore',
        'pay'            => 'Pay',
        'pay_now'        => 'Pay Now',
        'view_all'       => 'View All',
    ],

    // Status labels
    'status' => [
        'completed'      => 'Completed',
        'cancelled'      => 'Cancelled',
        'paid'           => 'Paid',
        'unpaid'         => 'Unpaid',
        'partial'        => 'Partially Paid',
        'active'         => 'Active',
        'inactive'       => 'Inactive',
        'monthly'        => 'Monthly',
        'one_time'       => 'One-time',
        'annual'         => 'Annual',
        'registered'     => 'Registered',
        'under_review'   => 'Under Review',
        'accepted'       => 'Accepted',
        'rejected'       => 'Rejected',
        'enrolled'       => 'Enrolled',
        'draft'          => 'Draft',
        'open'           => 'Open',
        'closed'         => 'Closed',
    ],

    // Common labels / column headers
    'label' => [
        'date'            => 'Date',
        'date_from'       => 'Date From',
        'date_to'         => 'Date To',
        'time'            => 'Time',
        'amount'          => 'Amount',
        'total'           => 'Total',
        'total_amount'    => 'Total Amount',
        'actions'         => 'Actions',
        'class'           => 'Class',
        'student'         => 'Student',
        'status'          => 'Status',
        'code'            => 'Code',
        'name'            => 'Name',
        'notes'           => 'Notes',
        'no'              => 'No',
        'all'             => 'All',
        'period'          => 'Period',
        'method'          => 'Method',
        'description'     => 'Description',
        'nis'             => 'NIS',
        'nisn'            => 'NISN',
        'nis_nisn'        => 'NIS / NISN',
        'category'        => 'Category',
        'discount'        => 'Discount',
        'type'            => 'Type',
        'level'           => 'Level',
        'academic_year'   => 'Academic Year',
        'gender'          => 'Gender',
        'enrollment_date' => 'Enrollment Date',
        'due_date'        => 'Due Date',
        'outstanding'     => 'Outstanding',
        'source'          => 'Source',
        'items'           => 'Items',
        'allocated'       => 'Allocated',
        'unallocated'     => 'Unallocated',
        'fee_type'        => 'Fee Type',
        'amount_rp'       => 'Amount (Rp)',
        'reference'       => 'Reference',
        'created_by'      => 'Created By',
        'month'           => 'Month',
        'year'            => 'Year',
    ],

    // Unit scope
    'unit' => [
        'current'   => 'Current Unit',
        'all'       => 'All Units',
        'unit'      => 'Unit',
        'breakdown' => 'Per-Unit Breakdown',
    ],

    // Payment methods
    'payment' => [
        'cash'     => 'Cash',
        'transfer' => 'Transfer',
        'qris'     => 'QRIS',
        'income'   => 'Income',
        'expense'  => 'Expense',
    ],

    // Filter options
    'filter' => [
        'all_status'  => 'All Status',
        'all_periods' => 'All Periods',
        'all_classes' => '-- All Classes --',
    ],

    // Navigation
    'nav' => [
        'dashboard'      => 'Dashboard',
        'transactions'   => 'Transactions',
        'invoices'       => 'Invoices',
        'settlements'    => 'Settlements',
        'students'       => 'Students',
        'classes'        => 'Classes',
        'categories'     => 'Categories',
        'fee_types'      => 'Fee Types',
        'fee_matrix'     => 'Fee Matrix',
        'daily_report'   => 'Daily Report',
        'monthly_report' => 'Monthly Report',
        'arrears_report' => 'Arrears Report',
        'profile'        => 'Profile',
        'log_out'        => 'Log Out',
        'master_data'    => 'Master Data',
        'reports'        => 'Reports',
        'users'          => 'Users',
        'language'       => 'Language',
        'admission'      => 'Admission',
        'admission_periods' => 'Admission Periods',
        'applicants'     => 'Applicants',
    ],

    // Empty state messages
    'empty' => [
        'transactions'  => 'No transactions found.',
        'invoices'      => 'No invoices found.',
        'settlements'   => 'No settlements found.',
        'students'      => 'No students found.',
        'classes'       => 'No classes found.',
        'fee_types'     => 'No fee types found.',
        'fee_matrices'  => 'No fee matrices found.',
        'categories'    => 'No categories found.',
        'users'         => 'No users found.',
        'entries_date'  => 'No entries found for this date.',
        'entries'       => 'No entries found.',
        'transactions_short' => 'No transactions.',
        'allocations'   => 'No allocations.',
        'arrears'       => 'No overdue invoices with outstanding balance.',
        'no_invoices_student' => 'No outstanding invoices found for this student.',
        'no_transactions_yet' => 'No transactions yet.',
        'admission_periods' => 'No admission periods found.',
        'applicants'     => 'No applicants found.',
    ],

    // Error/flash messages (from controllers/middleware)
    'error' => [
        'unit_inactive'    => 'Unit is not active.',
        'no_switch_perm'   => 'You do not have permission to switch units.',
        'no_unit_assigned' => 'Your account has not been assigned to any unit. Contact administrator.',
        'session_expired'  => 'Your session has expired due to inactivity.',
    ],

    // Search placeholders
    'placeholder' => [
        'search_transaction'  => 'Code / Student / Description',
        'search_invoice'      => 'Search invoice or student...',
        'cancellation_reason' => 'Enter reason for cancellation',
        'select_student'      => '-- Select Student --',
        'select_fee_type'     => '-- Select Fee Type --',
        'transfer_ref'        => 'Transfer reference, etc.',
        'all_categories'      => 'All Categories',
    ],

    // Form / create page strings
    'form' => [
        'payment_applied'       => 'Payment will be applied to the selected invoice.',
        'select_obligations'    => 'Select the obligations to include in this invoice:',
        'selected_total'        => 'Selected Total:',
        'min_max'               => 'Min: 1 | Max: Rp :max',
        'no_obligations'        => 'No uninvoiced unpaid obligations found for this student.',
        'batch_generate_desc'   => 'Batch generate invoices for all active students with unpaid obligations in the selected period.',
        'generation_errors'     => 'Generation Errors:',
        'confirm_generate'      => 'This will generate invoices for all matching students. Continue?',
        'process_income'        => 'Process Income',
        'process_expense'       => 'Process Expense',
    ],

];
