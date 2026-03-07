<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_batch_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_batch_id')->constrained('promotion_batches')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('from_enrollment_id')->constrained('student_enrollments')->restrictOnDelete();
            $table->string('action', 20);
            $table->foreignId('to_class_id')->nullable()->constrained('classes')->restrictOnDelete();
            $table->string('reason', 255)->nullable();
            $table->boolean('is_applied')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->unique(['promotion_batch_id', 'student_id'], 'promotion_batch_students_batch_student_unique');
            $table->index(['promotion_batch_id', 'action'], 'promotion_batch_students_batch_action_idx');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE promotion_batch_students ADD CONSTRAINT promotion_batch_students_action_check CHECK (action IN ('promote', 'retain', 'graduate'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_batch_students');
    }
};
