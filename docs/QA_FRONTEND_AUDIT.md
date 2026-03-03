# QA FRONTEND & ROUTE INTEGRITY AUDIT — SAKUMI
## Systematic Detection of Non-Functional UI, Broken Routes, and Silent Failures

**Date:** 3 March 2026
**System:** SAKUMI — Laravel 11 + PostgreSQL + Alpine.js
**Scope:** Routes, Controllers, Blade Forms, JavaScript, Error Handling

---

## TABLE OF CONTENTS

1. [Automated Audit Commands](#1-automated-audit-commands)
2. [Issues Found in Current Codebase](#2-issues-found-in-current-codebase)
3. [Middleware & Exception Protections](#3-middleware--exception-protections)
4. [Logging Strategy](#4-logging-strategy)
5. [Frontend Validation Checklist](#5-frontend-validation-checklist)
6. [Selenium-Style Test Flow](#6-selenium-style-test-flow)
7. [Step-by-Step QA Audit Checklist](#7-step-by-step-qa-audit-checklist)

---

## 1. AUTOMATED AUDIT COMMANDS

### 1.1 Route Integrity Audit

```bash
# Full audit with suggested fixes
php artisan audit:routes --fix

# Critical issues only (suitable for CI pipeline)
php artisan audit:routes --severity=critical

# JSON output for automated processing
php artisan audit:routes --json
```

**What it detects:**

| Check | Severity | Description |
|---|---|---|
| Missing controller class | CRITICAL | Route points to a controller that doesn't exist |
| Missing controller method | CRITICAL | Route points to a method that doesn't exist |
| Empty method body | CRITICAL | Controller method exists but has no implementation |
| Missing Blade view | CRITICAL | Controller calls `view('...')` but `.blade.php` is missing |
| No auth middleware | WARNING | Non-public route has no authentication protection |
| Duplicate URI | WARNING | Same HTTP method + URI defined twice |
| Unused controller method | INFO | Public method exists but no route points to it |

**File:** `app/Console/Commands/AuditRoutes.php`

### 1.2 Frontend Integrity Audit

```bash
# Full audit
php artisan audit:frontend

# Critical issues only
php artisan audit:frontend --severity=critical

# JSON output
php artisan audit:frontend --json
```

**What it detects:**

| Check | Severity | Description |
|---|---|---|
| Missing @csrf on POST/PUT/DELETE form | CRITICAL | Form submits without CSRF protection |
| fetch() without .catch() | CRITICAL | AJAX call fails silently |
| axios call without .catch() | CRITICAL | AJAX call fails silently |
| GET form with write action | CRITICAL | Form method mismatches route expectation |
| Dead link (href="#") | WARNING | Anchor goes nowhere |
| javascript:void(0) | WARNING | Obsolete pattern, should be `<button>` |
| Empty href attribute | WARNING | Anchor has no destination |
| Orphan button (no handler) | WARNING | `type="button"` without onclick/@click/wire:click |
| Unusual @method verb | WARNING | @method with unexpected verb |
| Static disabled attribute | INFO | Element always disabled without conditional |

**File:** `app/Console/Commands/AuditFrontend.php`

### 1.3 Smoke Test (PHPUnit)

```bash
# Run all smoke tests
php artisan test --filter=SmokeTest

# Individual assertions
php artisan test --filter=test_no_get_route_returns_500
php artisan test --filter=test_post_routes_reject_get_with_405
php artisan test --filter=test_write_routes_require_csrf
php artisan test --filter=test_protected_routes_redirect_guests
```

**What it detects:**

| Test | Description |
|---|---|
| `test_no_get_route_returns_500` | Hits every GET route as super_admin — none should return 500 |
| `test_post_routes_reject_get_with_405` | GET on a POST-only route must be 405, not 500 |
| `test_write_routes_require_csrf` | POST/PUT/PATCH/DELETE without CSRF must be 419, not 500 |
| `test_protected_routes_redirect_guests` | Protected routes must redirect guests to login, not 500 |

**File:** `tests/Feature/SmokeTest.php`

---

## 2. ISSUES FOUND IN CURRENT CODEBASE

### 2.1 Empty Controller Methods (RESOLVED)

Four controllers had empty `show()` method stubs left over from `--resource` scaffolding.
None of these had routes defined, views created, or UI links referencing them.

| Controller | Status |
|---|---|
| `Master\ClassController` | `show()` removed — dead code |
| `Master\FeeTypeController` | `show()` removed — dead code |
| `Master\FeeMatrixController` | `show()` removed — dead code |
| `Master\StudentCategoryController` | `show()` removed — dead code (entire controller is unrouted) |

The only `show()` methods that remain in `Master\` are the properly implemented ones:
`StudentController::show()` and `PromotionBatchController::show()`.

### 2.2 No Global AJAX Error Interceptor (FIXED)

**Before:** `resources/js/bootstrap.js` only set the `X-Requested-With` header. No error interceptor existed. Any AJAX failure (network error, 500, 419 session timeout) would fail silently.

**After:** Added axios response interceptor that handles:
- **419:** Session expired — prompts reload
- **403:** Permission denied — shows alert
- **429:** Rate limited — shows wait message
- **500+:** Server error — shows generic error + logs to console
- **Network error:** Connection lost — shows connectivity alert

### 2.3 Exception Handler (FIXED)

**Before:** `bootstrap/app.php` had an empty `withExceptions` block — relied entirely on Laravel defaults. AJAX 500 errors returned full HTML debug pages instead of JSON.

**After:** Configured renderable handlers for:
- 404 returns JSON `{"error": "...", "status": 404}` for AJAX requests
- 500+ returns JSON with sanitized message + logs full context

### 2.4 Dead Link

| File | Line | Element | Status |
|---|---|---|---|
| `transactions/create.blade.php` | 76 | `<a id="student-invoice-hint-link" href="#">` | ACCEPTABLE — dynamically populated via JS |

---

## 3. MIDDLEWARE & EXCEPTION PROTECTIONS

### 3.1 Middleware Stack (Current)

```
Request
  │
  ├─ ForceHttps         (production only, prepended)
  ├─ EncryptCookies      (framework)
  ├─ StartSession         (framework)
  ├─ VerifyCsrfToken      (framework)
  ├─ SetLocale            (appended)
  ├─ CheckInactivity      (appended — 2hr session timeout)
  ├─ EnsureUnitContext    (appended — multi-tenant scope)
  ├─ LogFailedActions     (appended — NEW)
  │
  ├─ Route-specific:
  │   ├─ auth             (authentication gate)
  │   ├─ verified          (email verification)
  │   ├─ role:...         (RBAC check)
  │   ├─ can:...          (permission check)
  │   ├─ throttle:...     (rate limiting)
  │   ├─ audit            (Spatie activity log)
  │   └─ restrict.roles   (super admin only)
  │
  Response
```

### 3.2 LogFailedActions Middleware (NEW)

**File:** `app/Http/Middleware/LogFailedActions.php`

Logs all 4xx/5xx responses on POST/PUT/PATCH/DELETE requests:

```
[2026-03-03 10:15:42] local.WARNING: Failed action {
    "status": 422,
    "method": "POST",
    "url": "https://sakumi.test/settlements",
    "route": "settlements.store",
    "user_id": 3,
    "user_email": "admin@mi.test",
    "ip": "127.0.0.1",
    "validation_errors": {"amount": ["Nominal melebihi outstanding."]}
}
```

### 3.3 Protection Matrix

| Attack/Failure | Protection Layer | Response |
|---|---|---|
| CSRF mismatch | `VerifyCsrfToken` middleware | 419 |
| Unauthenticated | `auth` middleware | Redirect to `/login` |
| Unauthorized role | `CheckRole` middleware | 403 |
| Session timeout | `CheckInactivity` middleware | Redirect to `/login` + flash message |
| Rate limit exceeded | `throttle` middleware | 429 |
| Wrong HTTP method | Laravel Router | 405 |
| Route not found | Laravel Router | 404 |
| Model not found | Route model binding | 404 |
| Validation failure | FormRequest | 422 + redirect back with errors |
| Server error (AJAX) | Exception handler | JSON `{"error": "...", "status": 500}` |
| Server error (browser) | Laravel error view | `errors/500.blade.php` |
| Network failure (JS) | Axios interceptor | Alert: "Koneksi terputus" |

---

## 4. LOGGING STRATEGY

### 4.1 What Gets Logged

| Event | Where | Middleware/Component |
|---|---|---|
| Every POST/PUT/PATCH/DELETE request | `activity_log` table | `AuditLog` middleware |
| Failed actions (4xx/5xx on writes) | `storage/logs/laravel.log` | `LogFailedActions` middleware |
| Login attempts (success/fail) | `activity_log` table | `LoginRequest` |
| Rate limit hits | Laravel default | `throttle` middleware |
| Session timeout logouts | `activity_log` table | `CheckInactivity` middleware |
| AJAX 500 errors | `storage/logs/laravel.log` | Exception handler |
| Model create/update/delete | `activity_log` table | Spatie ActivityLog trait |

### 4.2 Log Format

```
[YYYY-MM-DD HH:MM:SS] environment.LEVEL: Message {"context": "..."}
```

### 4.3 Monitoring Commands

```bash
# Real-time error monitoring
tail -f storage/logs/laravel.log | grep -E '\.(ERROR|WARNING):'

# Failed actions in last hour
grep 'Failed action' storage/logs/laravel.log | tail -20

# 500 errors in last 24h
grep -c 'status.*500' storage/logs/laravel.log

# Unique failed routes today
grep 'Failed action' storage/logs/laravel.log | \
  grep "$(date +%Y-%m-%d)" | \
  grep -oP '"route":"[^"]*"' | sort | uniq -c | sort -rn
```

---

## 5. FRONTEND VALIDATION CHECKLIST

### 5.1 Per-Page Manual Checklist

For every page in the application, verify:

```
┌─────────────────────────────────────────────────────────────┐
│ PAGE: _______________  URL: _______________                 │
│ TESTED BY: _______________  DATE: _______________           │
├─────────────────────────────────────────────────────────────┤
│ BUTTONS & LINKS                                             │
│ [ ] Every button performs a visible action on click          │
│ [ ] No button shows loading state indefinitely               │
│ [ ] Disabled buttons become enabled when condition is met    │
│ [ ] All navigation links reach the correct page              │
│ [ ] No 404 pages from any link on this page                  │
│                                                              │
│ FORMS                                                        │
│ [ ] Form submits successfully with valid data                │
│ [ ] Form shows validation errors with invalid data           │
│ [ ] Form shows errors inline (not just at page top)          │
│ [ ] Required fields are marked and enforced                  │
│ [ ] CSRF token is present (inspect source)                   │
│ [ ] @method directive matches route (PUT/PATCH/DELETE)        │
│ [ ] Success message appears after submission                 │
│ [ ] User is redirected to correct page after submit          │
│                                                              │
│ AJAX / DYNAMIC                                               │
│ [ ] AJAX actions show loading indicator                      │
│ [ ] AJAX failure shows user-friendly error message           │
│ [ ] Session timeout during AJAX shows reload prompt          │
│ [ ] Network disconnect during AJAX shows error message       │
│ [ ] Confirm dialogs appear before destructive actions        │
│                                                              │
│ ERROR STATES                                                 │
│ [ ] 403 shows "Tidak memiliki izin" (not white page)         │
│ [ ] 404 shows "Halaman tidak ditemukan" (not white page)     │
│ [ ] 500 shows friendly error (not stack trace)               │
│ [ ] Back button works after error                            │
│                                                              │
│ NOTES: ________________________________________________      │
└─────────────────────────────────────────────────────────────┘
```

### 5.2 Critical Page Inventory

| # | Page | URL | Priority |
|---|---|---|---|
| 1 | Login | `/login` | P0 |
| 2 | Dashboard | `/dashboard` | P0 |
| 3 | Create Transaction | `/transactions/create` | P0 |
| 4 | Create Settlement | `/settlements/create` | P0 |
| 5 | Create Invoice | `/invoices/create` | P0 |
| 6 | Generate Invoices | `/invoices/generate` | P0 |
| 7 | Print Receipt | `/receipts/{id}/print` | P0 |
| 8 | Daily Report | `/reports/daily` | P1 |
| 9 | Arrears Report | `/reports/arrears` | P1 |
| 10 | Student List | `/master/students` | P1 |
| 11 | Student Import | `/master/students/import` | P1 |
| 12 | Create Student | `/master/students/create` | P1 |
| 13 | User Management | `/users` | P1 |
| 14 | Fee Matrix | `/master/fee-matrix` | P1 |
| 15 | Bank Reconciliation | `/bank-reconciliation` | P2 |
| 16 | Admission Periods | `/admission/periods` | P2 |
| 17 | Applicant Workflow | `/admission/applicants/{id}` | P2 |
| 18 | Settings | `/settings` | P2 |
| 19 | Expense Entry | `/expenses` | P2 |
| 20 | Promotion Batches | `/master/promotion-batches` | P2 |

---

## 6. SELENIUM-STYLE TEST FLOW

### 6.1 Critical Path: Student Payment (End-to-End)

```
TEST: Complete Payment Cycle
ACTORS: Admin TU (admin_tu_mi)
PRECONDITIONS: Active student with unpaid invoice exists

STEP  ACTION                              ASSERTION
─────────────────────────────────────────────────────────────────
 1    Navigate to /login                  Page loads, no 500
 2    Enter email + password              Login form visible
 3    Click "Login"                       Redirect to /dashboard
 4    Verify dashboard loads              KPI cards visible, no error banner
 5    Navigate to /invoices               Table loads, rows visible
 6    Click student invoice row           Invoice detail page loads
 7    Verify invoice data                 student_name, amount, status visible
 8    Click "Pay Now" button              Redirect to /settlements/create
 9    Verify settlement form              student_name pre-filled, amount field
10    Enter payment amount                Field accepts numeric input
11    Select payment method "cash"        Dropdown works
12    Click "Save"                        Form submits (no JS error in console)
13    Verify redirect                     Redirect to settlement show page
14    Verify success message              Green banner: "Settlement berhasil"
15    Verify receipt button visible       "Cetak Kwitansi" button enabled
16    Click "Cetak Kwitansi"              Receipt page/PDF opens
17    Verify receipt content              student_name, amount, date, HMAC code
18    Navigate back to /invoices          Invoice status updated
19    Verify invoice status               status = "paid" or "partially_paid"
20    Navigate to /reports/daily          Today's transaction appears
21    Click "Logout"                      Redirect to /login

POST-CONDITIONS:
- Invoice.paid_amount increased by settlement amount
- Invoice.status updated accordingly
- Settlement record created with status "completed"
- SettlementAllocation record links settlement → invoice
- Receipt record created with verification_code
- Activity log records the transaction
- Daily report includes the transaction
```

### 6.2 Critical Path: Settlement Void

```
TEST: Void a Completed Settlement
ACTORS: Bendahara
PRECONDITIONS: Completed settlement exists

STEP  ACTION                              ASSERTION
─────────────────────────────────────────────────────────────────
 1    Login as Bendahara                  Dashboard loads
 2    Navigate to /settlements            Settlement list visible
 3    Find completed settlement           Row shows status "completed"
 4    Click settlement row                Detail page loads
 5    Click "Void" button                 Confirmation modal/prompt appears
 6    Enter void reason                   Text field accepts input
 7    Confirm void                        Form submits
 8    Verify redirect + success           "Settlement di-void" message
 9    Verify settlement status            status = "void"
10    Navigate to related invoice         paid_amount decreased
11    Verify invoice status rollback      status reverted if needed

ERROR CASES TO TEST:
 E1   Void without reason                Validation error shown
 E2   Void already-voided settlement     Blocked with message
 E3   Void as Operator TU               403 Forbidden
```

### 6.3 Critical Path: Invoice Generation

```
TEST: Batch Invoice Generation
ACTORS: Admin TU
PRECONDITIONS: Fee matrix configured, students with obligations

STEP  ACTION                              ASSERTION
─────────────────────────────────────────────────────────────────
 1    Login as Admin TU                   Dashboard loads
 2    Navigate to /invoices/generate      Generation form loads
 3    Select period type "monthly"        Month/year fields appear
 4    Select month and year               Fields accept values
 5    Click "Generate"                    Confirm dialog appears
 6    Confirm generation                  Processing begins
 7    Verify redirect                     Redirect to invoice index
 8    Verify success message              "X invoice berhasil dibuat"
 9    Verify invoices created             New invoices visible in list
10    Click one invoice                   Detail page with items

ERROR CASES TO TEST:
 E1   Generate duplicate period           Error: already generated
 E2   Generate with no active students    Error: no students found
 E3   Generate without fee matrix         Error: fee matrix empty
```

### 6.4 Error Simulation Tests

```
TEST: Session Timeout During AJAX
─────────────────────────────────────────────────────────────────
 1    Login normally
 2    Open browser dev tools → Application → Cookies
 3    Delete the session cookie
 4    Trigger an AJAX action (e.g., void settlement)
 5    ASSERT: Alert "Sesi Anda telah berakhir. Muat ulang halaman?"
 6    Click OK → page reloads to login

TEST: Network Failure During AJAX
─────────────────────────────────────────────────────────────────
 1    Login normally
 2    Open dev tools → Network → Offline mode
 3    Trigger an AJAX action
 4    ASSERT: Alert "Koneksi ke server terputus"

TEST: Direct URL Access Without Auth
─────────────────────────────────────────────────────────────────
 1    Open incognito browser
 2    Navigate to /dashboard directly
 3    ASSERT: Redirect to /login

TEST: Wrong Role Access
─────────────────────────────────────────────────────────────────
 1    Login as Kasir
 2    Navigate to /users (admin only)
 3    ASSERT: 403 page, not 500

TEST: Invalid Model ID
─────────────────────────────────────────────────────────────────
 1    Login as Admin TU
 2    Navigate to /invoices/999999
 3    ASSERT: 404 page, not 500
```

---

## 7. STEP-BY-STEP QA AUDIT CHECKLIST

### Phase 1: Automated Scans (10 minutes)

```bash
# 1. Route integrity
php artisan audit:routes --fix

# 2. Frontend integrity
php artisan audit:frontend

# 3. Smoke test (all routes respond without 500)
php artisan test --filter=SmokeTest

# 4. Full test suite
php artisan test
```

**Gate:** All critical issues from steps 1-3 must be resolved before proceeding.

### Phase 2: Error Page Verification (5 minutes)

```bash
# Test 404 page
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/this-page-does-not-exist

# Test 405 (GET on POST-only route)
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/locale

# Test 419 (POST without CSRF)
curl -s -o /dev/null -w "%{http_code}" -X POST http://localhost:8000/settlements

# Test 403 (wrong role — need auth cookie)
# → Manual: login as Kasir, visit /users
```

**Expected:** No response code should be 500. Each error must show a user-friendly page.

### Phase 3: Manual Form Testing (30 minutes)

For each form on the [Critical Page Inventory](#52-critical-page-inventory):

1. **Submit with valid data** → verify success
2. **Submit with empty required fields** → verify validation errors shown
3. **Submit with invalid data** (negative amount, future date, XSS string) → verify rejection
4. **Submit and immediately click back** → verify no duplicate submission
5. **Open form in two tabs, submit both** → verify no race condition / double entry

### Phase 4: JavaScript Interaction Testing (20 minutes)

1. **Open browser console** → reload every critical page → verify no JS errors
2. **Test dynamic forms**: add/remove items in transaction create, add/remove quotas in admission
3. **Test confirm dialogs**: void settlement, cancel transaction, permanent delete
4. **Test Alpine.js components**: sidebar collapse, mobile menu, applicant rejection reason toggle
5. **Disable JavaScript** → verify forms still submit (progressive enhancement)

### Phase 5: Security Verification (10 minutes)

```bash
# Check all forms have CSRF
php artisan audit:frontend --severity=critical

# Check all write routes are authenticated
php artisan audit:routes --severity=warning | grep "no auth middleware"

# Check rate limiting works
for i in $(seq 1 15); do
  curl -s -o /dev/null -w "%{http_code}\n" -X POST \
    http://localhost:8000/api/auth/login \
    -H "Content-Type: application/json" \
    -d '{"email":"test@test.com","password":"wrong"}';
done
# Last few should return 429
```

### Phase 6: Log Verification (5 minutes)

```bash
# Trigger a known error (e.g., submit invalid form)
# Then check it was logged:
grep 'Failed action' storage/logs/laravel.log | tail -5

# Check audit log captured the action:
php artisan tinker --execute="
    \Spatie\Activitylog\Models\Activity::latest()->take(5)->get(['description', 'subject_type', 'causer_id', 'created_at'])->each(fn(\$a) => dump(\$a->toArray()));
"
```

---

## APPENDIX: CI PIPELINE INTEGRATION

Add to `.github/workflows/ci.yml` or equivalent:

```yaml
- name: Route integrity audit
  run: php artisan audit:routes --severity=critical --json
  continue-on-error: false

- name: Frontend integrity audit
  run: php artisan audit:frontend --severity=critical --json
  continue-on-error: false

- name: Smoke test
  run: php artisan test --filter=SmokeTest
  continue-on-error: false
```

This ensures no broken route or missing CSRF can reach production.

---

*This audit should be re-run after every deployment and before every release.*
