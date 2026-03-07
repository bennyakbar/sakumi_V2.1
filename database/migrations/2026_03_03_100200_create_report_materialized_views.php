<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // ──────────────────────────────────────────────────────────────────────
        // 1. AR Outstanding materialized view
        //
        // Pre-computes outstanding invoice amounts by joining settlement
        // allocations, eliminating expensive leftJoinSub on every page load.
        // ──────────────────────────────────────────────────────────────────────
        DB::unprepared("
            CREATE MATERIALIZED VIEW IF NOT EXISTS mv_ar_outstanding AS
            SELECT
                i.id AS invoice_id,
                i.unit_id,
                i.student_id,
                i.invoice_number,
                i.period_type,
                i.period_identifier,
                i.invoice_date,
                i.due_date,
                i.total_amount,
                i.status,
                COALESCE(sa_sum.settled_amount, 0) AS settled_amount,
                (i.total_amount - COALESCE(sa_sum.settled_amount, 0)) AS outstanding_amount
            FROM invoices i
            LEFT JOIN (
                SELECT sa.invoice_id, SUM(sa.amount) AS settled_amount
                FROM settlement_allocations sa
                JOIN settlements s ON s.id = sa.settlement_id AND s.status = 'completed'
                GROUP BY sa.invoice_id
            ) sa_sum ON sa_sum.invoice_id = i.id
            WHERE i.status != 'cancelled'
              AND i.total_amount > COALESCE(sa_sum.settled_amount, 0)
            WITH DATA
        ");

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS mv_ar_outstanding_pk ON mv_ar_outstanding (invoice_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS mv_ar_outstanding_unit ON mv_ar_outstanding (unit_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS mv_ar_outstanding_due ON mv_ar_outstanding (due_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS mv_ar_outstanding_student ON mv_ar_outstanding (student_id)');

        // ──────────────────────────────────────────────────────────────────────
        // 2. Monthly cash summary materialized view
        //
        // Pre-aggregates cash movements by day for cash book reports,
        // eliminating full-table scans and running-balance calculations.
        // ──────────────────────────────────────────────────────────────────────
        DB::unprepared("
            CREATE MATERIALIZED VIEW IF NOT EXISTS mv_daily_cash_summary AS
            SELECT
                entry_date,
                unit_id,
                SUM(debit) AS total_debit,
                SUM(credit) AS total_credit,
                SUM(debit) - SUM(credit) AS net
            FROM (
                SELECT
                    s.payment_date AS entry_date,
                    s.unit_id,
                    s.allocated_amount AS debit,
                    0 AS credit
                FROM settlements s
                WHERE s.status = 'completed' AND s.payment_method = 'cash'

                UNION ALL

                SELECT
                    t.transaction_date AS entry_date,
                    t.unit_id,
                    CASE WHEN t.type = 'income' THEN t.total_amount ELSE 0 END AS debit,
                    CASE WHEN t.type = 'expense' THEN t.total_amount ELSE 0 END AS credit
                FROM transactions t
                WHERE t.status = 'completed'
                  AND t.payment_method = 'cash'
                  AND (t.type = 'expense' OR (t.type = 'income' AND t.student_id IS NULL))
            ) daily
            GROUP BY entry_date, unit_id
            WITH DATA
        ");

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS mv_daily_cash_pk ON mv_daily_cash_summary (entry_date, unit_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS mv_daily_cash_unit ON mv_daily_cash_summary (unit_id)');

        // ──────────────────────────────────────────────────────────────────────
        // 3. Performance indexes for common report query patterns
        // ──────────────────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX IF NOT EXISTS idx_settlements_payment_date_status ON settlements (payment_date, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_transactions_date_status_type ON transactions (transaction_date, status, type)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_invoices_due_date_status ON invoices (due_date, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_activity_log_created ON activity_log (created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_activity_log_causer ON activity_log (causer_type, causer_id)');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('DROP MATERIALIZED VIEW IF EXISTS mv_ar_outstanding');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS mv_daily_cash_summary');

        DB::statement('DROP INDEX IF EXISTS idx_settlements_payment_date_status');
        DB::statement('DROP INDEX IF EXISTS idx_transactions_date_status_type');
        DB::statement('DROP INDEX IF EXISTS idx_invoices_due_date_status');
        DB::statement('DROP INDEX IF EXISTS idx_activity_log_created');
        DB::statement('DROP INDEX IF EXISTS idx_activity_log_causer');
    }
};
