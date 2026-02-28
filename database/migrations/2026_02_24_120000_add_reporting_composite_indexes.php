<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->index(
                ['unit_id', 'status', 'type', 'transaction_date'],
                'transactions_unit_status_type_date_idx'
            );
        });

        Schema::table('settlements', function (Blueprint $table): void {
            $table->index(
                ['unit_id', 'status', 'payment_date'],
                'settlements_unit_status_payment_date_idx'
            );
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(
                ['unit_id', 'due_date', 'status'],
                'invoices_unit_due_date_status_idx'
            );
        });

        Schema::table('settlement_allocations', function (Blueprint $table): void {
            $table->index(
                ['invoice_id', 'settlement_id'],
                'settlement_allocations_invoice_settlement_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('settlement_allocations', function (Blueprint $table): void {
            $table->dropIndex('settlement_allocations_invoice_settlement_idx');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('invoices_unit_due_date_status_idx');
        });

        Schema::table('settlements', function (Blueprint $table): void {
            $table->dropIndex('settlements_unit_status_payment_date_idx');
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex('transactions_unit_status_type_date_idx');
        });
    }
};
