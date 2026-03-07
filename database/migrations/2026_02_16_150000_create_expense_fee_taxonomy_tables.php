<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_fee_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name', 120);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['unit_id', 'code'], 'expense_fee_categories_unit_code_unique');
            $table->index(['unit_id', 'sort_order'], 'expense_fee_categories_unit_sort_idx');
        });

        Schema::create('expense_fee_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('expense_fee_category_id')->constrained('expense_fee_categories')->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name', 120);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['unit_id', 'code'], 'expense_fee_subcategories_unit_code_unique');
            $table->index(['unit_id', 'expense_fee_category_id'], 'expense_fee_subcategories_unit_cat_idx');
        });

        Schema::table('fee_types', function (Blueprint $table) {
            $table->foreignId('expense_fee_subcategory_id')
                ->nullable()
                ->after('unit_id')
                ->constrained('expense_fee_subcategories')
                ->nullOnDelete();
            $table->index(['unit_id', 'expense_fee_subcategory_id'], 'fee_types_unit_exp_subcat_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fee_types', function (Blueprint $table) {
            $table->dropIndex('fee_types_unit_exp_subcat_idx');
            $table->dropForeign(['expense_fee_subcategory_id']);
            $table->dropColumn('expense_fee_subcategory_id');
        });

        Schema::dropIfExists('expense_fee_subcategories');
        Schema::dropIfExists('expense_fee_categories');
    }
};
