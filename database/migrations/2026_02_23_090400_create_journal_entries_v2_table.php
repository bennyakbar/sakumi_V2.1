<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries_v2', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('accounting_event_id')->constrained('accounting_events')->restrictOnDelete();
            $table->unsignedInteger('line_no');
            $table->date('entry_date');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->string('account_code', 30);
            $table->string('description', 255)->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('currency', 3)->default('IDR');
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['accounting_event_id', 'line_no']);
            $table->index(['unit_id', 'entry_date']);
            $table->index(['unit_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries_v2');
    }
};
