<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->string('code', 9); // e.g. 2026/2027
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('draft');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['unit_id', 'code'], 'academic_years_unit_code_unique');
            $table->index(['unit_id', 'is_active'], 'academic_years_unit_active_idx');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE academic_years ADD CONSTRAINT academic_years_status_check CHECK (status IN ('draft', 'active', 'closed'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
