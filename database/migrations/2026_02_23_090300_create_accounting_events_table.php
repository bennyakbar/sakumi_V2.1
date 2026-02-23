<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->uuid('event_uuid')->unique();
            $table->string('event_type', 60);
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('idempotency_key', 191)->nullable()->unique();
            $table->date('effective_date');
            $table->timestamp('occurred_at');
            $table->foreignId('fiscal_period_id')->nullable()->constrained('fiscal_periods')->nullOnDelete();
            $table->boolean('is_reversal')->default(false);
            $table->foreignId('reversal_of_event_id')->nullable()->constrained('accounting_events')->nullOnDelete();
            $table->string('status', 20)->default('posted');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['unit_id', 'event_type', 'effective_date']);
            $table->index(['unit_id', 'source_type', 'source_id']);
            $table->index(['unit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_events');
    }
};
