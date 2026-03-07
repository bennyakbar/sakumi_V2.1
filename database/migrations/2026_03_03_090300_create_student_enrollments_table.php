<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(true);
            $table->string('entry_status', 20)->default('new');
            $table->string('exit_status', 20)->nullable();
            $table->foreignId('promotion_batch_id')->nullable()->constrained('promotion_batches')->nullOnDelete();
            $table->foreignId('previous_enrollment_id')->nullable()->constrained('student_enrollments')->nullOnDelete();
            $table->timestamps();

            $table->index(['unit_id', 'student_id', 'is_current'], 'student_enrollments_unit_student_current_idx');
            $table->index(['unit_id', 'academic_year_id', 'class_id'], 'student_enrollments_unit_ay_class_idx');
            $table->unique(['student_id', 'academic_year_id'], 'student_enrollments_student_ay_unique');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE student_enrollments ADD CONSTRAINT student_enrollments_entry_status_check CHECK (entry_status IN ('new', 'promoted', 'retained', 'transferred_in'))");
            DB::statement("ALTER TABLE student_enrollments ADD CONSTRAINT student_enrollments_exit_status_check CHECK (exit_status IS NULL OR exit_status IN ('promoted', 'retained', 'graduated', 'transferred_out', 'dropout'))");
        }

        $students = DB::table('students')
            ->select('id', 'unit_id', 'class_id', 'enrollment_date', 'created_at')
            ->get();

        foreach ($students as $student) {
            $class = DB::table('classes')
                ->select('academic_year_id')
                ->where('id', $student->class_id)
                ->first();

            if (! $class || ! $class->academic_year_id) {
                continue;
            }

            $academicYear = DB::table('academic_years')
                ->select('start_date')
                ->where('id', $class->academic_year_id)
                ->first();

            DB::table('student_enrollments')->insert([
                'unit_id' => $student->unit_id,
                'student_id' => $student->id,
                'academic_year_id' => $class->academic_year_id,
                'class_id' => $student->class_id,
                'start_date' => $student->enrollment_date
                    ?? $academicYear?->start_date
                    ?? now()->toDateString(),
                'end_date' => null,
                'is_current' => true,
                'entry_status' => 'new',
                'exit_status' => null,
                'promotion_batch_id' => null,
                'previous_enrollment_id' => null,
                'created_at' => $student->created_at ?? now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
