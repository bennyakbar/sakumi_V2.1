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
    'transaction_redirect_to_settlement' => 'This student has an active invoice (:invoice). Please use Settlement so invoice status is updated correctly.',
    'student_has_unpaid_obligations_use_invoice' => 'This student still has unpaid obligations. Create/use an invoice and process payment through Settlement.',
    'student_required_for_monthly_fee' => 'Transactions containing monthly fee items must select a student to avoid bypassing invoice-settlement flow.',
    'monthly_fee_must_use_invoice' => 'Monthly fee items must be processed through Invoice/Settlement flow, not walk-in transactions.',
    'cancelled_by_admin'           => 'Cancelled by administrator',

    // Settlement
    'settlement_created'           => 'Settlement created: :number',
    'settlement_create_failed'     => 'Failed to create settlement: :error',
    'settlement_cancelled'         => 'Settlement cancelled successfully.',
    'settlement_approved'          => 'Settlement approved successfully.',
    'settlement_not_pending'       => 'Only pending settlements can be approved.',
    'settlement_maker_checker_violation' => 'You cannot approve a settlement that you created. A different user must approve it.',
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
    'invoice_approved'             => 'Invoice approved successfully.',
    'invoice_not_pending'          => 'Only pending invoices can be approved.',
    'invoice_maker_checker_violation' => 'You cannot approve an invoice that you created. A different user must approve it.',
    'invoice_generation_complete'  => 'Invoice generation complete: :created created, :skipped skipped.',
    'invoice_generation_errors'    => 'Errors: :count',
    'invoice_generation_failed'    => 'Generation failed: :error',
    'unsupported_period_type'      => 'Unsupported period type: :type',
    'no_valid_obligations'         => 'No valid unpaid obligations found.',
    'obligations_already_invoiced' => 'Some obligations are already paid or already invoiced.',
    'cannot_cancel_paid_invoice'   => 'Cannot cancel a fully paid invoice.',
    'cannot_cancel_invoice_payments' => 'Cannot cancel an invoice with existing payments. Cancel the settlements first.',
    'invoice_void_requires_single_allocation_settlement' => 'Cannot cancel: settlement :number covers multiple invoices. Please void that settlement first (Settlements > :number > Void), then retry cancelling this invoice.',
    'cancel_paid_invoice_not_authorized' => 'You do not have permission to cancel paid invoices. Contact bendahara or super admin.',
    'cancel_paid_invoice_requires_void_permission' => 'Cancelling a paid invoice requires settlement void permission. Contact bendahara or super admin.',
    'hard_delete_not_allowed'      => 'Direct deletion is not allowed. Use the cancel/void workflow instead.',
    'settings_updated'            => 'Settings updated successfully.',
    'academic_year_must_be_consecutive' => 'Academic year must be consecutive, for example: 2025/2026.',
    'permanent_delete_not_allowed' => 'Permanent delete is allowed only for superadmin and must be enabled in Settings.',
    'permanent_delete_confirmation_invalid' => 'Permanent delete confirmation is invalid. Type exactly: HAPUS PERMANEN.',
    'permanent_delete_blocked_dependencies' => 'Permanent delete is blocked because this data is already used: :details.',
    'permanent_delete_failed_fk' => 'Permanent delete failed because related records are still locked by database constraints.',
    'user_permanently_deleted' => 'User permanently deleted.',
    'student_permanently_deleted' => 'Student permanently deleted.',
    'class_permanently_deleted' => 'Class permanently deleted.',
    'category_permanently_deleted' => 'Category permanently deleted.',
    'fee_type_permanently_deleted' => 'Fee type permanently deleted.',
    'fee_matrix_permanently_deleted' => 'Fee matrix permanently deleted.',

    // Expense
    'expense_draft_created'        => 'Expense draft created successfully.',
    'expense_approved'             => 'Expense approved and posted successfully.',
    'expense_cancelled'            => 'Expense draft cancelled.',
    'expense_voided'               => 'Expense entry reversed and transaction voided.',
    'expense_attachment_uploaded'   => 'Attachment uploaded successfully.',
    'expense_attachment_deleted'    => 'Attachment deleted.',
    'expense_cancel_reason_required' => 'Reason is required to void a posted expense.',
    'expense_maker_checker_violation' => 'You cannot approve an expense that you created. A different user must approve it.',
    'expense_budget_exceeded'      => 'Warning: This expense exceeds the remaining budget. Remaining: Rp :remaining, over by: Rp :over.',
    'expense_locked'               => 'This expense is locked and cannot be modified. Use reversal to correct posted entries.',
    'expense_budget_override_not_authorized' => 'You do not have permission to override budget limits. Contact a supervisor.',
    'expense_budget_override_reason_required' => 'A reason is required when overriding the budget limit.',

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
    'student_fee_mapping_created'  => 'Student fee mapping created successfully.',
    'student_fee_mapping_updated'  => 'Student fee mapping updated successfully.',
    'student_fee_mapping_deactivated' => 'Student fee mapping deactivated successfully.',
    'student_fee_mapping_overlap'  => 'The selected period overlaps with another active mapping for the same fee type.',

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

    // Admission (PSB)
    'admission_period_created'     => 'Admission period created successfully.',
    'admission_period_updated'     => 'Admission period updated successfully.',
    'admission_period_deleted'     => 'Admission period deleted successfully.',
    'admission_period_has_applicants' => 'Cannot delete period that still has applicants.',
    'applicant_created'            => 'Applicant registered successfully.',
    'applicant_updated'            => 'Applicant updated successfully.',
    'applicant_deleted'            => 'Applicant deleted successfully.',
    'applicant_reviewed'           => 'Applicant moved to review.',
    'applicant_accepted'           => 'Applicant accepted.',
    'applicant_rejected'           => 'Applicant rejected.',
    'applicant_enrolled'           => 'Applicant enrolled as student successfully.',
    'applicant_cannot_edit_enrolled' => 'Cannot edit an enrolled applicant.',
    'applicant_cannot_delete_enrolled' => 'Cannot delete an enrolled applicant.',
    'applicants_bulk_updated'      => ':count applicant(s) updated successfully.',
    'admission_invalid_transition' => 'Invalid status transition.',
    'admission_quota_exceeded'     => 'Class quota has been reached.',
    'admission_invalid_status'     => 'Invalid status for bulk operation.',

    // Aging bucket labels
    'aging_0_30'                   => '0-30 days',
    'aging_31_60'                  => '31-60 days',
    'aging_61_90'                  => '61-90 days',
    'aging_90_plus'                => '>90 days',

];
