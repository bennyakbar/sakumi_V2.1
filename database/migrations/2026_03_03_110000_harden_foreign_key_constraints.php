<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Harden foreign key constraints across all financial tables.
 *
 * Changes:
 * - Replace NO ACTION with explicit RESTRICT on 8 FKs
 * - Replace CASCADE with RESTRICT on 3 financial child tables
 * - Replace SET NULL with RESTRICT on receipt FKs (audit trail protection)
 * - Apply explicit constraint naming with 'fk_' prefix
 *
 * Production-safe: only constraint metadata changes, no data modification.
 * Prerequisite: run orphan detection queries BEFORE this migration to
 * ensure no existing orphan records would violate the new constraints.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── transactions ────────────────────────────────────
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['cancelled_by']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('student_id', 'fk_transactions_student_id')
                ->references('id')->on('students')
                ->restrictOnDelete();

            $table->foreign('created_by', 'fk_transactions_created_by')
                ->references('id')->on('users')
                ->restrictOnDelete();

            $table->foreign('cancelled_by', 'fk_transactions_cancelled_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });

        // ─── transaction_items ───────────────────────────────
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->dropForeign(['fee_type_id']);
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->foreign('transaction_id', 'fk_transaction_items_transaction_id')
                ->references('id')->on('transactions')
                ->restrictOnDelete();

            $table->foreign('fee_type_id', 'fk_transaction_items_fee_type_id')
                ->references('id')->on('fee_types')
                ->restrictOnDelete();
        });

        // ─── invoice_items ───────────────────────────────────
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreign('invoice_id', 'fk_invoice_items_invoice_id')
                ->references('id')->on('invoices')
                ->restrictOnDelete();
        });

        // ─── settlement_allocations ──────────────────────────
        Schema::table('settlement_allocations', function (Blueprint $table) {
            $table->dropForeign(['settlement_id']);
        });

        Schema::table('settlement_allocations', function (Blueprint $table) {
            $table->foreign('settlement_id', 'fk_settlement_alloc_settlement_id')
                ->references('id')->on('settlements')
                ->restrictOnDelete();
        });

        // ─── students ────────────────────────────────────────
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropForeign(['category_id']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreign('class_id', 'fk_students_class_id')
                ->references('id')->on('classes')
                ->restrictOnDelete();

            $table->foreign('category_id', 'fk_students_category_id')
                ->references('id')->on('student_categories')
                ->restrictOnDelete();
        });

        // ─── student_obligations ─────────────────────────────
        Schema::table('student_obligations', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['fee_type_id']);
            $table->dropForeign(['transaction_item_id']);
        });

        Schema::table('student_obligations', function (Blueprint $table) {
            $table->foreign('student_id', 'fk_obligations_student_id')
                ->references('id')->on('students')
                ->restrictOnDelete();

            $table->foreign('fee_type_id', 'fk_obligations_fee_type_id')
                ->references('id')->on('fee_types')
                ->restrictOnDelete();

            $table->foreign('transaction_item_id', 'fk_obligations_transaction_item_id')
                ->references('id')->on('transaction_items')
                ->nullOnDelete();
        });

        // ─── receipts ────────────────────────────────────────
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['settlement_id']);
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->foreign('transaction_id', 'fk_receipts_transaction_id')
                ->references('id')->on('transactions')
                ->restrictOnDelete();

            $table->foreign('invoice_id', 'fk_receipts_invoice_id')
                ->references('id')->on('invoices')
                ->restrictOnDelete();

            $table->foreign('settlement_id', 'fk_receipts_settlement_id')
                ->references('id')->on('settlements')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // ─── receipts ────────────────────────────────────────
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropForeign('fk_receipts_transaction_id');
            $table->dropForeign('fk_receipts_invoice_id');
            $table->dropForeign('fk_receipts_settlement_id');
        });
        Schema::table('receipts', function (Blueprint $table) {
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            $table->foreign('settlement_id')->references('id')->on('settlements')->nullOnDelete();
        });

        // ─── student_obligations ─────────────────────────────
        Schema::table('student_obligations', function (Blueprint $table) {
            $table->dropForeign('fk_obligations_student_id');
            $table->dropForeign('fk_obligations_fee_type_id');
            $table->dropForeign('fk_obligations_transaction_item_id');
        });
        Schema::table('student_obligations', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('fee_type_id')->references('id')->on('fee_types');
            $table->foreign('transaction_item_id')->references('id')->on('transaction_items');
        });

        // ─── students ────────────────────────────────────────
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign('fk_students_class_id');
            $table->dropForeign('fk_students_category_id');
        });
        Schema::table('students', function (Blueprint $table) {
            $table->foreign('class_id')->references('id')->on('classes');
            $table->foreign('category_id')->references('id')->on('student_categories');
        });

        // ─── settlement_allocations ──────────────────────────
        Schema::table('settlement_allocations', function (Blueprint $table) {
            $table->dropForeign('fk_settlement_alloc_settlement_id');
        });
        Schema::table('settlement_allocations', function (Blueprint $table) {
            $table->foreign('settlement_id')->references('id')->on('settlements')->cascadeOnDelete();
        });

        // ─── invoice_items ───────────────────────────────────
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign('fk_invoice_items_invoice_id');
        });
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });

        // ─── transaction_items ───────────────────────────────
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropForeign('fk_transaction_items_transaction_id');
            $table->dropForeign('fk_transaction_items_fee_type_id');
        });
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete();
            $table->foreign('fee_type_id')->references('id')->on('fee_types');
        });

        // ─── transactions ────────────────────────────────────
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign('fk_transactions_student_id');
            $table->dropForeign('fk_transactions_created_by');
            $table->dropForeign('fk_transactions_cancelled_by');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('cancelled_by')->references('id')->on('users');
        });
    }
};
