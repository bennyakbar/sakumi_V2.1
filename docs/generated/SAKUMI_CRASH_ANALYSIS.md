# SAKUMI - Crash & Stability Analysis Report

Generated: 2026-03-12

---

## TABLE OF CONTENTS

1. [Critical Issues](#1-critical-issues)
2. [High Severity Issues](#2-high-severity-issues)
3. [Medium Severity Issues](#3-medium-severity-issues)
4. [Low Severity Issues](#4-low-severity-issues)
5. [Improvement Proposals](#5-improvement-proposals)
6. [Crash Prevention Checklist](#6-crash-prevention-checklist)

---

## 1. CRITICAL ISSUES

### C-01: Settlement allocation race condition — double payment risk

**File:** `app/Services/SettlementService.php:64-118`
**Risk Level:** CRITICAL

**Scenario:** In `createSettlement()`, invoice rows are locked with `lockForUpdate()` during validation (line 64), but the actual allocation write (line 117) uses a plain `Invoice::find()` without lock. Between lock release and re-fetch, a concurrent request could allocate the same invoice, causing over-payment.

```php
// Line 64 — locked query for validation
$invoice = Invoice::lockForUpdate()->where('id', $invoiceId)->first();

// Line 117 — UNLOCKED re-fetch for recalculation
$invoice = Invoice::find($invoiceId);
$invoice->recalculateFromAllocations();
```

**Crash:** Over-allocation silently corrupts financial data. Invoice `paid_amount` exceeds `total_amount`. Downstream reports show incorrect balances.

**Fix:**
```php
// Reuse the locked $invoice from the validation loop instead of re-fetching
// Store validated invoices in a map, then recalculate from those references
$validatedInvoices[$invoiceId] = $invoice; // during validation loop

// After allocation creation:
foreach ($validatedInvoices as $invoice) {
    $invoice->refresh(); // refresh within same transaction
    $invoice->recalculateFromAllocations();
}
```

**Best Practice:** Never release and re-acquire locks within the same transaction. Keep references to locked rows.

---

### C-02: Null crash on deleted FeeType in obligation→invoice chain

**File:** `app/Services/InvoiceService.php:146`
**Risk Level:** CRITICAL

**Scenario:** During invoice generation, `$obligation->feeType->name` is accessed without null check. If a FeeType is soft-deleted or hard-deleted while obligations reference it, this crashes.

```php
InvoiceItem::create([
    'description' => $obligation->feeType->name, // CRASH if feeType is null
]);
```

**Crash:** HTTP 500 during batch invoice generation. Entire generation batch fails, partial invoices may be created if transaction scope is per-student.

**Fix:**
```php
'description' => $obligation->feeType?->name ?? __('message.unknown_fee_type'),
```

**Best Practice:** Always use nullsafe operator on relationship accessors, especially in loops processing external data.

---

### C-03: Settlement→Invoice null crash in markObligationsFromAllocations

**File:** `app/Services/SettlementService.php:244`
**Risk Level:** CRITICAL

**Scenario:** After creating allocations, `markObligationsFromAllocations()` accesses `$allocation->invoice` without null check. If the invoice was deleted between allocation creation and this call:

```php
$invoice = $allocation->invoice;  // null if deleted
if ($invoice->status === 'paid') { // TypeError: accessing property on null
```

**Crash:** HTTP 500 during settlement creation. Transaction rolls back, but user gets generic error.

**Fix:**
```php
$invoice = $allocation->invoice;
if ($invoice && $invoice->status === 'paid') {
```

---

### C-04: ReceiptService PDF generation — no error handling

**File:** `app/Services/ReceiptService.php:34-36`
**Risk Level:** CRITICAL

**Scenario:** PDF generation and storage have zero error handling:

```php
$pdf = Pdf::loadView('receipts.template', $data);  // View error = crash
$path = "receipts/{$transaction->transaction_number}.pdf";
Storage::disk('public')->put($path, $pdf->output()); // Disk full/misconfigured = crash
```

**Crash:** HTTP 500 on transaction creation (receipt is generated in `DB::afterCommit`). Transaction is committed but receipt fails silently or crashes the response.

**Fix:**
```php
try {
    $pdf = Pdf::loadView('receipts.template', $data);
    Storage::disk('public')->put($path, $pdf->output());
    $transaction->update(['receipt_path' => $path]);
} catch (\Throwable $e) {
    Log::error('Receipt generation failed', [
        'transaction_id' => $transaction->id,
        'error' => $e->getMessage(),
    ]);
    // Transaction already committed — receipt can be regenerated later
}
```

---

### C-05: Auth bypass in InvoiceService::voidWithPayments

**File:** `app/Services/InvoiceService.php:270-273`
**Risk Level:** CRITICAL

**Scenario:** Permission check uses `Auth::user()` which returns null in CLI/queue context:

```php
$user = Auth::user();
if ($user && !$user->can('settlements.void')) {
    throw new \RuntimeException(...);
}
// If $user is null → permission check is SKIPPED entirely
```

**Crash:** No crash, but security bypass. Queued jobs or artisan commands can void settlements without permission checks.

**Fix:**
```php
$user = Auth::user();
if (!$user) {
    throw new \RuntimeException('Authentication required for void operations.');
}
if (!$user->can('settlements.void')) {
    throw new \RuntimeException(__('message.cancel_paid_invoice_requires_void_permission'));
}
```

---

### C-06: WhatsAppService null student crash

**File:** `app/Services/WhatsAppService.php:98-105`
**Risk Level:** CRITICAL

**Scenario:** `retry()` passes `$notification->student` to `send()` without null check. If student was deleted:

```php
public function retry(NotificationModel $notification): NotificationModel
{
    return $this->send(
        $notification->student,  // null if student deleted
        $notification->type,
        $notification->message
    );
}
// send() accesses $student->parent_whatsapp → TypeError
```

**Fix:**
```php
$student = $notification->student;
if (!$student) {
    throw new \RuntimeException("Student no longer exists for notification #{$notification->id}");
}
return $this->send($student, $notification->type, $notification->message);
```

---

### C-07: ReportService null FeeType crash

**File:** `app/Services/ReportService.php:39`
**Risk Level:** CRITICAL

**Scenario:** Daily report groups transaction items by fee_type and accesses `->feeType->name`. If FeeType is deleted:

```php
'fee_type' => $group->first()->feeType->name, // CRASH if feeType null
```

**Crash:** HTTP 500 on daily report page. Entire report fails for all users.

**Fix:**
```php
'fee_type' => $group->first()->feeType?->name ?? __('message.deleted_fee_type'),
```

---

### C-08: StudentFeeMapping::isActiveOn() null date crash

**File:** `app/Models/StudentFeeMapping.php:64`
**Risk Level:** CRITICAL

**Scenario:** `effective_from` could be null in database. Method calls `->gt()` on it:

```php
if ($this->effective_from->gt($date)) return false; // CRASH if null
```

**Crash:** TypeError during obligation generation for any student with a fee mapping that has null `effective_from`.

**Fix:**
```php
if ($this->effective_from && $this->effective_from->gt($date)) return false;
```

---

## 2. HIGH SEVERITY ISSUES

### H-01: AccountingEngine crash on missing ChartOfAccount

**File:** `app/Services/AccountingEngine.php:245-248`
**Risk Level:** HIGH

**Scenario:** If `ChartOfAccount` row is missing for a mapped account code, `RuntimeException` is thrown inside a DB transaction during invoice/settlement creation, causing the entire financial operation to fail.

**Fix:** Pre-validate account mappings during system setup. Add a health check that verifies all mapped account codes exist.

---

### H-02: TransactionService::createIncome — afterCommit exception swallowed

**File:** `app/Services/TransactionService.php:74-77`
**Risk Level:** HIGH

**Scenario:** `DB::afterCommit()` callback can crash (receipt generation fails). Transaction is already committed. Exception is not caught — may or may not surface to user depending on Laravel version.

```php
DB::afterCommit(function () use ($transaction) {
    $this->receiptService->generate($transaction); // can throw
    TransactionCreated::dispatch($transaction);     // can throw
});
```

**Fix:** Wrap afterCommit body in try-catch with logging.

---

### H-03: BankReconciliationService file handle leak

**File:** `app/Services/BankReconciliationService.php:35-76`
**Risk Level:** HIGH

**Scenario:** If exception thrown between `fopen()` and `fclose()`, file handle is never closed.

**Fix:**
```php
$handle = fopen($file->getRealPath(), 'rb');
try {
    // ... processing
} finally {
    if (is_resource($handle)) {
        fclose($handle);
    }
}
```

---

### H-04: ReceiptController::verifyByCode — null transaction access

**File:** `app/Http/Controllers/ReceiptController.php:135`
**Risk Level:** HIGH

**Scenario:** In receipt verification (public endpoint, no auth required):

```php
$transaction = $receipt->transaction;
$isVoided = ! $transaction || $transaction->status === 'cancelled';
```

If `$receipt->transaction` is null AND `$receipt->settlement` is also null → code continues with null objects, potential crash downstream.

**Fix:** Add early return with user-friendly message if neither transaction nor settlement exists.

---

### H-05: AdmissionService::enroll — null admissionPeriod crash

**File:** `app/Services/AdmissionService.php:190`
**Risk Level:** HIGH

**Scenario:**
```php
$academicYear = $applicant->admissionPeriod->academic_year ?? $year;
```
If `admissionPeriod` relationship returns null → TypeError.

**Fix:** `$applicant->admissionPeriod?->academic_year ?? $year`

---

### H-06: PromotionService::applyBatch — null student crash

**File:** `app/Services/PromotionService.php:76-79`
**Risk Level:** HIGH

**Scenario:**
```php
$item->student->update([
    'class_id' => $newEnrollment->class_id,
]);
```
If student is deleted, `$item->student` is null → crash.

**Fix:** Add null guard: `$item->student?->update(...)` or skip with warning.

---

### H-07: Immutable models throw without context

**Files:** `app/Models/AccountingEvent.php:48`, `app/Models/JournalEntryV2.php:47`
**Risk Level:** HIGH

**Scenario:** Update/delete attempts throw generic `RuntimeException`. If triggered during batch operations, entire batch fails with unhelpful error.

**Fix:** Use specific exception class with identifiable message. Consider logging before throwing.

---

### H-08: ReceiptVerificationService — insecure default HMAC key

**File:** `app/Services/ReceiptVerificationService.php:19`
**Risk Level:** HIGH

**Scenario:**
```php
$hmacKey = (string) (config('sakumi.receipt_hmac_key') ?: config('app.key', 'sakumi-default-key'));
```
Falls back to hardcoded string if both configs are null. Receipts signed with predictable key can be forged.

**Fix:** Throw exception if HMAC key is not configured in production.

---

### H-09: Database CHECK constraints only enforced in PostgreSQL

**Files:** Multiple migrations
**Risk Level:** HIGH

**Scenario:** Status enums (`unpaid`, `paid`, `cancelled`, etc.) and payment methods (`cash`, `transfer`, `qris`) are only validated by CHECK constraints in PostgreSQL. MySQL/SQLite accept invalid values.

**Fix:** Add Laravel model-level validation or use enum columns. Add `Illuminate\Validation\Rule::in()` in all service methods.

---

### H-10: session('current_unit_id') null throughout controllers

**Files:** Multiple controllers (SettlementController, InvoiceController, TransactionController, ExpenseController)
**Risk Level:** HIGH

**Scenario:** Many controllers access `session('current_unit_id')` without null guard. If session expires or unit isn't set:

```php
$unitId = session('current_unit_id'); // null
Rule::exists('students', 'id')->where('unit_id', $unitId) // WHERE unit_id IS NULL
```

**Crash:** Validation passes incorrectly (matches no rows or wrong rows).

**Fix:** Add middleware that guarantees unit context or throw early:
```php
$unitId = session('current_unit_id') ?? abort(403, 'No unit selected.');
```

---

## 3. MEDIUM SEVERITY ISSUES

### M-01: auth()->id() null in model boot methods

**Files:** `app/Models/Transaction.php:56`, `app/Models/Invoice.php:20`, `app/Models/Settlement.php:20`
**Risk Level:** MEDIUM

**Scenario:** Boot methods set `updated_by = auth()->id()` but in CLI/queued jobs, `auth()->id()` returns null. NULL stored in `updated_by` column.

**Fix:** Guard with `if (auth()->check())` (already done in some models, inconsistent).

---

### M-02: Setting static cache stale in long-running processes

**File:** `app/Models/Setting.php`
**Risk Level:** MEDIUM

**Scenario:** `private static array $runtimeCache = []` persists across requests in Octane/Swoole. Settings changes not reflected until restart.

**Fix:** Clear cache in Octane request lifecycle or use request-scoped caching.

---

### M-03: Error messages expose internal details

**Files:** Multiple controllers
**Risk Level:** MEDIUM

**Scenario:** Catch blocks return `$e->getMessage()` directly to users:

```php
return back()->with('error', $e->getMessage());
// Could expose: "SQLSTATE[23505]: Unique violation..."
```

**Fix:** Map exceptions to user-friendly messages. Never expose raw exception messages in production.

---

### M-04: Missing validation in several controller methods

**Files:**
- `ApplicantController::store()` — `$request->user()->id` without null check
- `StudentFeeMappingController::store()` — `auth()->id()` without null check
- `UnitSwitchController::__invoke()` — `$request->user()` without null check
- `ExpenseController::budgetVsRealization()` — input not validated

**Risk Level:** MEDIUM

---

### M-05: Nullable financial FK without constraint

**File:** `database/migrations/2026_02_11_110006_create_transactions_table.php:15`
**Risk Level:** MEDIUM

**Scenario:** `student_id` is nullable on transactions, allowing financial records with no student attribution.

---

### M-06: settlement_allocations asymmetric cascade/restrict

**File:** `database/migrations/2026_02_14_100004_create_settlement_allocations_table.php:14`
**Risk Level:** MEDIUM

**Scenario:** `settlement_id` cascades on delete but `invoice_id` restricts. If settlement somehow gets deleted (bypassing model protection), allocations cascade-delete, breaking invoice recalculation.

---

### M-07: Array cast fields could contain invalid JSON

**Files:** `app/Models/AccountingEvent.php`, `app/Models/JournalEntryV2.php`, `app/Models/BankReconciliationLog.php`
**Risk Level:** MEDIUM

**Scenario:** `payload` cast to `array` but could contain null or invalid JSON. Accessing `$event->payload['key']` crashes.

**Fix:** Use null coalesce: `($event->payload ?? [])['key'] ?? null`

---

## 4. LOW SEVERITY ISSUES

### L-01: DocumentSequence counter — theoretical overflow

**File:** `database/migrations/2026_03_03_100100_create_document_sequences_table.php`
**Risk:** `unsignedBigInteger` can overflow after 2^63 documents. Practically impossible but no bounds check.

### L-02: Missing index on student_obligations.transaction_item_id

**File:** `database/migrations/2026_02_11_110008_create_student_obligations_table.php`
**Risk:** Slow reverse lookups when cancelling transactions.

### L-03: Expense entries nullable period columns

**File:** `database/migrations/2026_03_11_100000_add_recommended_columns_to_expense_entries_table.php`
**Risk:** `period_year` and `period_month` nullable — new inserts could bypass backfill.

### L-04: BankReconciliationService silent skip on malformed CSV rows

**File:** `app/Services/BankReconciliationService.php:48-51`
**Risk:** `array_combine()` failure silently skipped. No logging or user notification.

### L-05: ReceiptController::print — multiple role strings not DRY

**File:** `app/Http/Controllers/Settlement/SettlementController.php:290`
**Risk:** Role list duplicated as hardcoded string array. Adding new roles requires updating multiple locations.

---

## 5. IMPROVEMENT PROPOSALS

### 5.1 Validation Improvements

```php
// 1. Create a UnitContext middleware to guarantee unit_id
class EnsureUnitContext
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('current_unit_id')) {
            return redirect()->route('dashboard')
                ->with('error', __('message.select_unit_first'));
        }
        return $next($request);
    }
}

// 2. Validate all financial amounts as positive
'amount' => 'required|numeric|min:0.01|max:999999999999.99',

// 3. Add form request classes for complex validations
class StoreSettlementRequest extends FormRequest
{
    public function rules(): array
    {
        $unitId = session('current_unit_id');
        return [
            'student_id' => ['required', Rule::exists('students', 'id')->where('unit_id', $unitId)],
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'required|in:cash,transfer,qris',
            'amount' => 'required|numeric|min:0.01',
        ];
    }
}
```

### 5.2 Service Layer Guards

```php
// Add guard trait for common checks
trait FinancialGuards
{
    protected function requireAuth(): User
    {
        return Auth::user() ?? throw new AuthenticationException('User context required.');
    }

    protected function requireUnitId(): int
    {
        $unitId = (int) session('current_unit_id');
        if ($unitId <= 0) {
            throw new \RuntimeException('Unit context is required.');
        }
        return $unitId;
    }

    protected function requireRelation(Model $model, string $relation): Model
    {
        $related = $model->{$relation};
        if (!$related) {
            throw new \RuntimeException("{$relation} not found for " . get_class($model) . " #{$model->id}");
        }
        return $related;
    }
}
```

### 5.3 Transaction Safety

```php
// 1. Always wrap multi-model writes in DB::transaction
// 2. Never re-fetch locked rows — reuse references
// 3. Add retry logic for deadlocks
DB::transaction(function () {
    // ... operations
}, attempts: 3); // Laravel retries on deadlock

// 4. Wrap afterCommit in try-catch
DB::afterCommit(function () use ($transaction) {
    try {
        $this->receiptService->generate($transaction);
    } catch (\Throwable $e) {
        Log::error('Post-commit receipt generation failed', [
            'transaction_id' => $transaction->id,
            'error' => $e->getMessage(),
        ]);
    }
});
```

### 5.4 Exception Handling Strategy

```php
// 1. Create domain-specific exceptions
namespace App\Exceptions;

class FinancialException extends \RuntimeException {}
class OverAllocationException extends FinancialException {}
class InvoiceAlreadyPaidException extends FinancialException {}
class SettlementAlreadyVoidException extends FinancialException {}

// 2. Map exceptions in Handler
class Handler extends ExceptionHandler
{
    protected function renderableExceptions(): array
    {
        return [
            FinancialException::class => fn ($e) => back()->with('error', $e->getMessage()),
            OverAllocationException::class => fn ($e) => back()->withErrors(['amount' => $e->getMessage()]),
        ];
    }
}

// 3. Never expose raw exception messages
catch (\Throwable $e) {
    Log::error('Operation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    return back()->with('error', __('message.operation_failed_contact_admin'));
}
```

### 5.5 User-Friendly Error Responses

```php
// Replace generic error catches:
// BEFORE:
return back()->with('error', $e->getMessage());

// AFTER:
return back()->with('error', match (true) {
    $e instanceof OverAllocationException => __('message.payment_exceeds_outstanding'),
    $e instanceof \Illuminate\Database\QueryException => __('message.database_error_try_again'),
    $e instanceof \Illuminate\Validation\ValidationException => $e->getMessage(),
    default => __('message.unexpected_error'),
});
```

### 5.6 Logging Improvements

```php
// 1. Structured logging for financial operations
Log::channel('financial')->info('Settlement created', [
    'settlement_id' => $settlement->id,
    'settlement_number' => $settlement->settlement_number,
    'student_id' => $settlement->student_id,
    'total_amount' => $settlement->total_amount,
    'allocations' => $allocations,
    'user_id' => $userId,
]);

// 2. Log all guard clause activations
if ($outstanding < 0) {
    Log::warning('Over-settled invoice detected', [
        'invoice_id' => $invoice->id,
        'total_amount' => $invoice->total_amount,
        'settled_amount' => $settledAmount,
        'calculated_outstanding' => $outstanding,
    ]);
    $outstanding = 0;
}

// 3. Add context to all error logs
Log::error('Settlement creation failed', [
    'student_id' => $data['student_id'],
    'allocations' => $allocations,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
    'user_id' => auth()->id(),
]);
```

---

## 6. CRASH PREVENTION CHECKLIST

### A. Code-Level Guards

- [ ] **C-01** Fix settlement allocation race condition — reuse locked invoice references
- [ ] **C-02** Add nullsafe on `$obligation->feeType?->name` in InvoiceService
- [ ] **C-03** Add null check on `$allocation->invoice` in markObligationsFromAllocations
- [ ] **C-04** Wrap ReceiptService PDF generation in try-catch
- [ ] **C-05** Fix auth bypass in InvoiceService::voidWithPayments — require user always
- [ ] **C-06** Add null student check in WhatsAppService::retry
- [ ] **C-07** Add nullsafe on `feeType?->name` in ReportService
- [ ] **C-08** Fix StudentFeeMapping::isActiveOn() null effective_from

### B. Service Layer

- [ ] **H-01** Pre-validate AccountingEngine chart of accounts mappings
- [ ] **H-02** Wrap DB::afterCommit callbacks in try-catch
- [ ] **H-03** Fix BankReconciliationService file handle leak with try-finally
- [ ] **H-05** Fix AdmissionService::enroll null admissionPeriod
- [ ] **H-06** Fix PromotionService::applyBatch null student
- [ ] **H-08** Throw if HMAC key not configured in production

### C. Controller Layer

- [ ] **H-04** Fix ReceiptController::verifyByCode null transaction
- [ ] **H-10** Add unit context middleware or guard in all financial controllers
- [ ] **M-03** Replace raw `$e->getMessage()` with user-friendly messages
- [ ] **M-04** Add null checks for `auth()->id()` in all controllers

### D. Database Integrity

- [ ] **H-09** Add model-level enum validation for status/payment_method fields
- [ ] **M-06** Review settlement_allocations cascade vs restrict FK behavior
- [ ] **L-02** Add index on `student_obligations.transaction_item_id`
- [ ] **L-03** Make `expense_entries.period_year` and `period_month` NOT NULL

### E. Architecture

- [ ] Create `App\Exceptions\FinancialException` hierarchy
- [ ] Create `EnsureUnitContext` middleware
- [ ] Create Form Request classes for Settlement, Invoice, Transaction
- [ ] Create `FinancialGuards` trait for service layer
- [ ] Add `financial` log channel for all money operations
- [ ] Add health check endpoint that validates accounting mappings
- [ ] Add model-level validation for enum fields (status, payment_method)

### F. Testing

- [ ] Write test: concurrent settlement creation on same invoice
- [ ] Write test: invoice generation with deleted FeeType
- [ ] Write test: settlement void with already-voided settlement
- [ ] Write test: receipt generation with disk full scenario
- [ ] Write test: settlement creation with null session unit_id
- [ ] Write test: obligation generation with null student fee mapping dates
- [ ] Write test: report generation with deleted relationships

### G. Monitoring

- [ ] Alert on over-settled invoice warnings in logs
- [ ] Alert on accounting engine failures
- [ ] Alert on receipt generation failures
- [ ] Monitor settlement void frequency (fraud indicator)
- [ ] Monitor receipt reprint frequency (fraud indicator)
- [ ] Dashboard widget for data integrity status

---

## SEVERITY SUMMARY

| Severity | Count | Action Required |
|----------|-------|-----------------|
| CRITICAL | 8 | Fix immediately — active crash/security risk |
| HIGH | 10 | Fix this sprint — stability risk under load |
| MEDIUM | 7 | Fix next sprint — data quality/UX risk |
| LOW | 5 | Backlog — minor improvements |

**Priority order:** C-01 (race condition) → C-05 (auth bypass) → C-04 (receipt crash) → C-02/C-03/C-06/C-07/C-08 (null crashes) → H-10 (unit context) → H-09 (DB constraints)
