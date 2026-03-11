<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_entries', function (Blueprint $table) {
            // Estimation vs realization tracking
            $table->decimal('estimated_amount', 15, 2)->nullable()->after('amount');
            $table->decimal('realized_amount', 15, 2)->nullable()->after('estimated_amount');

            // Period columns for indexed reporting (denormalized from entry_date)
            $table->smallInteger('period_year')->nullable()->after('entry_date');
            $table->smallInteger('period_month')->nullable()->after('period_year');

            // Attachment support (Option A: inline columns)
            $table->string('receipt_path')->nullable()->after('description');
            $table->string('supporting_doc_path')->nullable()->after('receipt_path');

            // Reporting index on period columns
            $table->index(
                ['unit_id', 'period_year', 'period_month', 'status'],
                'idx_expense_entries_period_report'
            );
        });

        // Backfill period columns from existing entry_date
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(<<<'SQL'
                UPDATE expense_entries
                SET period_year = EXTRACT(YEAR FROM entry_date)::smallint,
                    period_month = EXTRACT(MONTH FROM entry_date)::smallint
                WHERE period_year IS NULL
            SQL);
        } else {
            DB::statement(<<<'SQL'
                UPDATE expense_entries
                SET period_year = CAST(strftime('%Y', entry_date) AS INTEGER),
                    period_month = CAST(strftime('%m', entry_date) AS INTEGER)
                WHERE period_year IS NULL
            SQL);
        }
    }

    public function down(): void
    {
        Schema::table('expense_entries', function (Blueprint $table) {
            $table->dropIndex('idx_expense_entries_period_report');
            $table->dropColumn([
                'estimated_amount',
                'realized_amount',
                'period_year',
                'period_month',
                'receipt_path',
                'supporting_doc_path',
            ]);
        });
    }
};
