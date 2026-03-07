<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 30)->comment('e.g. NF-2026, NK-2026, INV-MI-2026, STL-2026');
            $table->unsignedBigInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique('prefix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
