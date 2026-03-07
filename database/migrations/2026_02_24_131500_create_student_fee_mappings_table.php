<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fee_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('fee_matrix_id')->constrained('fee_matrix')->restrictOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'effective_from', 'effective_to', 'is_active'], 'idx_sfm_student_effective');
            $table->index(['fee_matrix_id', 'effective_from', 'effective_to'], 'idx_sfm_matrix_effective');
            $table->unique(['student_id', 'fee_matrix_id', 'effective_from'], 'uq_sfm_student_matrix_from');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE student_fee_mappings ADD CONSTRAINT chk_sfm_effective_dates CHECK (effective_to IS NULL OR effective_to >= effective_from)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_mappings');
    }
};
