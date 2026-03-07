<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->string('name', 100);
            $table->string('academic_year', 20);
            $table->date('registration_open');
            $table->date('registration_close');
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('unit_id');
            $table->index('status');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE admission_periods ADD CONSTRAINT chk_admission_periods_status CHECK (status IN ('draft', 'open', 'closed'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_periods');
    }
};
