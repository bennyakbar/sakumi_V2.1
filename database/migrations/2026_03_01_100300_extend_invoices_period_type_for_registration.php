<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_period_type_check');
            DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_period_type_check CHECK (period_type IN ('monthly', 'annual', 'registration'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_period_type_check');
            DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_period_type_check CHECK (period_type IN ('monthly', 'annual'))");
        }
    }
};
