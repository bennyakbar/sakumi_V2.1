<?php

return [

    // Document titles
    'title' => [
        'payment_receipt'    => 'PAYMENT RECEIPT',
        'expense_receipt'    => 'EXPENSE RECEIPT',
        'invoice'            => 'PAYMENT INVOICE',
        'settlement_receipt' => 'SETTLEMENT RECEIPT',
    ],

    // Meta labels
    'label' => [
        'receipt_no'       => 'Receipt No.',
        'voucher_no'       => 'Voucher No.',
        'transaction_no'   => 'Transaction No.',
        'pay_date'         => 'Payment Date',
        'date'             => 'Date',
        'method'           => 'Method',
        'payment_method'   => 'Payment Method',
        'student_name'     => 'Student Name',
        'class'            => 'Class',
        'officer'          => 'Officer',
        'transaction_type' => 'Transaction Type',
        'notes'            => 'Notes',
        'phone'            => 'Phone',
        'issued_at'        => 'Issued At',
        'printed_at'       => 'Printed at',
        'print_status'     => 'Print Status',
        'school_treasurer'   => 'School Treasurer',
        'settlement_no'      => 'Settlement No.',
        'reference_number'   => 'Reference No.',
    ],

    // Table headers
    'table' => [
        'no'               => 'No',
        'description'      => 'Transaction Description',
        'expense_desc'     => 'Expense Description',
        'invoice_item'     => 'Invoice Item',
        'detail'           => 'Detail',
        'nominal'          => 'Amount',
        'period'           => 'Period',
        'invoice_no'       => 'Invoice #',
        'invoice_total'    => 'Invoice Total',
        'allocated'        => 'Allocated Amount',
    ],

    // Totals
    'total' => [
        'payment'          => 'Total Payment',
        'expense'          => 'Total Expense',
        'invoice'          => 'Total Invoice',
        'paid'             => 'Already Paid',
        'outstanding'      => 'Outstanding',
        'settlement'       => 'Total Settlement',
    ],

    // Footer
    'footer' => [
        'official_receipt' => 'This document is an official school payment receipt.',
        'official_expense' => 'This document is an official school expense receipt.',
        'items_condensed'  => ':count additional items condensed to fit 1 page',
        'digitally_signed' => 'Digitally signed',
        'verification'       => 'Verification Code',
        'official_settlement' => 'This document is an official school settlement receipt.',
    ],

    // Verification page
    'verify' => [
        'title'            => 'Receipt Verification',
        'valid'            => 'VALID DOCUMENT',
        'invalid'          => 'INVALID DOCUMENT / CODE MISMATCH',
        'doc_no'           => 'Document No.',
        'type'             => 'Type',
        'date'             => 'Date',
        'total'            => 'Total',
        'status'           => 'Status',
        'code_sent'        => 'Code Sent',
        'code_valid'       => 'Valid Code',
    ],

    // Invoice-specific labels
    'label_invoice_no'     => 'Invoice No.',
    'label_due_date'       => 'Due Date',
    'label_period'         => 'Period',
    'label_status'         => 'Status',
    'label_nis'            => 'NIS',
    'label_digital_sig'    => 'Digital Signature',
    'label_admin_tu'       => 'Admin TU',
    'logo_fallback'        => 'Logo',

    // Misc
    'empty'                => 'No transaction items.',
    'no_invoice_items'     => 'No invoice items.',
    'expense_type'         => 'EXPENSE',
    'income_type'          => 'INCOME',
    'address_not_set'      => 'School address not configured',

];
