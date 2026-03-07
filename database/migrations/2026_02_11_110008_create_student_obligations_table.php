<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_obligations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('fee_type_id')->constrained('fee_types');
            $table->smallInteger('month'); // 1-12
            $table->smallInteger('year');
            $table->decimal('amount', 15, 2);
            $table->boolean('is_paid')->default(false);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('transaction_item_id')->nullable()->constrained('transaction_items');
            $table->timestamps();

            // Idempotency: prevents duplicate obligations on re-runs
            $table->unique(['student_id', 'fee_type_id', 'month', 'year'], 'uq_obligation_period');

            $table->index(['year', 'month']);
        });

        // Partial index for unpaid obligations (PostgreSQL)
        DB::statement('CREATE INDEX idx_obligations_unpaid ON student_obligations(student_id, is_paid) WHERE is_paid = false');
    }

    public function down(): void
    {
        Schema::dropIfExists('student_obligations');
    }
};
