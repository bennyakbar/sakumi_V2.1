<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->smallInteger('year');
            $table->tinyInteger('month');
            $table->foreignId('expense_fee_subcategory_id')->constrained('expense_fee_subcategories')->restrictOnDelete();
            $table->decimal('budget_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['unit_id', 'year', 'month', 'expense_fee_subcategory_id'], 'uq_expense_budget_period');
            $table->index(['unit_id', 'year', 'month'], 'idx_expense_budget_period');
        });

        Schema::create('expense_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('expense_fee_subcategory_id')->constrained('expense_fee_subcategories')->restrictOnDelete();
            $table->foreignId('fee_type_id')->constrained('fee_types')->restrictOnDelete();
            $table->date('entry_date');
            $table->string('payment_method', 20)->default('cash');
            $table->string('vendor_name', 150)->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('posted_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['unit_id', 'entry_date', 'status'], 'idx_expense_entries_period_status');
            $table->index(['unit_id', 'expense_fee_subcategory_id', 'entry_date'], 'idx_expense_entries_subcat_period');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE expense_entries ADD CONSTRAINT chk_expense_entries_status CHECK (status IN ('draft', 'approved', 'posted', 'cancelled'))");
            DB::statement("ALTER TABLE expense_entries ADD CONSTRAINT chk_expense_entries_payment_method CHECK (payment_method IN ('cash', 'transfer', 'qris'))");
            DB::statement('ALTER TABLE expense_budgets ADD CONSTRAINT chk_expense_budget_month CHECK (month BETWEEN 1 AND 12)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_entries');
        Schema::dropIfExists('expense_budgets');
    }
};
