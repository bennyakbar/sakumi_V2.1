<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance indexes identified during integrity audit.
 *
 * These indexes optimize the most common financial query patterns:
 * - Arrears/outstanding queries (invoices by student + status + due date)
 * - Balance recalculation (settlement allocations lookup)
 * - Student financial history (transactions by student + status + date)
 * - Receipt lookup after settlement (receipts by settlement)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settlement_allocations', function (Blueprint $table) {
            $table->index(
                ['invoice_id', 'settlement_id', 'amount'],
                'idx_sa_invoice_settlement_amount'
            );
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(
                ['student_id', 'status', 'transaction_date'],
                'idx_txn_student_status_date'
            );
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->index(
                ['settlement_id', 'issued_at'],
                'idx_receipts_settlement_issued'
            );
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(
                ['student_id', 'status', 'due_date'],
                'idx_invoices_student_status_due'
            );
        });
    }

    public function down(): void
    {
        Schema::table('settlement_allocations', function (Blueprint $table) {
            $table->dropIndex('idx_sa_invoice_settlement_amount');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_txn_student_status_date');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropIndex('idx_receipts_settlement_issued');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_student_status_due');
        });
    }
};
