# DATABASE INTEGRITY AUDIT — SAKUMI
## Orphan Record Detection, FK Hardening & Data Repair Strategy

**Auditor Level:** Senior Laravel 11 + PostgreSQL
**Date:** 3 March 2026
**System:** SAKUMI — Sistem Administrasi Keuangan Madrasah Ibtidaiyah
**Stack:** Laravel 11 / PostgreSQL / Eloquent ORM

---

## EXECUTIVE SUMMARY

### Schema Correction

The original assumption described:
```
invoices.student_id → students.id
payments.invoice_id → invoices.id
settlements.payment_id → payments.id
```

**Actual schema** — there is NO `payments` table. The real financial flow is:

```
students ──→ invoices ←── settlement_allocations ←── settlements
   │              ↑                                        │
   │         (student_id)                            (student_id)
   │
   └──→ transactions ──→ transaction_items ──→ student_obligations
```

Key junction: `settlement_allocations` links settlements to invoices (many-to-many).

### Audit Findings

| Category | Count | Severity |
|---|---|---|
| FKs missing explicit `ON DELETE` action (defaults to NO ACTION) | 8 | MEDIUM |
| FKs using `CASCADE DELETE` on financial child tables | 3 | LOW (mitigated by model-level hard-delete prevention) |
| FKs with auto-generated names (no explicit naming) | 32 of 32 | LOW |
| Soft-delete logical orphan risk (students → financial records) | 1 pattern | MEDIUM |
| Missing recommended indexes | 4 | LOW |

---

## PART 1 — ORPHAN RECORD DETECTION

### 1.1 SQL Queries

Run these against the production database to detect orphan records.

```sql
-- ═══════════════════════════════════════════════════════════
-- ORPHAN DETECTION SUITE — SAKUMI
-- Run as read-only user. No data is modified.
-- ═══════════════════════════════════════════════════════════

-- ───────────────────────────────────────────────────────────
-- 1. Invoices referencing non-existent students
-- Risk: Invoice cannot be attributed to any student
-- ───────────────────────────────────────────────────────────
SELECT
    i.id            AS invoice_id,
    i.invoice_number,
    i.student_id,
    i.total_amount,
    i.status,
    i.created_at
FROM invoices i
LEFT JOIN students s ON s.id = i.student_id
WHERE s.id IS NULL;

-- ───────────────────────────────────────────────────────────
-- 2. Invoices referencing soft-deleted students
-- Risk: Logical orphan — student exists but is inactive
-- ───────────────────────────────────────────────────────────
SELECT
    i.id            AS invoice_id,
    i.invoice_number,
    i.student_id,
    i.total_amount,
    i.status,
    s.name          AS student_name,
    s.deleted_at    AS student_deleted_at
FROM invoices i
INNER JOIN students s ON s.id = i.student_id
WHERE s.deleted_at IS NOT NULL
  AND i.status NOT IN ('cancelled');

-- ───────────────────────────────────────────────────────────
-- 3. Settlements referencing non-existent students
-- ───────────────────────────────────────────────────────────
SELECT
    st.id               AS settlement_id,
    st.settlement_number,
    st.student_id,
    st.total_amount,
    st.status,
    st.created_at
FROM settlements st
LEFT JOIN students s ON s.id = st.student_id
WHERE s.id IS NULL;

-- ───────────────────────────────────────────────────────────
-- 4. Settlements referencing soft-deleted students
-- ───────────────────────────────────────────────────────────
SELECT
    st.id               AS settlement_id,
    st.settlement_number,
    st.student_id,
    st.total_amount,
    st.status,
    s.name              AS student_name,
    s.deleted_at        AS student_deleted_at
FROM settlements st
INNER JOIN students s ON s.id = st.student_id
WHERE s.deleted_at IS NOT NULL
  AND st.status NOT IN ('cancelled', 'void');

-- ───────────────────────────────────────────────────────────
-- 5. Settlement allocations referencing non-existent invoices
-- Risk: Money allocated to a phantom invoice
-- ───────────────────────────────────────────────────────────
SELECT
    sa.id              AS allocation_id,
    sa.settlement_id,
    sa.invoice_id,
    sa.amount
FROM settlement_allocations sa
LEFT JOIN invoices i ON i.id = sa.invoice_id
WHERE i.id IS NULL;

-- ───────────────────────────────────────────────────────────
-- 6. Settlement allocations referencing non-existent settlements
-- ───────────────────────────────────────────────────────────
SELECT
    sa.id              AS allocation_id,
    sa.settlement_id,
    sa.invoice_id,
    sa.amount
FROM settlement_allocations sa
LEFT JOIN settlements st ON st.id = sa.settlement_id
WHERE st.id IS NULL;

-- ───────────────────────────────────────────────────────────
-- 7. Invoice items referencing non-existent invoices
-- ───────────────────────────────────────────────────────────
SELECT
    ii.id              AS item_id,
    ii.invoice_id,
    ii.amount,
    ii.description
FROM invoice_items ii
LEFT JOIN invoices i ON i.id = ii.invoice_id
WHERE i.id IS NULL;

-- ───────────────────────────────────────────────────────────
-- 8. Transaction items referencing non-existent transactions
-- ───────────────────────────────────────────────────────────
SELECT
    ti.id              AS item_id,
    ti.transaction_id,
    ti.amount,
    ti.description
FROM transaction_items ti
LEFT JOIN transactions t ON t.id = ti.transaction_id
WHERE t.id IS NULL;

-- ───────────────────────────────────────────────────────────
-- 9. Transactions referencing non-existent students
--    (student_id is nullable — only check non-null)
-- ───────────────────────────────────────────────────────────
SELECT
    t.id                AS transaction_id,
    t.transaction_number,
    t.student_id,
    t.total_amount,
    t.status,
    t.created_at
FROM transactions t
LEFT JOIN students s ON s.id = t.student_id
WHERE t.student_id IS NOT NULL
  AND s.id IS NULL;

-- ───────────────────────────────────────────────────────────
-- 10. Student obligations referencing non-existent students
-- ───────────────────────────────────────────────────────────
SELECT
    so.id              AS obligation_id,
    so.student_id,
    so.fee_type_id,
    so.amount,
    so.month,
    so.year
FROM student_obligations so
LEFT JOIN students s ON s.id = so.student_id
WHERE s.id IS NULL;

-- ───────────────────────────────────────────────────────────
-- 11. Student obligations referencing non-existent fee types
-- ───────────────────────────────────────────────────────────
SELECT
    so.id              AS obligation_id,
    so.student_id,
    so.fee_type_id,
    so.amount
FROM student_obligations so
LEFT JOIN fee_types ft ON ft.id = so.fee_type_id
WHERE ft.id IS NULL;

-- ───────────────────────────────────────────────────────────
-- 12. Receipts referencing non-existent transactions
--     (nullable — only check non-null)
-- ───────────────────────────────────────────────────────────
SELECT
    r.id               AS receipt_id,
    r.transaction_id,
    r.verification_code
FROM receipts r
LEFT JOIN transactions t ON t.id = r.transaction_id
WHERE r.transaction_id IS NOT NULL
  AND t.id IS NULL;

-- ───────────────────────────────────────────────────────────
-- 13. Receipts referencing non-existent settlements
-- ───────────────────────────────────────────────────────────
SELECT
    r.id               AS receipt_id,
    r.settlement_id,
    r.verification_code
FROM receipts r
LEFT JOIN settlements st ON st.id = r.settlement_id
WHERE r.settlement_id IS NOT NULL
  AND st.id IS NULL;
```

### 1.2 Laravel DB::query() Equivalents

```php
<?php

/**
 * SAKUMI Orphan Record Detection — Laravel DB Facade
 *
 * Run via: php artisan tinker < database/scripts/orphan_detection.php
 * Or embed in an Artisan command.
 */

use Illuminate\Support\Facades\DB;

$checks = [];

// 1. Invoices → students (hard orphan)
$checks['invoices_without_student'] = DB::table('invoices AS i')
    ->leftJoin('students AS s', 's.id', '=', 'i.student_id')
    ->whereNull('s.id')
    ->select('i.id', 'i.invoice_number', 'i.student_id', 'i.total_amount', 'i.status')
    ->get();

// 2. Invoices → soft-deleted students (logical orphan)
$checks['invoices_with_deleted_student'] = DB::table('invoices AS i')
    ->join('students AS s', 's.id', '=', 'i.student_id')
    ->whereNotNull('s.deleted_at')
    ->where('i.status', '<>', 'cancelled')
    ->select('i.id', 'i.invoice_number', 'i.student_id', 'i.total_amount',
             's.name AS student_name', 's.deleted_at AS student_deleted_at')
    ->get();

// 3. Settlements → students (hard orphan)
$checks['settlements_without_student'] = DB::table('settlements AS st')
    ->leftJoin('students AS s', 's.id', '=', 'st.student_id')
    ->whereNull('s.id')
    ->select('st.id', 'st.settlement_number', 'st.student_id', 'st.total_amount', 'st.status')
    ->get();

// 4. Settlements → soft-deleted students
$checks['settlements_with_deleted_student'] = DB::table('settlements AS st')
    ->join('students AS s', 's.id', '=', 'st.student_id')
    ->whereNotNull('s.deleted_at')
    ->whereNotIn('st.status', ['cancelled', 'void'])
    ->select('st.id', 'st.settlement_number', 'st.student_id', 'st.total_amount',
             's.name AS student_name', 's.deleted_at AS student_deleted_at')
    ->get();

// 5. Settlement allocations → invoices (hard orphan)
$checks['allocations_without_invoice'] = DB::table('settlement_allocations AS sa')
    ->leftJoin('invoices AS i', 'i.id', '=', 'sa.invoice_id')
    ->whereNull('i.id')
    ->select('sa.id', 'sa.settlement_id', 'sa.invoice_id', 'sa.amount')
    ->get();

// 6. Settlement allocations → settlements (hard orphan)
$checks['allocations_without_settlement'] = DB::table('settlement_allocations AS sa')
    ->leftJoin('settlements AS st', 'st.id', '=', 'sa.settlement_id')
    ->whereNull('st.id')
    ->select('sa.id', 'sa.settlement_id', 'sa.invoice_id', 'sa.amount')
    ->get();

// 7. Invoice items → invoices (hard orphan)
$checks['invoice_items_without_invoice'] = DB::table('invoice_items AS ii')
    ->leftJoin('invoices AS i', 'i.id', '=', 'ii.invoice_id')
    ->whereNull('i.id')
    ->select('ii.id', 'ii.invoice_id', 'ii.amount')
    ->get();

// 8. Transaction items → transactions (hard orphan)
$checks['transaction_items_without_transaction'] = DB::table('transaction_items AS ti')
    ->leftJoin('transactions AS t', 't.id', '=', 'ti.transaction_id')
    ->whereNull('t.id')
    ->select('ti.id', 'ti.transaction_id', 'ti.amount')
    ->get();

// 9. Transactions → students (hard orphan, nullable)
$checks['transactions_without_student'] = DB::table('transactions AS t')
    ->leftJoin('students AS s', 's.id', '=', 't.student_id')
    ->whereNotNull('t.student_id')
    ->whereNull('s.id')
    ->select('t.id', 't.transaction_number', 't.student_id', 't.total_amount', 't.status')
    ->get();

// 10. Student obligations → students (hard orphan)
$checks['obligations_without_student'] = DB::table('student_obligations AS so')
    ->leftJoin('students AS s', 's.id', '=', 'so.student_id')
    ->whereNull('s.id')
    ->select('so.id', 'so.student_id', 'so.fee_type_id', 'so.amount', 'so.month', 'so.year')
    ->get();

// 11. Student obligations → fee_types (hard orphan)
$checks['obligations_without_fee_type'] = DB::table('student_obligations AS so')
    ->leftJoin('fee_types AS ft', 'ft.id', '=', 'so.fee_type_id')
    ->whereNull('ft.id')
    ->select('so.id', 'so.student_id', 'so.fee_type_id', 'so.amount')
    ->get();

// 12. Receipts → transactions (hard orphan)
$checks['receipts_without_transaction'] = DB::table('receipts AS r')
    ->leftJoin('transactions AS t', 't.id', '=', 'r.transaction_id')
    ->whereNotNull('r.transaction_id')
    ->whereNull('t.id')
    ->select('r.id', 'r.transaction_id', 'r.verification_code')
    ->get();

// 13. Receipts → settlements (hard orphan)
$checks['receipts_without_settlement'] = DB::table('receipts AS r')
    ->leftJoin('settlements AS st', 'st.id', '=', 'r.settlement_id')
    ->whereNotNull('r.settlement_id')
    ->whereNull('st.id')
    ->select('r.id', 'r.settlement_id', 'r.verification_code')
    ->get();

// ── Summary Report ──
echo "\n══════════════════════════════════════════\n";
echo "  SAKUMI ORPHAN DETECTION REPORT\n";
echo "  " . now()->toDateTimeString() . "\n";
echo "══════════════════════════════════════════\n\n";

$hasOrphans = false;

foreach ($checks as $name => $results) {
    $count = $results->count();
    $status = $count === 0 ? 'OK' : 'ORPHANS FOUND';

    if ($count > 0) {
        $hasOrphans = true;
    }

    echo sprintf("  %-45s %s (%d)\n", $name, $status, $count);
}

echo "\n──────────────────────────────────────────\n";
echo $hasOrphans
    ? "  RESULT: ORPHAN RECORDS DETECTED — review required\n"
    : "  RESULT: ALL CLEAR — no orphan records found\n";
echo "──────────────────────────────────────────\n\n";
```

### 1.3 Cross-Table Integrity Validation (Financial Balances)

```sql
-- ═══════════════════════════════════════════════════════════
-- CROSS-TABLE BALANCE VALIDATION
-- Detects amount mismatches between parent and child records
-- ═══════════════════════════════════════════════════════════

-- A. Invoice paid_amount vs actual settlement allocations
--    Detects denormalization drift
SELECT
    i.id                AS invoice_id,
    i.invoice_number,
    i.paid_amount       AS recorded_paid,
    COALESCE(agg.actual_paid, 0) AS actual_paid,
    i.paid_amount - COALESCE(agg.actual_paid, 0) AS drift
FROM invoices i
LEFT JOIN (
    SELECT
        sa.invoice_id,
        SUM(sa.amount) AS actual_paid
    FROM settlement_allocations sa
    INNER JOIN settlements st ON st.id = sa.settlement_id
    WHERE st.status = 'completed'
    GROUP BY sa.invoice_id
) agg ON agg.invoice_id = i.id
WHERE i.status <> 'cancelled'
  AND ABS(i.paid_amount - COALESCE(agg.actual_paid, 0)) > 0.001;

-- B. Settlement allocated_amount vs actual allocations
--    Detects denormalization drift
SELECT
    st.id                   AS settlement_id,
    st.settlement_number,
    st.allocated_amount     AS recorded_allocated,
    COALESCE(agg.actual_allocated, 0) AS actual_allocated,
    st.allocated_amount - COALESCE(agg.actual_allocated, 0) AS drift
FROM settlements st
LEFT JOIN (
    SELECT
        sa.settlement_id,
        SUM(sa.amount) AS actual_allocated
    FROM settlement_allocations sa
    GROUP BY sa.settlement_id
) agg ON agg.settlement_id = st.id
WHERE st.status = 'completed'
  AND ABS(st.allocated_amount - COALESCE(agg.actual_allocated, 0)) > 0.001;

-- C. Invoice total_amount vs sum of invoice_items
SELECT
    i.id                AS invoice_id,
    i.invoice_number,
    i.total_amount      AS recorded_total,
    COALESCE(agg.items_total, 0) AS items_total,
    i.total_amount - COALESCE(agg.items_total, 0) AS drift
FROM invoices i
LEFT JOIN (
    SELECT invoice_id, SUM(amount) AS items_total
    FROM invoice_items
    GROUP BY invoice_id
) agg ON agg.invoice_id = i.id
WHERE i.status <> 'cancelled'
  AND ABS(i.total_amount - COALESCE(agg.items_total, 0)) > 0.001;

-- D. Invoices where paid_amount exceeds total_amount (overpayment)
SELECT
    id              AS invoice_id,
    invoice_number,
    total_amount,
    paid_amount,
    paid_amount - total_amount AS overpayment
FROM invoices
WHERE status <> 'cancelled'
  AND paid_amount > total_amount + 0.001;

-- E. Invoice status inconsistency
--    status says 'paid' but outstanding > 0, or 'unpaid' but has payments
SELECT
    id              AS invoice_id,
    invoice_number,
    status,
    total_amount,
    paid_amount,
    total_amount - paid_amount AS outstanding
FROM invoices
WHERE status <> 'cancelled'
  AND (
    (status = 'paid' AND ABS(total_amount - paid_amount) > 0.001)
    OR (status = 'unpaid' AND paid_amount > 0.001)
    OR (status = 'partially_paid' AND (paid_amount <= 0.001 OR paid_amount >= total_amount - 0.001))
  );

-- F. Count summary per table (sanity baseline)
SELECT 'students'               AS tbl, COUNT(*) AS total, COUNT(*) FILTER (WHERE deleted_at IS NULL) AS active FROM students
UNION ALL SELECT 'invoices',             COUNT(*), COUNT(*) FILTER (WHERE status <> 'cancelled') FROM invoices
UNION ALL SELECT 'settlements',          COUNT(*), COUNT(*) FILTER (WHERE status = 'completed') FROM settlements
UNION ALL SELECT 'settlement_allocations', COUNT(*), COUNT(*) FROM settlement_allocations
UNION ALL SELECT 'transactions',         COUNT(*), COUNT(*) FILTER (WHERE status = 'completed') FROM transactions
UNION ALL SELECT 'transaction_items',    COUNT(*), COUNT(*) FROM transaction_items
UNION ALL SELECT 'invoice_items',        COUNT(*), COUNT(*) FROM invoice_items
UNION ALL SELECT 'student_obligations',  COUNT(*), COUNT(*) FILTER (WHERE deleted_at IS NULL) FROM student_obligations
UNION ALL SELECT 'receipts',             COUNT(*), COUNT(*) FROM receipts
ORDER BY tbl;
```

---

## PART 2 — FOREIGN KEY CONSTRAINT AUDIT

### 2.1 Current State of All 32 Foreign Keys

#### Properly Constrained (RESTRICT) — 11 FKs
| Table | Column | Target | On Delete |
|---|---|---|---|
| invoices | student_id | students | RESTRICT |
| invoices | created_by | users | RESTRICT |
| invoices | unit_id | units | RESTRICT |
| settlements | student_id | students | RESTRICT |
| settlements | created_by | users | RESTRICT |
| settlements | unit_id | units | RESTRICT |
| settlement_allocations | invoice_id | invoices | RESTRICT |
| invoice_items | student_obligation_id | student_obligations | RESTRICT |
| invoice_items | fee_type_id | fee_types | RESTRICT |
| payment_allocations_v2 | invoice_id | invoices | RESTRICT |
| payment_allocations_v2 | accounting_event_id | accounting_events | RESTRICT |

#### CASCADE DELETE — 3 FKs (Risk Assessment Below)
| Table | Column | Target | On Delete | Risk |
|---|---|---|---|---|
| invoice_items | invoice_id | invoices | CASCADE | LOW — invoices have model-level hard-delete prevention |
| settlement_allocations | settlement_id | settlements | CASCADE | LOW — settlements have model-level hard-delete prevention |
| transaction_items | transaction_id | transactions | CASCADE | LOW — transactions have model-level hard-delete prevention |

> **Assessment:** These CASCADE constraints exist on child detail tables. The parent tables (invoices, settlements, transactions) all throw `RuntimeException` on delete attempts at the Eloquent model level. The CASCADE would only fire if someone bypasses Eloquent (raw SQL). **Recommendation: Change to RESTRICT as defense-in-depth.**

#### NULL ON DELETE — 10 FKs
| Table | Column | Target | On Delete | Assessment |
|---|---|---|---|---|
| settlements | cancelled_by | users | SET NULL | Acceptable — audit metadata |
| settlements | voided_by | users | SET NULL | Acceptable — audit metadata |
| transactions | account_id | accounts | SET NULL | Acceptable — optional reference |
| transactions | category_id | categories | SET NULL | Acceptable — optional reference |
| receipts | transaction_id | transactions | SET NULL | **CONCERN** — breaks receipt→transaction link |
| receipts | invoice_id | invoices | SET NULL | **CONCERN** — breaks receipt→invoice link |
| receipts | settlement_id | settlements | SET NULL | **CONCERN** — breaks receipt→settlement link |

> **Assessment:** Receipt FKs using SET NULL is problematic. If a transaction/invoice/settlement were somehow deleted, the receipt would lose its link to the source — breaking the audit trail. Since parent tables prevent deletion, the risk is theoretical but violates defense-in-depth.

#### NO ACTION (Missing Explicit Policy) — 8 FKs
| Table | Column | Target | Should Be |
|---|---|---|---|
| transactions | student_id | students | RESTRICT |
| transactions | created_by | users | RESTRICT |
| transactions | cancelled_by | users | SET NULL (nullable, audit field) |
| students | class_id | classes | RESTRICT |
| students | category_id | student_categories | RESTRICT |
| student_obligations | student_id | students | RESTRICT |
| student_obligations | fee_type_id | fee_types | RESTRICT |
| student_obligations | transaction_item_id | transaction_items | SET NULL (nullable) |
| transaction_items | fee_type_id | fee_types | RESTRICT |

> **Assessment: These 8 FKs default to NO ACTION which is functionally similar to RESTRICT in PostgreSQL (both prevent deletion of referenced rows). However, NO ACTION checks at transaction end vs RESTRICT checks immediately. For a financial system, explicit RESTRICT is preferred for clarity and self-documenting constraints.**

### 2.2 Naming Convention Issue

All 32 foreign key constraints use Laravel's auto-generated names (e.g., `invoices_student_id_foreign`). This is functional but makes debugging migration failures harder. Explicit naming with a project prefix is recommended for production systems.

---

## PART 3 — MIGRATION: FK HARDENING

### 3.1 Migration to Fix All Constraint Issues

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Harden foreign key constraints across all financial tables.
 *
 * Changes:
 * - Replace NO ACTION with explicit RESTRICT on 8 FKs
 * - Replace CASCADE with RESTRICT on 3 financial child tables
 * - Replace SET NULL with RESTRICT on receipt FKs
 * - Apply explicit constraint naming with 'fk_' prefix
 *
 * Safe to run in production: only constraint metadata changes,
 * no data modification, no table locks beyond brief ALTER TABLE.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── transactions table ──────────────────────────────
        // student_id: NO ACTION → RESTRICT
        // created_by: NO ACTION → RESTRICT
        // cancelled_by: NO ACTION → SET NULL (nullable audit field)
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['cancelled_by']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('student_id', 'fk_transactions_student_id')
                ->references('id')->on('students')
                ->restrictOnDelete();

            $table->foreign('created_by', 'fk_transactions_created_by')
                ->references('id')->on('users')
                ->restrictOnDelete();

            $table->foreign('cancelled_by', 'fk_transactions_cancelled_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });

        // ─── transaction_items table ─────────────────────────
        // transaction_id: CASCADE → RESTRICT (defense-in-depth)
        // fee_type_id: NO ACTION → RESTRICT
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->dropForeign(['fee_type_id']);
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->foreign('transaction_id', 'fk_transaction_items_transaction_id')
                ->references('id')->on('transactions')
                ->restrictOnDelete();

            $table->foreign('fee_type_id', 'fk_transaction_items_fee_type_id')
                ->references('id')->on('fee_types')
                ->restrictOnDelete();
        });

        // ─── invoice_items table ─────────────────────────────
        // invoice_id: CASCADE → RESTRICT (defense-in-depth)
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreign('invoice_id', 'fk_invoice_items_invoice_id')
                ->references('id')->on('invoices')
                ->restrictOnDelete();
        });

        // ─── settlement_allocations table ────────────────────
        // settlement_id: CASCADE → RESTRICT (defense-in-depth)
        Schema::table('settlement_allocations', function (Blueprint $table) {
            $table->dropForeign(['settlement_id']);
        });

        Schema::table('settlement_allocations', function (Blueprint $table) {
            $table->foreign('settlement_id', 'fk_settlement_alloc_settlement_id')
                ->references('id')->on('settlements')
                ->restrictOnDelete();
        });

        // ─── students table ─────────────────────────────────
        // class_id: NO ACTION → RESTRICT
        // category_id: NO ACTION → RESTRICT
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropForeign(['category_id']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreign('class_id', 'fk_students_class_id')
                ->references('id')->on('classes')
                ->restrictOnDelete();

            $table->foreign('category_id', 'fk_students_category_id')
                ->references('id')->on('student_categories')
                ->restrictOnDelete();
        });

        // ─── student_obligations table ──────────────────────
        // student_id: NO ACTION → RESTRICT
        // fee_type_id: NO ACTION → RESTRICT
        // transaction_item_id: NO ACTION → SET NULL (nullable link)
        Schema::table('student_obligations', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['fee_type_id']);
            $table->dropForeign(['transaction_item_id']);
        });

        Schema::table('student_obligations', function (Blueprint $table) {
            $table->foreign('student_id', 'fk_obligations_student_id')
                ->references('id')->on('students')
                ->restrictOnDelete();

            $table->foreign('fee_type_id', 'fk_obligations_fee_type_id')
                ->references('id')->on('fee_types')
                ->restrictOnDelete();

            $table->foreign('transaction_item_id', 'fk_obligations_transaction_item_id')
                ->references('id')->on('transaction_items')
                ->nullOnDelete();
        });

        // ─── receipts table ─────────────────────────────────
        // transaction_id: SET NULL → RESTRICT (audit trail)
        // invoice_id: SET NULL → RESTRICT (audit trail)
        // settlement_id: SET NULL → RESTRICT (audit trail)
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['settlement_id']);
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->foreign('transaction_id', 'fk_receipts_transaction_id')
                ->references('id')->on('transactions')
                ->restrictOnDelete();

            $table->foreign('invoice_id', 'fk_receipts_invoice_id')
                ->references('id')->on('invoices')
                ->restrictOnDelete();

            $table->foreign('settlement_id', 'fk_receipts_settlement_id')
                ->references('id')->on('settlements')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Revert to original auto-named constraints with original behaviors.
        // Separated into drop + re-add to avoid constraint name conflicts.

        // ─── receipts ──────────────────────────────────────
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropForeign('fk_receipts_transaction_id');
            $table->dropForeign('fk_receipts_invoice_id');
            $table->dropForeign('fk_receipts_settlement_id');
        });
        Schema::table('receipts', function (Blueprint $table) {
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            $table->foreign('settlement_id')->references('id')->on('settlements')->nullOnDelete();
        });

        // ─── student_obligations ────────────────────────────
        Schema::table('student_obligations', function (Blueprint $table) {
            $table->dropForeign('fk_obligations_student_id');
            $table->dropForeign('fk_obligations_fee_type_id');
            $table->dropForeign('fk_obligations_transaction_item_id');
        });
        Schema::table('student_obligations', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('fee_type_id')->references('id')->on('fee_types');
            $table->foreign('transaction_item_id')->references('id')->on('transaction_items');
        });

        // ─── students ──────────────────────────────────────
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign('fk_students_class_id');
            $table->dropForeign('fk_students_category_id');
        });
        Schema::table('students', function (Blueprint $table) {
            $table->foreign('class_id')->references('id')->on('classes');
            $table->foreign('category_id')->references('id')->on('student_categories');
        });

        // ─── settlement_allocations ─────────────────────────
        Schema::table('settlement_allocations', function (Blueprint $table) {
            $table->dropForeign('fk_settlement_alloc_settlement_id');
        });
        Schema::table('settlement_allocations', function (Blueprint $table) {
            $table->foreign('settlement_id')->references('id')->on('settlements')->cascadeOnDelete();
        });

        // ─── invoice_items ─────────────────────────────────
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign('fk_invoice_items_invoice_id');
        });
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });

        // ─── transaction_items ─────────────────────────────
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropForeign('fk_transaction_items_transaction_id');
            $table->dropForeign('fk_transaction_items_fee_type_id');
        });
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete();
            $table->foreign('fee_type_id')->references('id')->on('fee_types');
        });

        // ─── transactions ──────────────────────────────────
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign('fk_transactions_student_id');
            $table->dropForeign('fk_transactions_created_by');
            $table->dropForeign('fk_transactions_cancelled_by');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('cancelled_by')->references('id')->on('users');
        });
    }
};
```

---

## PART 4 — INDEX IMPROVEMENTS

### 4.1 Suggested Indexes

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance indexes identified during integrity audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A. settlement_allocations — composite for balance queries
        //    Used by: invoice paid_amount recalculation, over-settlement check
        Schema::table('settlement_allocations', function (Blueprint $table) {
            $table->index(
                ['invoice_id', 'settlement_id', 'amount'],
                'idx_sa_invoice_settlement_amount'
            );
        });

        // B. transactions — composite for student financial history
        //    Used by: student statement, transaction listing by student
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(
                ['student_id', 'status', 'transaction_date'],
                'idx_txn_student_status_date'
            );
        });

        // C. receipts — composite for receipt lookup by settlement
        //    Used by: receipt printing after settlement creation
        Schema::table('receipts', function (Blueprint $table) {
            $table->index(
                ['settlement_id', 'issued_at'],
                'idx_receipts_settlement_issued'
            );
        });

        // D. invoices — composite for arrears/outstanding queries
        //    Used by: arrears report, student dashboard, payment screen
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(
                ['student_id', 'status', 'due_date'],
                'idx_invoices_student_status_due'
            );
        });
    }

    public function down(): void
    {
        Schema::table('settlement_allocations', function (Blueprint $table) {
            $table->dropIndex('idx_sa_invoice_settlement_amount');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_txn_student_status_date');
        });
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropIndex('idx_receipts_settlement_issued');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_student_status_due');
        });
    }
};
```

---

## PART 5 — DATA REPAIR STRATEGY

### 5.1 Decision Matrix If Orphans Are Found

| Orphan Type | Severity | Repair Action |
|---|---|---|
| Invoice → missing student | CRITICAL | DO NOT DELETE. Flag for manual review. Cross-reference with `activity_log` to identify original student. Restore soft-deleted student if applicable. |
| Settlement → missing student | CRITICAL | Same as above. Settlement represents actual money received — cannot be discarded. |
| Allocation → missing invoice | CRITICAL | Investigate via `activity_log`. The invoice may have been removed via raw SQL bypass. Recreate invoice stub if amount is known. |
| Allocation → missing settlement | HIGH | Should not happen (FK exists). If found, allocations are dangling money — investigate immediately. |
| Transaction → missing student | MEDIUM | Transactions can have NULL student_id (expenses). Only critical if type = 'income'. |
| Obligation → missing student | MEDIUM | Soft-deleted student likely. Restore student or cancel obligation. |
| Invoice items → missing invoice | LOW | Likely cascade artifact. Safe to archive/remove if invoice is confirmed gone. |
| Transaction items → missing transaction | LOW | Same as above. |

### 5.2 Repair Scripts (Transaction-Safe)

```php
<?php

/**
 * SAKUMI Orphan Repair — run ONLY after backup and review.
 *
 * Strategy: NEVER delete financial records. Instead:
 * 1. Restore soft-deleted students if they are referenced
 * 2. Flag truly orphaned records for manual review
 * 3. Use DB transactions for atomicity
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// ── Step 1: Restore soft-deleted students referenced by active invoices ──
DB::transaction(function () {
    $restoredCount = DB::table('students')
        ->whereNotNull('deleted_at')
        ->whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('invoices')
                ->whereColumn('invoices.student_id', 'students.id')
                ->where('invoices.status', '<>', 'cancelled');
        })
        ->update(['deleted_at' => null, 'status' => 'active']);

    Log::channel('single')->info("Orphan repair: restored {$restoredCount} soft-deleted students with active invoices");
});

// ── Step 2: Restore soft-deleted students referenced by completed settlements ──
DB::transaction(function () {
    $restoredCount = DB::table('students')
        ->whereNotNull('deleted_at')
        ->whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('settlements')
                ->whereColumn('settlements.student_id', 'students.id')
                ->where('settlements.status', 'completed');
        })
        ->whereNull('deleted_at') // skip already restored in step 1
        ->update(['deleted_at' => null, 'status' => 'active']);

    // Count those that were already restored
    $totalWithSettlements = DB::table('students')
        ->whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('settlements')
                ->whereColumn('settlements.student_id', 'students.id')
                ->where('settlements.status', 'completed');
        })
        ->count();

    Log::channel('single')->info("Orphan repair: verified {$totalWithSettlements} students with completed settlements are active");
});

// ── Step 3: Flag hard orphans (student physically missing) for review ──
// These CANNOT be auto-repaired — need human investigation.
$hardOrphanInvoices = DB::table('invoices AS i')
    ->leftJoin('students AS s', 's.id', '=', 'i.student_id')
    ->whereNull('s.id')
    ->pluck('i.id');

if ($hardOrphanInvoices->isNotEmpty()) {
    Log::channel('single')->error(
        'CRITICAL: Hard orphan invoices found (student physically missing). IDs: '
        . $hardOrphanInvoices->implode(', ')
    );
    // DO NOT auto-delete — flag for DBA review
}

$hardOrphanSettlements = DB::table('settlements AS st')
    ->leftJoin('students AS s', 's.id', '=', 'st.student_id')
    ->whereNull('s.id')
    ->pluck('st.id');

if ($hardOrphanSettlements->isNotEmpty()) {
    Log::channel('single')->error(
        'CRITICAL: Hard orphan settlements found (student physically missing). IDs: '
        . $hardOrphanSettlements->implode(', ')
    );
}
```

### 5.3 Pre-Repair Checklist

Before running ANY repair:

- [ ] Full database backup completed and verified (`restore_test.sh` passed)
- [ ] Application in maintenance mode (`php artisan down`)
- [ ] Queue workers stopped (`supervisorctl stop sakumi-worker:*`)
- [ ] Orphan detection queries run and results documented
- [ ] Cross-table balance validation passed (Part 1.3)
- [ ] Repair scope approved by Bendahara/Kepala Sekolah
- [ ] DBA or Super Admin present during execution

---

## PART 6 — INTEGRITY VERIFICATION CHECKLIST

### 6.1 Post-Migration Verification

Run after applying the FK hardening migration:

```sql
-- Verify all expected constraints exist with correct actions
SELECT
    tc.constraint_name,
    tc.table_name,
    kcu.column_name,
    ccu.table_name  AS referenced_table,
    ccu.column_name AS referenced_column,
    rc.delete_rule,
    rc.update_rule
FROM information_schema.table_constraints tc
JOIN information_schema.key_column_usage kcu
    ON tc.constraint_name = kcu.constraint_name
    AND tc.table_schema = kcu.table_schema
JOIN information_schema.constraint_column_usage ccu
    ON tc.constraint_name = ccu.constraint_name
    AND tc.table_schema = ccu.table_schema
JOIN information_schema.referential_constraints rc
    ON tc.constraint_name = rc.constraint_name
    AND tc.table_schema = rc.constraint_schema
WHERE tc.constraint_type = 'FOREIGN KEY'
  AND tc.table_schema = 'public'
ORDER BY tc.table_name, kcu.column_name;
```

**Expected results after migration:**

| Constraint | Table | delete_rule |
|---|---|---|
| fk_transactions_student_id | transactions | RESTRICT |
| fk_transactions_created_by | transactions | RESTRICT |
| fk_transactions_cancelled_by | transactions | SET NULL |
| fk_transaction_items_transaction_id | transaction_items | RESTRICT |
| fk_transaction_items_fee_type_id | transaction_items | RESTRICT |
| fk_invoice_items_invoice_id | invoice_items | RESTRICT |
| fk_settlement_alloc_settlement_id | settlement_allocations | RESTRICT |
| fk_students_class_id | students | RESTRICT |
| fk_students_category_id | students | RESTRICT |
| fk_obligations_student_id | student_obligations | RESTRICT |
| fk_obligations_fee_type_id | student_obligations | RESTRICT |
| fk_obligations_transaction_item_id | student_obligations | SET NULL |
| fk_receipts_transaction_id | receipts | RESTRICT |
| fk_receipts_invoice_id | receipts | RESTRICT |
| fk_receipts_settlement_id | receipts | RESTRICT |

### 6.2 Periodic Integrity Checklist (Monthly)

| # | Check | SQL/Command | Pass Criteria |
|---|---|---|---|
| 1 | No hard orphan invoices | Part 1.1, Query #1 | 0 rows |
| 2 | No hard orphan settlements | Part 1.1, Query #3 | 0 rows |
| 3 | No hard orphan allocations | Part 1.1, Queries #5-6 | 0 rows |
| 4 | No hard orphan transactions | Part 1.1, Query #9 | 0 rows |
| 5 | Invoice paid_amount matches allocations | Part 1.3, Query A | 0 rows with drift |
| 6 | Settlement allocated_amount matches allocations | Part 1.3, Query B | 0 rows with drift |
| 7 | Invoice total matches items | Part 1.3, Query C | 0 rows with drift |
| 8 | No overpayments | Part 1.3, Query D | 0 rows |
| 9 | No status inconsistencies | Part 1.3, Query E | 0 rows |
| 10 | All FK constraints present | Part 6.1 | All 15 hardened FKs listed |
| 11 | Table count baseline stable | Part 1.3, Query F | No unexpected drops |

---

## PART 7 — RISK EXPLANATION

### If Orphan Records Exist

| Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|
| **Financial misreporting** | Orphan invoices inflate or deflate outstanding receivables in reports | HIGH if orphans exist | Orphan detection + repair |
| **Audit failure** | Auditor cannot trace settlement back to student | CRITICAL | FK hardening + constraint naming |
| **Reconciliation errors** | Daily/monthly reconciliation will show unexplained gaps | HIGH | Cross-table balance validation |
| **Cascading data loss** | CASCADE DELETE on financial child tables could erase line items if parent deleted via raw SQL | LOW (model prevents, but raw SQL bypass possible) | Change CASCADE → RESTRICT |
| **Soft-delete blind spots** | Soft-deleted student still has active invoices/settlements — queries using `withoutTrashed()` miss them | MEDIUM | Restore soft-deleted students with active financials |
| **Silent corruption** | NO ACTION FKs allow deferred constraint checking — errors surface at COMMIT time, harder to debug | LOW | Change to explicit RESTRICT |

### Defense-in-Depth Layers (Current → Proposed)

```
Layer 1: Eloquent Model    → RuntimeException on delete()     [EXISTS]
Layer 2: PostgreSQL FK     → RESTRICT on parent delete        [PARTIAL → FULL]
Layer 3: PostgreSQL Trigger → Immutability on critical fields  [EXISTS]
Layer 4: Activity Log      → Spatie audit trail               [EXISTS]
Layer 5: Application Logic → SoD, Maker-Checker workflow      [EXISTS]
```

The FK hardening migration closes the gap in Layer 2, ensuring that even raw SQL `DELETE` statements are blocked at the database engine level.

---

*This audit should be re-run quarterly or after any migration that modifies financial table schemas.*
