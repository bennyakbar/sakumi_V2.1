<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite tidak support ALTER COLUMN, jadi kita pakai pragma
        if (DB::connection()->getDriverName() === 'sqlite') {
            // Backup existing data
            $students = DB::table('students')->get();
            
            // Drop existing table
            Schema::dropIfExists('students');
            
            // Recreate table dengan NIS nullable
            Schema::create('students', function ($table) {
                $table->id();
                $table->string('nis')->nullable();  // ← INI YANG DIUBAH!
                $table->string('nisn')->nullable();
                $table->string('name');
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('category_id');
                $table->string('gender')->nullable();
                $table->date('birth_date')->nullable();
                $table->string('birth_place')->nullable();
                $table->string('parent_name')->nullable();
                $table->string('parent_phone')->nullable();
                $table->string('parent_whatsapp')->nullable();
                $table->text('address')->nullable();
                $table->string('status')->default('active');
                $table->date('enrollment_date');
                $table->timestamps();
                $table->softDeletes();
                $table->unsignedBigInteger('unit_id');
                
                $table->foreign('class_id')->references('id')->on('classes');
                $table->foreign('category_id')->references('id')->on('student_categories');
                $table->foreign('unit_id')->references('id')->on('units');
                
                $table->index('class_id');
                $table->index('status');
                $table->index('unit_id');
            });
            
            // Restore data
            foreach ($students as $student) {
                DB::table('students')->insert((array) $student);
            }
            
            // Recreate indexes
            Schema::table('students', function ($table) {
                $table->unique('nis');
                $table->unique('nisn');
            });
        }
    }

    public function down(): void
    {
        // Rollback - kembalikan NIS ke NOT NULL
        if (DB::connection()->getDriverName() === 'sqlite') {
            $students = DB::table('students')->get();
            
            Schema::dropIfExists('students');
            
            Schema::create('students', function ($table) {
                $table->id();
                $table->string('nis')->nullable(false);  // NOT NULL lagi
                $table->string('nisn')->nullable();
                $table->string('name');
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('category_id');
                $table->string('gender')->nullable();
                $table->date('birth_date')->nullable();
                $table->string('birth_place')->nullable();
                $table->string('parent_name')->nullable();
                $table->string('parent_phone')->nullable();
                $table->string('parent_whatsapp')->nullable();
                $table->text('address')->nullable();
                $table->string('status')->default('active');
                $table->date('enrollment_date');
                $table->timestamps();
                $table->softDeletes();
                $table->unsignedBigInteger('unit_id');
                
                $table->foreign('class_id')->references('id')->on('classes');
                $table->foreign('category_id')->references('id')->on('student_categories');
                $table->foreign('unit_id')->references('id')->on('units');
            });
            
            foreach ($students as $student) {
                DB::table('students')->insert((array) $student);
            }
            
            Schema::table('students', function ($table) {
                $table->unique('nis');
                $table->unique('nisn');
            });
        }
    }
};