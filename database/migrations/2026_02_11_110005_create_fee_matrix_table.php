<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_matrix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_type_id')->constrained('fee_types');
            $table->foreignId('class_id')->nullable()->constrained('classes');
            $table->foreignId('category_id')->nullable()->constrained('student_categories');
            $table->decimal('amount', 15, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['fee_type_id', 'class_id', 'category_id', 'effective_from'], 'idx_feematrix_lookup');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE fee_matrix ADD CONSTRAINT chk_effective_dates CHECK (effective_to IS NULL OR effective_to >= effective_from)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_matrix');
    }
};
