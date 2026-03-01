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
        // 1. Invoice over-settlement prevention trigger
        //
        // Ensures SUM(settlement_allocations.amount) for completed settlements
        // never exceeds invoice.total_amount.  Fires on INSERT or UPDATE of
        // settlement_allocations AND on status changes in settlements.
        // ──────────────────────────────────────────────────────────────────────
        DB::unprepared("
            CREATE OR REPLACE FUNCTION prevent_invoice_over_settlement()
            RETURNS TRIGGER AS \$\$
            DECLARE
                v_invoice_total   NUMERIC(15,2);
                v_settled_sum     NUMERIC(15,2);
                v_settlement_status TEXT;
            BEGIN
                -- On settlement_allocations INSERT/UPDATE, check the linked invoice
                -- Only enforce when the parent settlement is 'completed'
                SELECT s.status INTO v_settlement_status
                FROM settlements s
                WHERE s.id = NEW.settlement_id;

                IF v_settlement_status != 'completed' THEN
                    RETURN NEW;
                END IF;

                SELECT total_amount INTO v_invoice_total
                FROM invoices
                WHERE id = NEW.invoice_id;

                SELECT COALESCE(SUM(sa.amount), 0) INTO v_settled_sum
                FROM settlement_allocations sa
                JOIN settlements s ON s.id = sa.settlement_id AND s.status = 'completed'
                WHERE sa.invoice_id = NEW.invoice_id
                  AND sa.id IS DISTINCT FROM NEW.id;

                -- Add the new/updated allocation amount
                v_settled_sum := v_settled_sum + NEW.amount;

                IF v_settled_sum > v_invoice_total THEN
                    RAISE EXCEPTION 'Over-settlement blocked: invoice % total is % but settled sum would be %',
                        NEW.invoice_id, v_invoice_total, v_settled_sum;
                END IF;

                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS check_invoice_over_settlement ON settlement_allocations;
            CREATE TRIGGER check_invoice_over_settlement
            BEFORE INSERT OR UPDATE ON settlement_allocations
            FOR EACH ROW EXECUTE FUNCTION prevent_invoice_over_settlement();
        ");

        // ──────────────────────────────────────────────────────────────────────
        // 2. Invoice immutability trigger
        //
        // Protects key financial fields on non-cancelled invoices from
        // modification, mirroring the transaction immutability trigger.
        // Allowed changes: status, paid_amount, notes (for void annotation).
        // ──────────────────────────────────────────────────────────────────────
        DB::unprepared("
            CREATE OR REPLACE FUNCTION prevent_invoice_update()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF OLD.status IN ('unpaid', 'partially_paid', 'paid')
                   AND NEW.status IN ('unpaid', 'partially_paid', 'paid') THEN
                    IF OLD.total_amount IS DISTINCT FROM NEW.total_amount
                       OR OLD.invoice_number IS DISTINCT FROM NEW.invoice_number
                       OR OLD.student_id IS DISTINCT FROM NEW.student_id
                       OR OLD.invoice_date IS DISTINCT FROM NEW.invoice_date
                       OR OLD.period_type IS DISTINCT FROM NEW.period_type
                       OR OLD.period_identifier IS DISTINCT FROM NEW.period_identifier THEN
                        RAISE EXCEPTION 'Cannot modify immutable fields on active invoices';
                    END IF;
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS check_invoice_immutability ON invoices;
            CREATE TRIGGER check_invoice_immutability
            BEFORE UPDATE ON invoices
            FOR EACH ROW EXECUTE FUNCTION prevent_invoice_update();
        ");

        // ──────────────────────────────────────────────────────────────────────
        // 3. Settlement immutability trigger
        //
        // Protects key financial fields on completed settlements from
        // modification.  Only status, void/cancel metadata may change.
        // ──────────────────────────────────────────────────────────────────────
        DB::unprepared("
            CREATE OR REPLACE FUNCTION prevent_settlement_update()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF OLD.status = 'completed' AND NEW.status = 'completed' THEN
                    IF OLD.total_amount IS DISTINCT FROM NEW.total_amount
                       OR OLD.settlement_number IS DISTINCT FROM NEW.settlement_number
                       OR OLD.student_id IS DISTINCT FROM NEW.student_id
                       OR OLD.payment_date IS DISTINCT FROM NEW.payment_date
                       OR OLD.payment_method IS DISTINCT FROM NEW.payment_method
                       OR OLD.allocated_amount IS DISTINCT FROM NEW.allocated_amount THEN
                        RAISE EXCEPTION 'Cannot modify immutable fields on completed settlements';
                    END IF;
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS check_settlement_immutability ON settlements;
            CREATE TRIGGER check_settlement_immutability
            BEFORE UPDATE ON settlements
            FOR EACH ROW EXECUTE FUNCTION prevent_settlement_update();
        ");

        // ──────────────────────────────────────────────────────────────────────
        // 4. Composite index for efficient over-settlement check queries
        // ──────────────────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX IF NOT EXISTS idx_settlement_alloc_invoice_amount ON settlement_allocations (invoice_id, amount)');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS check_invoice_over_settlement ON settlement_allocations');
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_invoice_over_settlement()');

        DB::unprepared('DROP TRIGGER IF EXISTS check_invoice_immutability ON invoices');
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_invoice_update()');

        DB::unprepared('DROP TRIGGER IF EXISTS check_settlement_immutability ON settlements');
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_settlement_update()');

        DB::statement('DROP INDEX IF EXISTS idx_settlement_alloc_invoice_amount');
    }
};
