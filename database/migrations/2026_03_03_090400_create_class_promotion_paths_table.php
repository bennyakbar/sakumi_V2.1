<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_promotion_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('from_class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('to_class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('from_academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('to_academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['unit_id', 'from_class_id', 'is_active'], 'class_promo_paths_lookup_idx');
            $table->unique(
                ['from_class_id', 'to_class_id', 'from_academic_year_id', 'to_academic_year_id'],
                'class_promo_paths_window_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_promotion_paths');
    }
};
