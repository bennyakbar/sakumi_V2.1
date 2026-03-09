<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // CEK: Hanya jalan jika koneksi sedang menggunakan sakumi_dummy
        if (DB::connection()->getName() === 'sakumi_dummy') {
            if (!Schema::hasColumn('invoices', 'updated_by')) {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->unsignedBigInteger('updated_by')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        if (DB::connection()->getName() === 'sakumi_dummy') {
            if (Schema::hasColumn('invoices', 'updated_by')) {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->dropColumn('updated_by');
                });
            }
        }
    }
};