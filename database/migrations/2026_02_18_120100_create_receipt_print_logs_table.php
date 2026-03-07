<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_print_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('printed_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('device')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['receipt_id', 'printed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_print_logs');
    }
};

