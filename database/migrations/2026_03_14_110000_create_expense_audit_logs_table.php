<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_entry_id')->constrained('expense_entries')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('event_type', 50);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['expense_entry_id', 'created_at']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_audit_logs');
    }
};
