<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_event_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->string('event', 50);
            $table->foreignId('user_id')->constrained('users');
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id', 'created_at'], 'fel_entity_timeline');
            $table->index('event');
            $table->index(['user_id', 'created_at'], 'fel_user_timeline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_event_logs');
    }
};
