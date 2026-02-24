<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backend Flash / Error Messages
    |--------------------------------------------------------------------------
    */

    // Transaction
    'transaction_created'          => 'Transaction created successfully. Number: :number',
    'transaction_create_failed'    => 'Failed to create transaction: :error',
    'transaction_cancelled'        => 'Transaction cancelled successfully.',
    'transaction_no_edit'          => 'Transactions cannot be edited.',
    'transaction_already_cancelled' => 'Transaction is already cancelled.',
    'invalid_transaction_type'     => 'Invalid transaction type.',
    'expense_not_authorized'       => 'You are not authorized to create expense transactions.',
    'cancelled_by_admin'           => 'Cancelled by administrator',

    // Settlement
    'settlement_created'           => 'Settlement created: :number',
    'settlement_create_failed'     => 'Failed to create settlement: :error',
    'settlement_cancelled'         => 'Settlement cancelled successfully.',
    'settlement_already_cancelled' => 'Settlement is already cancelled.',
    'settlement_min_allocation'    => 'Settlement must have at least one allocation with amount > 0',
    'allocation_exceeds_settlement' => 'Total allocation (Rp :allocated) exceeds settlement amount (Rp :total).',
    'invoice_not_found'            => 'Invoice #:id not found, already paid, or belongs to a different student.',
    'allocation_exceeds_outstanding' => 'Allocation for invoice :number (Rp :allocated) exceeds outstanding (Rp :outstanding).',
    'payment_exceeds_outstanding'  => 'Payment amount exceeds outstanding invoice amount.',
    'invoice_no_balance'           => 'Selected invoice has no outstanding balance.',
    'settlement_voided'            => 'Settlement voided successfully.',
    'settlement_void_failed'       => 'Failed to void settlement: :error',
    'settlement_already_void'      => 'Settlement is already voided.',
    'settlement_not_active'        => 'Settlement cannot be voided (current status: :status).',

    // Invoice
    'invoice_created'              => 'Invoice created: :number',
    'invoice_create_failed'        => 'Failed to create invoice: :error',
    'invoice_cancelled'            => 'Invoice cancelled successfully.',
    'invoice_generation_complete'  => 'Invoice generation complete: :created created, :skipped skipped.',
    'invoice_generation_errors'    => 'Errors: :count',
    'invoice_generation_failed'    => 'Generation failed: :error',
    'unsupported_period_type'      => 'Unsupported period type: :type',
    'no_valid_obligations'         => 'No valid unpaid obligations found.',
    'obligations_already_invoiced' => 'Some obligations are already paid or already invoiced.',
    'cannot_cancel_paid_invoice'   => 'Cannot cancel a fully paid invoice.',
    'cannot_cancel_invoice_payments' => 'Cannot cancel an invoice with existing payments. Cancel the settlements first.',

    // Master: Fee Type
    'fee_type_created'             => 'Fee Type created successfully.',
    'fee_type_updated'             => 'Fee Type updated successfully.',
    'fee_type_deleted'             => 'Fee Type deleted successfully.',
    'fee_type_in_use'              => 'Cannot delete fee type because it is used in fee matrices.',

    // Master: Fee Matrix
    'fee_matrix_created'           => 'Fee Matrix created successfully.',
    'fee_matrix_updated'           => 'Fee Matrix updated successfully.',
    'fee_matrix_deleted'           => 'Fee Matrix deleted successfully.',
    'fee_matrix_exists'            => 'Fee Matrix for this combination already exists.',

    // Master: Student
    'student_created'              => 'Student created successfully.',
    'student_updated'              => 'Student updated successfully.',
    'student_deleted'              => 'Student deleted successfully.',
    'student_import_success'       => 'Student import finished successfully.',

    // Master: Class
    'class_created'                => 'Class created successfully.',
    'class_updated'                => 'Class updated successfully.',
    'class_deleted'                => 'Class deleted successfully.',
    'class_has_students'           => 'Cannot delete class with assigned students.',

    // Master: Category
    'category_created'             => 'Student Category created successfully.',
    'category_updated'             => 'Student Category updated successfully.',
    'category_deleted'             => 'Student Category deleted successfully.',
    'category_has_students'        => 'Cannot delete category because it has associated students.',

    // User Management
    'user_created'                 => 'User created successfully.',
    'user_updated'                 => 'User updated successfully.',
    'user_deleted'                 => 'User deactivated successfully.',
    'user_password_reset'          => 'Temporary password generated successfully.',
    'users_bulk_updated'           => ':count user(s) updated successfully.',
    'cannot_deactivate_self'       => 'You cannot deactivate your own account.',

    // Middleware / Auth
    'no_unit_assigned'             => 'Your account has not been assigned to any unit. Contact administrator.',
    'unit_inactive'                => 'Unit is not active.',
    'no_switch_permission'         => 'You do not have permission to switch units.',
    'session_expired'              => 'Your session has expired due to inactivity.',
    'unauthorized'                 => 'Unauthorized action.',
    'super_admin_only'             => 'Only Super Admin can manage roles.',
    'cannot_modify_own_role'       => 'You cannot modify your own role.',

    // Report
    'source_settlement'            => 'Settlement',
    'source_direct_transaction'    => 'Direct Transaction',
    'uncategorized'                => 'Uncategorized',
    'general'                      => 'General',
    'watermark_original'           => 'ORIGINAL',

    // Aging bucket labels
    'aging_0_30'                   => '0-30 days',
    'aging_31_60'                  => '31-60 days',
    'aging_61_90'                  => '61-90 days',
    'aging_90_plus'                => '>90 days',

];
