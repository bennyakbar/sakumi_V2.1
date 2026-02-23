<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->string('event_type', 60);
            $table->string('line_key', 60);
            $table->string('entry_side', 10);
            $table->string('account_code', 30);
            $table->smallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->string('description', 255)->nullable();
            $table->json('filters')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'event_type', 'is_active']);
            $table->index(['unit_id', 'account_code']);
            $table->unique(['unit_id', 'event_type', 'line_key', 'entry_side', 'priority'], 'account_mappings_unique_rule');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_mappings');
    }
};
