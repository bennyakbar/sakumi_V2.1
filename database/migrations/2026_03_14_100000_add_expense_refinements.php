<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add internal notes column to expense_entries
        Schema::table('expense_entries', function (Blueprint $table) {
            $table->string('internal_notes', 500)->nullable()->after('description');
        });

        // Create expense_attachments table
        Schema::create('expense_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_entry_id')->constrained('expense_entries')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('file_size')->default(0);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index('expense_entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_attachments');

        Schema::table('expense_entries', function (Blueprint $table) {
            $table->dropColumn('internal_notes');
        });
    }
};
