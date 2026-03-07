<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_obligations', function (Blueprint $table) {
            $table->foreignId('academic_year_id')
                ->nullable()
                ->after('unit_id')
                ->constrained('academic_years')
                ->restrictOnDelete();

            $table->foreignId('student_enrollment_id')
                ->nullable()
                ->after('student_id')
                ->constrained('student_enrollments')
                ->restrictOnDelete();

            $table->foreignId('class_id_snapshot')
                ->nullable()
                ->after('student_enrollment_id')
                ->constrained('classes')
                ->restrictOnDelete();

            $table->index(['academic_year_id', 'class_id_snapshot'], 'student_obligations_ay_class_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('academic_year_id')
                ->nullable()
                ->after('unit_id')
                ->constrained('academic_years')
                ->restrictOnDelete();

            $table->foreignId('student_enrollment_id')
                ->nullable()
                ->after('student_id')
                ->constrained('student_enrollments')
                ->restrictOnDelete();

            $table->index(['academic_year_id', 'student_enrollment_id'], 'invoices_ay_enrollment_idx');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_ay_enrollment_idx');
            $table->dropConstrainedForeignId('student_enrollment_id');
            $table->dropConstrainedForeignId('academic_year_id');
        });

        Schema::table('student_obligations', function (Blueprint $table) {
            $table->dropIndex('student_obligations_ay_class_idx');
            $table->dropConstrainedForeignId('class_id_snapshot');
            $table->dropConstrainedForeignId('student_enrollment_id');
            $table->dropConstrainedForeignId('academic_year_id');
        });
    }
};
