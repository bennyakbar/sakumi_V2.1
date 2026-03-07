<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. invoice_items: prevent same obligation linked twice to one invoice
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->unique(['invoice_id', 'student_obligation_id'], 'uq_invoice_items_invoice_obligation');
        });

        // 2. settlement_allocations: prevent same settlement allocated to same invoice twice
        Schema::table('settlement_allocations', function (Blueprint $table) {
            $table->unique(['settlement_id', 'invoice_id'], 'uq_settlement_allocations_settlement_invoice');
        });

        // 3. fee_matrix: prevent duplicate fee entries for same combination + effective date
        Schema::table('fee_matrix', function (Blueprint $table) {
            $table->unique(
                ['unit_id', 'class_id', 'category_id', 'fee_type_id', 'effective_from'],
                'uq_fee_matrix_unit_class_cat_fee_effective'
            );
        });

        // 4. invoices: replace global unique with unit-scoped unique
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->unique(['unit_id', 'invoice_number'], 'uq_invoices_unit_number');
        });

        // 5. settlements: replace global unique with unit-scoped unique
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropUnique(['settlement_number']);
            $table->unique(['unit_id', 'settlement_number'], 'uq_settlements_unit_number');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropUnique('uq_invoice_items_invoice_obligation');
        });

        Schema::table('settlement_allocations', function (Blueprint $table) {
            $table->dropUnique('uq_settlement_allocations_settlement_invoice');
        });

        Schema::table('fee_matrix', function (Blueprint $table) {
            $table->dropUnique('uq_fee_matrix_unit_class_cat_fee_effective');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('uq_invoices_unit_number');
            $table->unique('invoice_number');
        });

        Schema::table('settlements', function (Blueprint $table) {
            $table->dropUnique('uq_settlements_unit_number');
            $table->unique('settlement_number');
        });
    }
};
