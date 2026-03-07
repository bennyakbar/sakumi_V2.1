<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('admission_period_id')->constrained('admission_periods')->restrictOnDelete();
            $table->string('registration_number', 30)->unique();
            $table->string('name', 255);
            $table->foreignId('target_class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('student_categories')->restrictOnDelete();
            $table->char('gender', 1);
            $table->date('birth_date')->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->string('parent_name', 255)->nullable();
            $table->string('parent_phone', 20)->nullable();
            $table->string('parent_whatsapp', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('previous_school', 255)->nullable();
            $table->string('status', 20)->default('registered');
            $table->text('rejection_reason')->nullable();
            $table->date('status_changed_at')->nullable();
            $table->foreignId('status_changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('unit_id');
            $table->index('admission_period_id');
            $table->index('status');
            $table->index('target_class_id');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE applicants ADD CONSTRAINT chk_applicants_gender CHECK (gender IN ('L', 'P'))");
            DB::statement("ALTER TABLE applicants ADD CONSTRAINT chk_applicants_status CHECK (status IN ('registered', 'under_review', 'accepted', 'rejected', 'enrolled'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
