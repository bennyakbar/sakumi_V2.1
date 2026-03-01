<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('from_academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('to_academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->date('effective_date');
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['unit_id', 'status'], 'promotion_batches_unit_status_idx');
            $table->unique(['unit_id', 'from_academic_year_id', 'to_academic_year_id'], 'promotion_batches_unit_window_unique');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE promotion_batches ADD CONSTRAINT promotion_batches_status_check CHECK (status IN ('draft', 'approved', 'applied', 'cancelled'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_batches');
    }
};
