<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('cancellation_reason');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable()->after('voided_by');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE settlements DROP CONSTRAINT IF EXISTS settlements_status_check");
            DB::statement("ALTER TABLE settlements ADD CONSTRAINT settlements_status_check CHECK (status IN ('completed', 'cancelled', 'void'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE settlements DROP CONSTRAINT IF EXISTS settlements_status_check");
            DB::statement("ALTER TABLE settlements ADD CONSTRAINT settlements_status_check CHECK (status IN ('completed', 'cancelled'))");
        }

        Schema::table('settlements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['voided_at', 'void_reason']);
        });
    }
};
