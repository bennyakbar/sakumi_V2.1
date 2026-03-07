<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reversals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('original_accounting_event_id')->constrained('accounting_events')->restrictOnDelete();
            $table->foreignId('reversal_accounting_event_id')->unique()->constrained('accounting_events')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['unit_id', 'original_accounting_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reversals');
    }
};
