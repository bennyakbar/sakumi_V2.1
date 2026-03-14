<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });

        Schema::table('settlements', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });

        // Seed maker-checker settings (off by default)
        \App\Models\Setting::set('maker_checker.invoices_enabled', false);
        \App\Models\Setting::set('maker_checker.settlements_enabled', false);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn('approved_at');
        });

        Schema::table('settlements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn('approved_at');
        });

        \App\Models\Setting::query()
            ->whereIn('key', ['maker_checker.invoices_enabled', 'maker_checker.settlements_enabled'])
            ->delete();
    }
};
