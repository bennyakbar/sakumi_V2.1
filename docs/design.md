# Design Document
## School Financial Management System (MI/SD)

## 1. Design Purpose
This document defines the implementation design for the selected build strategy:
**Option 2 - Core-first phased rollout**.

Design is based on:
- `openspec/SISTEM_KEUANGAN_SEKOLAH.md` (version 2.1 — Production-Ready Enhancement, February 10, 2026)
- `proposal.md`

## 2. Build Strategy
### 2.1 Release Waves
- **Wave 1 (Core Go-Live, Weeks 1–8):**
  - Master Data (classes, categories, fee types, fee matrix, students)
  - Transactions (income/expense with immutability enforcement)
  - PDF Receipts (auto-generate, reprint, cancellation watermark)
  - Obligations & Arrears core processing (monthly generation, payment tracking)
  - Core reports (daily/monthly/arrears with PDF/Excel export)
- **Wave 2 (Enhancement, Weeks 9–12):**
  - Dashboard analytics (summary cards, charts, trends)
  - WhatsApp automation (payment confirmation, arrears reminders)
  - User management hardening (role-escalation protection, audit log viewer)
  - Settings & backup management UI (configuration-as-code, manual backup)

### 2.2 Design Principles
- Deliver business-critical finance operations first.
- Keep one codebase (Laravel monolith) with clear internal module boundaries.
- Use staged releases (local → staging → production) with UAT gate.
- Financial data is immutable — corrections use cancellation + replacement, never edit/delete.
- All design decisions below are sourced from the spec unless marked `[Assumption]`.

## 3. System Architecture Design
### 3.1 Layered Architecture

(Source: spec section 1.2–1.3)

```
Client Layer (Browser via HTTPS/TLS 1.3)
    ↓
Presentation Layer (Blade + Tailwind CSS 3.x + Alpine.js 3.x)
    ↓
Application Layer (Controllers → Services → Events/Listeners)
                   (Middleware → Form Requests → Resources)
    ↓
Data Access Layer (Eloquent ORM → Query Builder → Migrations)
    ↓
Database Layer (PostgreSQL 15+, ACID Compliant)
```

External integrations:
- WhatsApp Gateway API (notifications)
- Storage (Local/S3 for PDFs & backups)
- Email Service (SMTP/Mailgun for alerts)

### 3.2 Component Structure

(Source: spec section 1.3)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/               # Authentication (Breeze)
│   │   ├── Master/             # Student, Class, Category, FeeType, FeeMatrix
│   │   ├── Transaction/        # Income & Expense
│   │   ├── Report/             # Daily, Monthly, Arrears
│   │   ├── Admin/              # User management, Settings, Backup
│   │   └── Dashboard/          # Main dashboard
│   ├── Middleware/
│   │   ├── CheckRole.php
│   │   ├── AuditLog.php
│   │   ├── ForceHttps.php
│   │   ├── RestrictRoleManagement.php
│   │   └── CheckInactivity.php
│   └── Requests/               # Form validation (FormRequest classes)
├── Models/
│   ├── User, Student, Transaction, StudentObligation, ...
├── Services/
│   ├── TransactionService.php  # Number generation, income/expense creation, cancellation
│   ├── ReceiptService.php      # PDF generation, terbilang, watermark
│   ├── ReportService.php       # Daily/monthly/arrears reporting, chart data
│   ├── ArrearsService.php      # Monthly obligation generation, arrears aggregation
│   └── WhatsAppService.php     # Gateway integration, notification dispatch
├── Events/
│   ├── TransactionCreated.php
│   └── ObligationGenerated.php
├── Listeners/
│   ├── SendPaymentNotification.php
│   └── UpdateArrearsStatus.php
├── Console/Commands/
│   ├── GenerateMonthlyObligations.php
│   └── SendArrearsReminder.php
└── Exports/
    ├── DailyReportExport.php
    ├── MonthlyReportExport.php
    └── ArrearsReportExport.php
```

### 3.3 Runtime Components

- **Web app process:** PHP-FPM + Nginx.
- **Queue backend:** `QUEUE_CONNECTION=database` (PostgreSQL `jobs` / `failed_jobs` tables). Chosen for zero additional infrastructure — no Redis dependency. Sufficient for expected throughput (notifications, PDF generation). Revisit if queue depth regularly exceeds 1000 pending jobs.
- **Queue worker process:** Supervisor-managed (source: spec section 8.4):
  - `numprocs: 2`
  - `--sleep=3 --tries=3 --max-time=3600`
  - `user: www-data`
  - Auto-start, auto-restart enabled.
- **Scheduler/cron** (source: spec section 8.3):

  | Schedule | Command | Function |
  |----------|---------|----------|
  | `0 0 1 * *` | `obligations:generate` | Monthly obligation generation (1st, midnight) |
  | `0 9 * * 1` | `arrears:remind` | Arrears reminders via WhatsApp (Monday, 09:00) |
  | `0 2 * * *` | `backup:run` | Encrypted daily backup (02:00) |
  | `*/5 * * * *` | Health check curl | System monitoring + email alert on failure |

- **Health endpoints:** `GET /health/live` (public liveness probe) and `GET /health` (authenticated diagnostics — requires `super_admin` or IP allowlist). Detail in section 6.9.

## 4. Module Design

### 4.1 Master Data Module

(Source: spec sections 2.1, 3, 5)

**Entities:** classes, student_categories, fee_types, fee_matrix, students.

**Fee matrix resolution** — specificity-based priority ordering:
- Query filters: `fee_type_id` match, `class_id` match OR NULL, `category_id` match OR NULL, effective date within range, `is_active = true`.
- Sort: `ORDER BY class_id DESC, category_id DESC` then take first result.
- Effect: a rate for "Kelas 1A + Reguler" beats "all classes + Reguler" beats "all classes + all categories."
- Effective date window: `effective_from <= date` AND (`effective_to IS NULL` OR `effective_to >= date`).

**Student lifecycle:**
- Active → graduated (soft delete via `status = 'graduated'`).
- Import/export via Excel (`maatwebsite/excel`) for bulk onboarding.
- Search supports NIS, NISN, name with pagination and filters.

### 4.2 Transaction Module

(Source: spec sections 5, 6.0)

**Transaction numbering** — concurrency-safe via pessimistic locking:
- Format: `NF-{YYYY}-{NNNNNN}` (income), `NK-{YYYY}-{NNNNNN}` (expense).
- Mechanism: inside `DB::transaction()`, query last number with `lockForUpdate()`, increment, format with `sprintf('%s-%s-%06d', ...)`.
- Guarantees no duplicate numbers under concurrent access.

**Income creation** — financial write + deferred side effects:

*Phase 1: Atomic DB transaction* (`DB::beginTransaction()`):
1. Create `transactions` row (with auto-generated number).
2. Create `transaction_items` rows (loop over line items).
3. Update `student_obligations` → set `is_paid = true`, `paid_amount`, `paid_at`, `transaction_item_id` (for monthly fees with month/year).
4. Commit.

*Phase 2: After-commit side effects* (via `DB::afterCommit()` or dispatched jobs):
5. Generate PDF receipt via `ReceiptService::generate()` → store `receipt_path` on transaction.
6. Fire `TransactionCreated` event (triggers WhatsApp notification listener).

- Rationale: PDF generation and event dispatch are I/O-heavy side effects. Running them inside the transaction holds locks longer, reduces throughput, and risks rolling back the financial write due to unrelated I/O failures (e.g. PDF engine error, queue unavailable).
- Side effects in Phase 2 use retry-safe queued jobs with compensating logic if they fail (e.g. receipt can be regenerated on demand).
- If PDF generation fails, the transaction remains valid — receipt is generated on retry or manual reprint.

**Immutability enforcement:**
- Application: no edit/delete routes. Corrections via cancel + replacement.
- Database trigger `prevent_transaction_update()`: prevents changes to 6 financial/identity fields (`total_amount`, `transaction_date`, `student_id`, `transaction_number`, `type`, `description`) on rows where `status = 'completed'` using null-safe `IS DISTINCT FROM` (detail in section 5.3).
- `receipt_path` is intentionally unprotected — it is a system-managed artifact path written after commit (Phase 2).
- CHECK constraint: `status IN ('completed', 'cancelled')`.

### 4.3 Receipt Module

(Source: spec section 5, ReceiptService)

- Auto-generate PDF on successful income transaction (Phase 2, step 5 — after commit).
- Template includes: school profile (name, address, logo from settings), payment details, **terbilang** (number-to-words in Indonesian).
- Cancelled transactions: receipt regenerated with "DIBATALKAN" watermark overlay.
- Reprint: supported and logged in audit trail.
- PDF engine: `barryvdh/laravel-dompdf` ^3.0.

### 4.4 Obligation & Arrears Module

(Source: spec section 5, ArrearsService; section 8.3)

- **Monthly obligation generation:** cron command `obligations:generate` (1st of every month, midnight).
  - Iterates active students.
  - For each student, looks up applicable fee matrix entries (monthly fee types).
  - Creates `student_obligations` records with calculated amount using **upsert** strategy (`INSERT ... ON CONFLICT DO NOTHING`).
  - **Idempotency guarantee:** unique composite constraint on `(student_id, fee_type_id, period_month, period_year)` prevents duplicate obligations on re-runs or retries.
  - Command is safe to retry manually without risk of overstating arrears.
- **Payment tracking:** obligations auto-updated when income transactions are posted (step 3 of atomic flow).
- **Arrears aggregation:** grouped by student, class, and period. Supports reporting filters.

### 4.5 Reporting Module

(Source: spec section 5, ReportService; Exports)

- **Daily report:** income/expense summary with breakdown by type, daily statistics.
- **Monthly report:** summary with daily stats, Chart.js data generation for visualization.
- **Arrears report:** grouped by class, by month, with student-level detail.
- Export: PDF (DomPDF) and Excel (`maatwebsite/excel`).
- Export classes: `DailyReportExport`, `MonthlyReportExport`, `ArrearsReportExport`.

### 4.6 Notification Module (Wave 2)

(Source: spec section 5, WhatsAppService; section 6.7)

- **Payment confirmation** (`payment_success`): triggered by `TransactionCreated` event → `SendPaymentNotification` listener.
- **Arrears reminder** (`arrears_reminder`): triggered by `arrears:remind` cron (Monday 09:00). Sends to students with arrears exceeding threshold (default: 1 month, configurable via `arrears_threshold_months` setting).
- **Message templates:** customizable via admin UI. Placeholders: `{student_name}`, `{fee_type}`, `{amount}` (stored in `notification_payment_template` setting).
- **Phone validation:** Indonesian format regex `/^628[0-9]{8,11}$/`, stored in `students.parent_whatsapp`.
- **Delivery tracking:** `notifications` table with `whatsapp_status` (pending/sent/failed), `whatsapp_sent_at`, `whatsapp_response`.
- **Failed notifications:** logged with error in `whatsapp_response`; manual retry supported.
- **Gateway config:**
  - `whatsapp_gateway_url`: configurable via admin UI (stored in `settings` table).
  - `whatsapp_api_key`: stored **only** in `.env` (never in database). Not editable via admin UI. Rotation is an ops-only operation via environment variable update + deployment.
  - Rationale: keeping secrets out of DB reduces blast radius if admin account is compromised and avoids credential exposure in database backups/dumps.

### 4.7 User/Role & Settings Module (Wave 2 hardening)

(Source: spec sections 4, 6.7)

**RBAC** — five roles via `spatie/laravel-permission` ^6.0:

| Role | Key Access |
|------|-----------|
| `super_admin` | Full access, sensitive role management |
| `bendahara` | Transactions, receipts, reports, fee matrix |
| `kepala_sekolah` | View-only dashboard, students, transactions, reports |
| `operator_tu` | Students, classes, view transactions/reports |
| `auditor` | View-only all data, audit log |

**Role-escalation protection:**
- Only `super_admin` can assign/modify roles.
- Users cannot change their own role or deactivate themselves.
- All role changes permanently logged in `activity_log`.
- Email alert sent when `super_admin` role is assigned.
- Enforced via `RestrictRoleManagement` middleware.

**Configuration-as-Code:**
- All operational settings in `settings` table (key-value store).
- Type casting: string, number (float), boolean (`filter_var`), json (`json_decode`).
- In-memory cache per request (`static $cache`); public settings cached 1 hour (`Cache::remember`).
- Global helpers: `getSetting($key, $default)`, `setSetting($key, $value)`.
- Categories: school, receipt, notification, arrears, system.
- Admin UI: grouped by category, editable by `super_admin`.

## 5. Data Design

### 5.1 Core Tables

(Source: spec section 2.1)

14+ tables organized in four groups:

**Authentication & Authorization:**
`users`, `roles`, `permissions`, `role_has_permissions`

**Master Data:**
`classes`, `student_categories`, `students`, `fee_types`, `fee_matrix`

**Financial Operations:**
`transactions`, `transaction_items`, `student_obligations`

**System:**
`notifications`, `activity_log`, `settings`

### 5.2 Soft Delete Policy Matrix

(Source: spec section 6.0.4)

| Table | Soft Delete | Alternative | Reason |
|-------|-------------|-------------|--------|
| `students` | YES | `status = 'graduated'` | Historical integrity, may return |
| `transactions` | NO | `status = 'cancelled'` | Financial immutability |
| `transaction_items` | NO | Cancel parent transaction | Child of immutable transaction |
| `users` | YES | `is_active = false` | Account lifecycle, re-activation |
| `fee_matrix` | YES | `is_active = false` | Historical tariff records |
| `classes` | YES | `is_active = false` | Historical class records |
| `fee_types` | YES | May be reactivated | Lifecycle management |
| `student_categories` | NO | Mark inactive | Referenced by active students |
| `student_obligations` | NO | `is_paid` flag only | Payment history |
| `notifications` | YES | Auto-delete after 6 months | Storage cleanup |
| `activity_log` | NO | **Never delete** | Permanent audit trail |

### 5.3 Integrity Controls

**Database trigger for immutability** (source: spec section 6.0):

```sql
CREATE OR REPLACE FUNCTION prevent_transaction_update()
RETURNS TRIGGER AS $$
BEGIN
    IF OLD.status = 'completed' AND NEW.status = 'completed' THEN
        -- Use IS DISTINCT FROM for null-safe comparison (handles nullable student_id on expenses)
        IF OLD.total_amount IS DISTINCT FROM NEW.total_amount
           OR OLD.transaction_date IS DISTINCT FROM NEW.transaction_date
           OR OLD.student_id IS DISTINCT FROM NEW.student_id
           OR OLD.transaction_number IS DISTINCT FROM NEW.transaction_number
           OR OLD.type IS DISTINCT FROM NEW.type
           OR OLD.description IS DISTINCT FROM NEW.description THEN
            RAISE EXCEPTION 'Cannot modify completed transactions';
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER check_transaction_immutability
BEFORE UPDATE ON transactions
FOR EACH ROW EXECUTE FUNCTION prevent_transaction_update();
```

- Protected fields on completed transactions: `total_amount`, `transaction_date`, `student_id`, `transaction_number`, `type`, `description`.
- Uses `IS DISTINCT FROM` (null-safe) instead of `!=` to prevent NULL bypass (e.g. `student_id` is nullable on expense transactions).
- **Intentionally unprotected:** `receipt_path` — must remain writable after commit so the after-commit PDF generation (Phase 2, step 5) can store the path. `receipt_path` is a system-managed derived artifact, not a financial field.
- Only `status`, `receipt_path`, and system-managed timestamps (`updated_at`) may change on completed rows.
- Status transition `completed → cancelled` is permitted (the trigger allows it because `NEW.status` would be `'cancelled'`).
- CHECK constraint: `status IN ('completed', 'cancelled')`.

**Other integrity controls:**
- Unique constraints on identity fields (`nis`, `nisn`, `transaction_number`, `email`).
- Unique composite constraint on `student_obligations(student_id, fee_type_id, period_month, period_year)` — prevents duplicate obligation generation.
- Foreign keys for relational consistency.
- Indexing strategy: composite indexes on frequent query paths (date + status, student + period), partial indexes on active records (`WHERE is_active = true`), trigram indexes for name search.

## 6. Security Design

(Source: spec sections 4, 6.0–6.6)

### 6.1 Authentication & Authorization
- Authentication via `laravel/breeze` ^2.0.
- RBAC via `spatie/laravel-permission` ^6.0 with per-module authorization gates.
- Role-escalation protection via `RestrictRoleManagement` middleware (detail in section 4.7).

### 6.2 Input Validation & Sanitization
- Global `BaseRequest` strips HTML tags and trims all string inputs.
- SQL injection prevention: Eloquent ORM / parameterized queries.
- XSS prevention: always `{{ }}` (escaped), never `{!! !!}` in Blade.
- CSRF protection via `@csrf` (Laravel default).

### 6.3 Rate Limiting

(Source: spec section 6.3)

| Endpoint | Max Attempts | Decay Period |
|----------|-------------|-------------|
| Login | 5 | 15 minutes |
| API (general) | 100 | 1 minute |
| Transaction creation | 10 | 1 minute |

### 6.4 Password Policy

(Source: spec section 6.2)

- Minimum 8 characters.
- At least 1 uppercase, 1 lowercase, 1 number, 1 special character (`@$!%*?&`).
- Password reset token expires in 60 minutes.
- Reset throttle: 60 seconds.

### 6.5 Session Security

(Source: spec section 6.4)

| Setting | Value |
|---------|-------|
| `lifetime` | 120 minutes (2 hours) |
| `expire_on_close` | false |
| `encrypt` | true |
| `http_only` | true |
| `secure` | true (HTTPS only in production) |
| `same_site` | lax |
| Inactivity auto-logout | 7200 seconds (via `CheckInactivity` middleware) |

### 6.6 File Upload Security

(Source: spec section 6.5)

- Allowed MIME types: jpg, jpeg, png, pdf.
- Max size: 2048 KB (2 MB).
- Directory traversal prevention via `basename()`.
- Storage: `receipts` disk (public), `backups` disk (private).

### 6.7 Database Security

Two dedicated PostgreSQL roles with least-privilege separation:

| Role | Purpose | Grants |
|------|---------|--------|
| `app_runtime` | Used by PHP-FPM / queue workers at runtime | SELECT, INSERT, UPDATE, DELETE only |
| `app_migrator` | Used exclusively during deployment migrations | SELECT, INSERT, UPDATE, DELETE + CREATE, ALTER, DROP |

- **Runtime** (`app_runtime`): configured in `.env` `DB_USERNAME` — no schema modification capability.
- **Migration** (`app_migrator`): used only by `php artisan migrate` during release pipeline. Credentials stored in CI/CD secrets, never in application `.env`.
- Neither role is `root` or `postgres`.
- Backup encryption: AES-256.

### 6.8 Permanent Audit Trail
- `spatie/laravel-activitylog` ^4.8 for all critical changes.
- Financial transactions and role activities permanently logged.
- `activity_log` table is never deleted.
- `AuditLog` middleware for automatic logging.

### 6.9 Health Check Design

(Source: spec section 3, HealthCheckController)

Two endpoints with different access levels to avoid exposing operational details publicly:

**Public liveness** — `GET /health/live` (no authentication):
- Returns only `{"status": "ok"}` (200) or `{"status": "degraded"}` (503).
- Checks database connectivity only (`DB::connection()->getPdo()`).
- Suitable for uptime monitors and load balancer probes.
- No internal details (queue depth, storage %, failure counts) exposed.

**Authenticated readiness/diagnostics** — `GET /health` (requires `super_admin` role or IP allowlist):

| Check | Method | Warning | Error |
|-------|--------|---------|-------|
| **Database** | `DB::connection()->getPdo()` + response time | — | Connection fails |
| **Storage** | `disk_free_space()` / `disk_total_space()` | Usage >= 90% | — |
| **Queue** | Count `jobs` + `failed_jobs` tables (database driver) | failed_jobs > 10 | failed_jobs > 50 OR pending_jobs > 100 |
| **Cache** | Write `health_check = true` (TTL 10s), read back | — | Read fails |

Overall status: any `error` → `degraded` (503); any `warning` → `warning` (503); otherwise `ok` (200).

The internal cron health check (every 5 minutes) hits the authenticated endpoint using a server-side token or localhost allowlist.

## 7. Deployment & Operations Design

### 7.1 Infrastructure Requirements

(Source: spec section 8.1–8.2)

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| OS | Ubuntu 22.04 LTS | Ubuntu 22.04 LTS |
| Web Server | Nginx 1.22+ | Nginx 1.22+ |
| PHP | 8.2 with FPM | 8.2+ with FPM |
| Database | PostgreSQL 15 | PostgreSQL 15+ |
| RAM | 2 GB | 4 GB |
| Storage | 50 GB (20 GB app + 30 GB backups) | 50 GB+ |
| SSL | Let's Encrypt | Let's Encrypt or commercial |
| Node.js | 20.x (asset build) | 20.x |

PHP extensions: fpm, pgsql, mbstring, xml, bcmath, curl, gd, zip, intl.

### 7.2 Environment Model

(Source: spec Environment Separation Strategy)

| Environment | Purpose | Data | Controls |
|-------------|---------|------|----------|
| **Local** | Development, debugging | Fake/seeded | Mock services, `QUEUE_CONNECTION=sync` |
| **Staging** | UAT, pre-production testing | Anonymized production copy | `QUEUE_CONNECTION=database`, sandbox WhatsApp |
| **Production** | Live operations | Real data | `APP_DEBUG=false`, `FORCE_HTTPS=true`, `QUEUE_CONNECTION=database` |

Each environment has its own database, storage, API keys, queue workers, and domain.

### 7.3 Deployment Governance
- No direct production deployment — all changes must pass staging + UAT first.
- No direct migration execution on production console.
- `.env` files not committed to Git; `.env.example` as template.
- Pre-deployment backup mandatory.
- Deployment flow: feature branch → PR → staging → UAT sign-off → main → production.

### 7.4 Backup & Retention

(Source: spec section 8.6)

| Policy | Value |
|--------|-------|
| Encryption | AES-256 (`BACKUP_ENCRYPTION_PASSWORD` env) |
| Schedule | Daily at 02:00 |
| Keep all backups | 7 days |
| Keep daily backups | 16 days |
| Keep weekly backups | 8 weeks |
| Keep monthly backups | 4 months |
| Keep yearly backups | 2 years |
| Max storage | 5000 MB (5 GB) |
| Health check: max age | 1 day |
| Health check: max storage | 5000 MB |
| Alert | Email on backup failure/unhealthy |

Package: `spatie/laravel-backup` ^9.0.

### 7.5 Disaster Recovery

(Source: spec section 8.7)

Three recovery scenarios:

| Scenario | Time Estimate | RTO | RPO |
|----------|--------------|-----|-----|
| **Database corruption** | 30–60 min | < 1 hour | < 24 hours |
| **Server crash / Data center outage** | 2–4 hours | Activate DR site if > 4 hours | < 24 hours |
| **Ransomware / Security breach** | 1–3 days | — | Restore from clean pre-breach backup |

Ransomware procedure: isolate server immediately (iptables DROP all), preserve evidence, assess scope, notify management, restore from clean backup, change all passwords, rotate all API keys, update SSH keys.

Recovery testing schedule:
- **Monthly:** backup restore test on staging, integrity verification.
- **Quarterly:** full DR drill, database corruption simulation.
- **Annually:** emergency contact review, security audit, RTO/RPO target review.

## 8. Non-Functional Design Targets

(Source: spec sections 7, 8, 9)

### 8.1 Reliability
- Financial transactions are immutable with database-level enforcement.
- Operations are recoverable via encrypted backups with tiered retention.
- Health check monitors 4 system components every 5 minutes.

### 8.2 Performance
- Database indexing: composite, partial, and trigram indexes on frequent query paths.
- Application caching: dashboard data, public settings (1-hour TTL).
- Queue-based background jobs for notifications (2 Supervisor workers).
- Load target: 100 concurrent transaction requests with zero duplicate numbers.

### 8.3 Security
- Least-privilege access: 5 roles with granular permissions.
- Immutable audit evidence: `activity_log` is never deleted.
- Defense in depth: application validation + database constraints + triggers.
- Rate limiting at 3 tiers (login, API, transaction).

### 8.4 Testability

(Source: spec section 7)

| Target | Coverage |
|--------|---------|
| Overall minimum | 60% |
| Services (TransactionService, ArrearsService, ReceiptService) | 80%+ |
| Controllers | 60%+ |
| Models | 50%+ |
| Commands (cron jobs) | 70%+ |
| Static analysis (PHPStan) | Level 5+ |
| Load test | 100 concurrent requests, no duplicates |

Tools: PHPUnit, Laravel Dusk, Pest (optional), PHPStan.
CI/CD: run tests on every commit, block merge on failure, generate coverage report.

### 8.5 Maintainability
- Modular services with single responsibility (5 service classes).
- Phased rollout with release gates.
- Configuration-as-code via database settings with admin UI.
- Documented DR runbook with periodic drills.

## 9. Release Gates

### 9.1 Core Go-Live Gate (End of Week 8)
- Master data CRUD validated (classes, categories, fee types, fee matrix, students).
- Income/expense transactions stable: concurrency-safe numbering confirmed, immutability enforced.
- PDF receipts operational: correct school profile, terbilang accurate, reprint working.
- Core reports usable: daily, monthly, arrears with PDF/Excel export.
- Student obligations auto-generated via cron command.
- Core UAT approved by school representatives (Bendahara/TU).

### 9.2 Enhancement Release Gate (End of Week 12)
- Dashboard renders accurate summary data with charts and caching.
- WhatsApp notifications: payment confirmation and arrears reminders sent with status logging.
- User management: role assignment, escalation protection, audit log viewer functional.
- Settings: school profile, receipt customization, WhatsApp config editable via admin UI.
- Backup: manual trigger and scheduled automation validated.
- Monitoring: health check endpoint returns correct status for all 4 components.
- All above validated on staging before production release.

## 10. Assumptions
- **[Assumption]** Historical pre-system transactions are not migrated; operations start with opening balance entry.
- **[Assumption]** SLA details (response time, support hours, severity matrix) are defined in a separate support contract.
- **[Assumption]** Project implementation roles (PM, Architect, Developer, QA, DevOps, Trainer) are staffed as described in `proposal.md` section 10.2.
