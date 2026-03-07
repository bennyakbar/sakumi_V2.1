<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations_v2', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('accounting_event_id')->constrained('accounting_events')->restrictOnDelete();
            $table->string('payment_source_type', 100);
            $table->unsignedBigInteger('payment_source_id');
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->decimal('allocated_amount', 15, 2);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['unit_id', 'invoice_id']);
            $table->index(['unit_id', 'payment_source_type', 'payment_source_id']);
            $table->index(['accounting_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations_v2');
    }
};
