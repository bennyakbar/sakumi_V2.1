# Project Proposal
## Development of a Web-Based School Financial Management System (MI/SD)

### 1. Executive Summary

This proposal outlines the development of a **web-based School Financial Management System (MI/SD)** to digitize end-to-end school finance operations. The solution is fully based on `openspec/SISTEM_KEUANGAN_SEKOLAH.md` (latest version: 2.1 — Production-Ready Enhancement, February 10, 2026).

Key value offered:
- **Financial transparency and accountability** through permanent audit trails and the financial immutability principle — transactions cannot be edited or deleted; corrections use a cancellation + replacement transaction workflow.
- **Operational efficiency** for treasurer and administration through automated concurrency-safe transaction numbering, PDF receipts, and structured reporting.
- **Stronger arrears control** through automatic monthly obligation generation and scheduled WhatsApp reminders.
- **Data reliability** through AES-256 encrypted daily backups, tiered retention policy, and a disaster recovery runbook covering 3 recovery scenarios.
- **Institutional readiness** through a modern technology stack (Laravel 11.x + PostgreSQL 15+), layered security, and a scalable architecture.

**Technology stack** (per source spec section 1.1):
- **Backend:** Laravel 11.x LTS, PHP 8.2+
- **Database:** PostgreSQL 15+ (primary), MySQL 8.0+ (acceptable alternative)
- **Frontend:** Blade Templates, Tailwind CSS 3.x, Alpine.js 3.x, Chart.js 4.x
- **Key packages:** `spatie/laravel-permission` ^6.0, `barryvdh/laravel-dompdf` ^3.0, `maatwebsite/excel` ^3.1, `spatie/laravel-backup` ^9.0, `spatie/laravel-activitylog` ^4.8, `laravel/breeze` ^2.0

### 2. Background & Problems in Current School Finance Management

Common school finance management challenges addressed by this system:

| # | Problem | Impact |
|---|---------|--------|
| 1 | Transactions recorded manually or across disconnected tools (ledger books, spreadsheets) | Input errors, data duplication, reconciliation issues |
| 2 | Arrears monitoring is not real-time | Delayed follow-up, unpredictable cash flow |
| 3 | Receipt and report preparation is manual | Time-consuming, inconsistent format, prone to duplication |
| 4 | User access controls are not role-structured | Privilege misuse risk, no segregation of duties |
| 5 | Audit trails are insufficient | Difficult to account to foundation/board |
| 6 | No formal disaster recovery procedures | Risk of data loss without recovery mechanism |
| 7 | Fee rates differ by class and student category, difficult to manage manually | Fee errors, billing inconsistency |

**[Assumption]** The current-state points above are derived from common school practices and the system goals in the source document, as each school's baseline process is not detailed explicitly.

### 3. Proposed Solution Overview

The proposed solution is an integrated school finance web application covering:
- **Master data management** for students, classes, categories, fee types, and a dynamic fee matrix supporting rate determination by fee type + class + student category + effective period (source: section 2.1, `fee_matrix` table).
- **Income/expense transaction recording** with concurrency-safe numbering using pessimistic locking (`lockForUpdate()`) to prevent duplicate numbers during simultaneous access (source: section 5, TransactionService).
- **Automated PDF receipts** with school-branded templates, Indonesian number-to-words (terbilang), and controlled reprint (source: section 5, ReceiptService).
- **Automatic monthly student obligations** generated via cron job on the 1st of every month, with payment status tracking per student (source: section 5, ArrearsService; section 8.3 cron schedule).
- **WhatsApp notifications** for payment confirmations (real-time via event) and arrears reminders (scheduled every Monday at 09:00) (source: section 5, WhatsAppService; section 8.3 cron schedule).
- **Comprehensive reporting** — daily, monthly, and arrears reports with PDF/Excel export and Chart.js visualization (source: section 5, ReportService).
- **Dashboard** — summary of income, expenses, balance, arrears count, and 6-month trend (source: section 9, Week 9 roadmap).
- **Multi-role access control** with 5 roles, role-escalation protection, and permanent audit logging (source: section 4).
- **System health check** — public `GET /health` endpoint monitoring database, storage, queue, and cache status (source: section 3, HealthCheckController).

### 4. Key Features & Modules

#### 4.1 Master Data Module
- **Classes** — CRUD with academic year, level 1–6, active/inactive status.
- **Student Categories** — Reguler, Yatim, Dhuafa, etc. with discount percentage per category.
- **Fee Types** — SPP (monthly), Daftar Ulang, Seragam, etc. with monthly flag.
- **Fee Matrix (Dynamic Pricing)** — Rate determination by fee type + class + student category + effective date range. Uses a specificity-based priority: a rate specific to "Kelas 1A + Reguler" takes precedence over a generic "all classes + all categories" rate. Supports fee changes across periods without deleting historical records (source: section 2.1, `fee_matrix` table; section 5, `getFeeMatrix()` method).
- **Students** — Full CRUD with NIS/NISN, search, filter, soft delete, and **Excel import/export** for bulk data onboarding (source: section 3, student API; section 9, Week 3–4).

#### 4.2 Transaction Module
- Income and expense recording with line-item detail (multi-item per transaction).
- **Concurrency-safe auto-numbering:** format `NF-{YYYY}-{NNNNNN}` (income) and `NK-{YYYY}-{NNNNNN}` (expense), using pessimistic locking inside a database transaction (source: section 5, `generateTransactionNumber()`).
- **Financial immutability:** completed transactions cannot be edited or deleted. Protected by PostgreSQL trigger (`prevent_transaction_update()`) preventing changes to `total_amount`, `transaction_date`, and `student_id` (source: section 6.0).
- Corrections via **cancellation** (status → `cancelled`) + new replacement transaction. Both remain in the database.
- Business rule validation: active student, fee per matrix, valid amount.

#### 4.3 Receipt Module
- Auto-generated PDF after successful income transaction.
- Template with school identity (name, address, logo), payment details, and **terbilang** (number-to-words in Indonesian) (source: section 5, ReceiptService).
- Cancelled transaction receipts display "DIBATALKAN" watermark.
- Controlled reprint with logging.

#### 4.4 Obligation & Arrears Module
- **Automatic obligation generation** via cron job (1st of every month at 00:00) — creates monthly obligation records for each active student based on the applicable fee matrix (source: section 5, `generateMonthlyObligations()`; section 8.3 cron `0 0 1 * *`).
- Automatic payment status update when income transaction is processed.
- Arrears recap per student, per class, and per period.

#### 4.5 Notification Module (WhatsApp)
- **Payment confirmation** — sent automatically after successful income transaction via `TransactionCreated` event (source: section 5, WhatsAppService).
- **Arrears reminder** — sent every Monday at 09:00 via cron job `arrears:remind`, for students with arrears > 1 month overdue (source: section 8.3 cron `0 9 * * 1`; Settings `arrears_threshold_months` default: 1).
- Customizable message templates via admin UI (placeholders: `{student_name}`, `{fee_type}`, `{amount}`) (source: section 6.7, Configuration-as-Code, `notification_payment_template` setting).
- Delivery status logging (pending/sent/failed) in `notifications` table with gateway response (source: section 2.1, `notifications` table).
- Manual retry for failed notifications (source: section 9, Week 10).
- Parent WhatsApp number validation (Indonesian format: `628xxxxxxxxx`) (source: section 5, WhatsAppService).

#### 4.6 Reporting Module
- **Daily report** — income & expense summary per day with breakdown by type.
- **Monthly report** — monthly summary with daily statistics and chart visualization.
- **Arrears report** — by class, by month, with student-level detail.
- Export to **PDF and Excel** (source: section 5, ReportService; Exports classes).
- Chart data for dashboard (Chart.js).

#### 4.7 Dashboard Module
- Summary cards: total income, expenses, balance, and arrears student count.
- Bar chart of 6-month income trend.
- Pie chart of income breakdown by fee type.
- Recent transactions widget.
- Performance caching for optimal response time (source: section 9, Week 9).

#### 4.8 User & Access Module
- User CRUD with role assignment and active/inactive status.
- **5 system roles** with granular permissions (detail in section 6).
- **Role escalation protection:** only Super Admin can manage sensitive roles. Users cannot change their own role or deactivate themselves. All role changes are permanently logged. Email alert sent when Super Admin role is assigned (source: section 4, Role Escalation Protection).
- Audit log viewer for user activity review.
- Menu visibility by role.

#### 4.9 Settings & Backup Module
- School profile (name, address, phone, logo) — editable via admin UI.
- Receipt customization (footer text, show logo).
- WhatsApp gateway configuration (URL, API key, enable/disable).
- **Configuration-as-Code** — all operational settings stored in database `settings` table with type casting (string, number, boolean, json), 1-hour cache for public settings, and global helper functions `getSetting()` / `setSetting()` (source: section 6.7, Configuration-as-Code).
- Backup management — manual backup trigger, backup history view.

#### 4.10 System Health Check
- Public endpoint `GET /health` (no authentication) for integration with monitoring tools (UptimeRobot, Pingdom, etc.).
- Checks 4 components: database (connectivity + response time), storage (usage percentage), queue (pending/failed jobs), cache (read/write test).
- Response status: `ok` (200), `warning` (503 — storage > 90%, failed jobs > 10), `degraded` (503 — component error) (source: section 3, HealthCheckController).
- Automated health check via cron every 5 minutes with email alert on failure (source: section 8.3, `*/5 * * * * curl -f ... || mail`).

### 5. System Architecture Overview

#### 5.1 Application Architecture (Layered)

```
┌──────────────────────────────────────────────┐
│              Client Layer                     │
│  Browser (Chrome, Firefox, Safari, Edge)     │
└────────────────┬─────────────────────────────┘
                 │ HTTPS / TLS 1.3
                 ▼
┌──────────────────────────────────────────────┐
│         Presentation Layer                    │
│  Blade Templates + Tailwind CSS + Alpine.js  │
└────────────────┬─────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────┐
│         Application Layer                     │
│  Controllers → Services → Events/Listeners   │
│  Middleware → Form Requests → Resources      │
└────────────────┬─────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────┐
│         Data Access Layer                     │
│  Eloquent ORM → Query Builder → Migrations   │
└────────────────┬─────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────┐
│         Database Layer                        │
│  PostgreSQL 15+ (ACID Compliant)             │
└──────────────────────────────────────────────┘

External Integrations:
├─ WhatsApp Gateway API (Notifications)
├─ Storage Layer (Local/S3 for PDF & Backups)
└─ Email Service (SMTP/Mailgun for alerts)
```

(Source: section 1.2–1.3)

#### 5.2 Data Model

The system uses **14+ tables** designed for data integrity and auditability (source: section 2.1):

| Table | Purpose | Delete Policy |
|-------|---------|---------------|
| `users` | Authentication & user roles | Soft delete (`is_active = false`) |
| `roles` / `permissions` | Spatie role & permission definitions | — |
| `classes` | Classes (1–6) per academic year | Soft delete (`is_active = false`) |
| `student_categories` | Student categories with discount percentage | Mark inactive |
| `students` | Student master data (NIS, NISN, class, category) | Soft delete (`status = 'graduated'`) |
| `fee_types` | Fee types (SPP, Daftar Ulang, Seragam) | Soft delete (reactivatable) |
| `fee_matrix` | Dynamic pricing matrix per combination | Soft delete (`is_active = false`) |
| `transactions` | Income/expense transactions (immutable) | **Cannot be deleted** (`status = 'cancelled'`) |
| `transaction_items` | Line items per transaction | **Cannot be deleted** (follows parent) |
| `student_obligations` | Monthly payment obligations per student | **Cannot be deleted** (`is_paid` flag) |
| `notifications` | WhatsApp notification log & delivery status | Auto-delete after 6 months |
| `activity_log` | Permanent audit trail (Spatie) | **Never deleted** |
| `settings` | System configuration (key-value store) | — |

Key design patterns:
- **Financial immutability** enforced at database level (PostgreSQL trigger).
- **Concurrency-safe numbering** with pessimistic locking.
- **Comprehensive indexing strategy** including partial indexes and trigram search for performant lookups.
- **Database-level constraints** (CHECK, UNIQUE, FK) as last line of defense above application validation.

#### 5.3 Environment Separation

Three isolated environments (source: Environment Separation Strategy section):

| Environment | Purpose | Access | Data |
|-------------|---------|--------|------|
| **Local** | Development & debugging | Developers | Fake/seeded data |
| **Staging** | Pre-production testing & UAT | Developers, Testers, Client | Anonymized production copy |
| **Production** | Live system | End users, Support team | Real data |

Critical rules:
- No direct production deployment — all changes must pass staging + UAT first.
- Each environment has separate database, storage, API keys, queue workers, and domain.
- No direct migration execution on production console.
- `.env` files must not be committed to Git.

#### 5.4 Scheduled Automation (Cron Jobs)

(Source: section 8.3)

| Schedule | Command | Function |
|----------|---------|----------|
| 1st of every month, 00:00 | `obligations:generate` | Generate monthly student obligations |
| Every Monday, 09:00 | `arrears:remind` | Send arrears reminders via WhatsApp |
| Every day, 02:00 | `backup:run` | Encrypted daily backup |
| Every 5 minutes | Health check cron | System status monitoring & email alert |

#### 5.5 Infrastructure Requirements

(Source: section 8.1–8.2)

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| **OS** | Ubuntu 22.04 LTS | Ubuntu 22.04 LTS |
| **Web Server** | Nginx 1.22+ | Nginx 1.22+ |
| **PHP** | 8.2 | 8.2+ with FPM |
| **Database** | PostgreSQL 15 | PostgreSQL 15+ |
| **RAM** | 2 GB | 4 GB |
| **Storage** | 50 GB (20 GB app + 30 GB backups) | 50 GB+ |
| **SSL** | Let's Encrypt (free) | Let's Encrypt or commercial |
| **Node.js** | 20.x (for asset build) | 20.x |

PHP extensions required: fpm, pgsql, mbstring, xml, bcmath, curl, gd, zip, intl.

Queue workers: Supervisor with **2 worker processes** (`--sleep=3 --tries=3 --max-time=3600`, auto-start, auto-restart) (source: section 8.4).

### 6. Security & Data Protection Approach

#### 6.1 Role-Based Access Control (RBAC)

(Source: section 4)

| Role | Primary Access |
|------|---------------|
| **Super Admin** | Full system access, sensitive user/role management |
| **Bendahara** (Treasurer) | Dashboard, students, transactions, reports, receipts, fee matrix |
| **Kepala Sekolah** (Principal) | Dashboard, view-only students/transactions/reports |
| **Operator TU** (Admin Staff) | Dashboard, students, classes, view transactions/reports |
| **Auditor** | View-only all data, audit log access |

#### 6.2 Role Escalation Protection
- Only Super Admin can manage sensitive roles.
- Users cannot change their own role or deactivate themselves.
- All role changes permanently logged in audit trail.
- Email alert sent when Super Admin role is assigned.

(Source: section 4, Role Escalation Protection)

#### 6.3 Financial Immutability
- Transactions cannot be edited after creation, cannot be physically deleted.
- Corrections: cancel original (status → `cancelled`) then create replacement. Both remain in database.
- Protected by PostgreSQL trigger `prevent_transaction_update()`.

(Source: section 6.0)

#### 6.4 Technical Security

(Source: section 6.1–6.6)

| Aspect | Detail |
|--------|--------|
| **Input Validation** | HTML tag stripping, parameterized queries (Eloquent), CSRF protection, Blade escaping (`{{ }}`) |
| **Password Policy** | Min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special character. Reset token expires 60 min. |
| **Rate Limiting** | Login: 5 attempts / 15 min. API: 100 requests / min. Transactions: 10 requests / min. |
| **Session Security** | 2-hour lifetime, encrypted, HTTP-only, secure (HTTPS only), same-site lax, auto-logout on inactivity. |
| **File Upload** | MIME validation (jpg, jpeg, png, pdf), max 2 MB, directory traversal prevention. |
| **Database Security** | Dedicated DB user (not root), only SELECT/INSERT/UPDATE/DELETE grants. |
| **Audit Trail** | Permanent, never deleted. Logs all critical changes and role activities. |

#### 6.5 Backup & Retention

(Source: section 8.6)

| Policy | Value |
|--------|-------|
| Encryption | AES-256 |
| Schedule | Daily at 02:00 |
| Keep all backups | 7 days |
| Keep daily backups | 16 days |
| Keep weekly backups | 8 weeks |
| Keep monthly backups | 4 months |
| Keep yearly backups | 2 years |
| Storage limit | 5 GB (delete oldest when exceeded) |
| Backup health check | Max age 1 day, max storage 5 GB |
| Alert | Email notification if backup fails/unhealthy |

#### 6.6 Disaster Recovery Runbook

Three recovery scenarios with different time targets (source: section 8.7):

| Scenario | Time Estimate | RTO | RPO |
|----------|--------------|-----|-----|
| **Database corruption** | 30–60 min | < 1 hour | < 24 hours (daily backups) |
| **Server crash / Data center outage** | 2–4 hours | Activate DR site if outage > 4 hours | < 24 hours |
| **Ransomware / Security breach** | 1–3 days | — | Restore from clean backup before breach |

Ransomware procedure: immediate server isolation, evidence preservation, scope assessment, management notification, restore from clean backup, change all passwords, rotate all API keys, update SSH keys.

Recovery testing schedule (source: section 8.7):
- **Monthly:** Backup restore test on staging, integrity verification, storage capacity check.
- **Quarterly:** Full disaster recovery drill, database corruption simulation, failover procedure test.
- **Annually:** Emergency contact review, security audit, RTO/RPO target review.

### 7. Implementation Methodology (Phases)

Implementation follows phased delivery over **20 weeks** (source: section 9).

**Selected build strategy:** **Option 2 - Core-first phased rollout**.
- **Wave 1 (Core Go-Live):** release essential finance operations first after core UAT sign-off.
- **Wave 2 (Enhancement):** release advanced capabilities after production stabilization of core modules.

#### Phase 1: Foundation (Weeks 1–2)

| Week | Activities | Acceptance Criteria |
|------|-----------|-------------------|
| **1** | Laravel 11 installation & Git setup; database schema & migrations; seeders for roles/permissions/settings; authentication (Laravel Breeze); main layout (sidebar, navbar, footer); Tailwind configuration | Migrations run successfully; login/logout functional; responsive layout renders |
| **2** | Role-based middleware; base controller & service structure; form request validation; error handling & logging; activity log integration | RBAC middleware functional per role; audit log records activity |

#### Phase 2: Core Features (Weeks 3–8)

| Week | Activities | Acceptance Criteria |
|------|-----------|-------------------|
| **3–4** | CRUD for classes, student categories, fee types; fee matrix management (grid UI, effective dates); student CRUD (search, filter, soft delete); **student import/export via Excel**; validation & error handling | Master data CRUD complete; Excel import of 100+ students succeeds; fee matrix lookup accurate per combination |
| **5–6** | Auto transaction numbering; income transaction (student selection, fee selection, multi-item); expense transaction (file upload, categorization); transaction list (filter, search, pagination); detail & cancellation; business rule validation | Concurrency-safe transactions (no duplicate numbers); immutability enforced; cancellation reverts obligations |
| **7** | PDF receipt generation; receipt template (Blade); terbilang helper; download & reprint; automatic obligation update; monthly obligation generation command | PDF receipt renders correctly; terbilang accurate; obligations auto-generated |
| **8** | Daily report (summary, breakdown, export); monthly report (summary, charts, export); arrears report (by class, by month); Chart.js integration; PDF/Excel export | Reports accurate & match transaction data; export functional |

**Core Go-Live Gate (end of Week 8):**
- Master data ready and validated.
- Income/expense transactions stable and auditable.
- PDF receipts and core reports operational.
- Core UAT approved by school representatives.

#### Phase 3: Enhancement (Weeks 9–12)

| Week | Activities | Acceptance Criteria |
|------|-----------|-------------------|
| **9** | Dashboard (summary cards, 6-month bar chart, pie chart breakdown, recent transactions); performance optimization (caching) | Dashboard data accurate; caching functional |
| **10** | **WhatsApp integration** — gateway research & setup; WhatsAppService implementation; payment confirmation notification; arrears reminder notification; notification log & status tracking; manual retry for failed notifications | Notifications sent; status recorded; retry functional |
| **11** | User CRUD (assign role, deactivate); password reset; audit log viewer; permission testing; menu visibility by role | RBAC enforcement complete; audit log viewer functional |
| **12** | School profile settings; logo upload; receipt customization; WhatsApp configuration via UI; backup management; manual backup trigger | Settings saved & applied; manual backup succeeds |

**Enhancement Release Gate (end of Week 12):**
- Dashboard, WhatsApp notifications, user management, and advanced settings are production-ready.
- Monitoring, backup, and access-control checks validated on staging.

#### Phase 4: Testing & Deployment (Weeks 13–16)

Testing targets (source: section 7):
- Overall coverage: **minimum 60%**
- Services (TransactionService, ArrearsService, ReceiptService): **80%+**
- Controllers: **60%+**
- Models: **50%+**
- Commands (cron jobs): **70%+**
- Static analysis (PHPStan): level **5+**
- Load test: **100 concurrent requests** with no duplicate transaction numbers
- Tools: PHPUnit, Laravel Dusk, Pest (optional), PHPStan

| Week | Activities | Acceptance Criteria |
|------|-----------|-------------------|
| **13–14** | Unit tests (services, models); feature tests (controllers, API); browser tests (Dusk); manual testing per role; edge case testing; performance testing; security audit | Coverage >= 60% overall, >= 80% services; load test 100 concurrent OK |
| **15** | Bug fixing (critical, high, medium); code optimization; database indexing; query optimization; production environment setup; SSL configuration; backup automation | Zero critical bugs; production environment ready |
| **16** | **Production deployment**; smoke testing; user manual creation; video tutorial recording; user training sessions; UAT with client; **initial data migration** (import existing students via Excel); go-live; post-launch monitoring | UAT sign-off; go-live successful; users trained |

CI/CD integration (source: section 7): run tests on every commit, block merge if tests fail, generate coverage report.

#### Phase 5: Post-Launch Support (Weeks 17–20)

| Week | Activities |
|------|-----------|
| **17–18** | Intensive monitoring; priority bug fixing; performance fine-tuning |
| **19–20** | Bug fixes & minor enhancements; user feedback collection; documentation updates |

### 8. Project Timeline (Estimated)

Summary (source: section 9):
- **Core implementation:** 16 weeks.
- **Post-go-live stabilization:** 4 weeks.
- **Total initial program:** 20 weeks.

| Period | Phase |
|--------|-------|
| Weeks 1–2 | Foundation |
| Weeks 3–8 | Core operational features (**Wave 1 Core Go-Live**) |
| Weeks 9–12 | Functional enhancements (**Wave 2 Enhancement Release**) |
| Weeks 13–16 | Testing, deployment, training, UAT, go-live |
| Weeks 17–20 | Hypercare and early optimization |

### 9. Deliverables

| # | Deliverable | Phase |
|---|------------|-------|
| 1 | Production-ready school finance web application | 1–4 |
| 2 | Full module set within approved scope (10 modules) | 2–3 |
| 3 | Database schema, migrations, and seeders (roles, permissions, settings) | 1 |
| 4 | Local/staging/production environment configurations | 1, 4 |
| 5 | Encrypted backup mechanism (AES-256) with scheduled automation | 3–4 |
| 6 | PDF receipt template with school identity | 2 |
| 7 | WhatsApp gateway integration (payment confirmation & arrears reminders) | 3 |
| 8 | Deployment, monitoring, and disaster recovery documentation | 4 |
| 9 | User documentation (manuals) and training materials (documents + video tutorials) | 4 |
| 10 | UAT report, testing results, and go-live checklist | 4 |
| 11 | Source code with test suite (target coverage >= 60%) | 1–4 |

**Rollout note (Option 2):**
- **Wave 1 Core Deliverables:** production-ready core transactions, receipts, and core reporting stack.
- **Wave 2 Enhancement Deliverables:** dashboard, WhatsApp automation, advanced user/settings/backup management.

**Data migration & onboarding plan:**
- **Student data import** — bulk import via Excel using a standard template provided by the system. Supports fields: NIS, NISN, name, class, category, parent contact (source: section 3, student API; section 9, Week 3–4; `maatwebsite/excel` package).
- **Master data setup** — class configuration per academic year, student categories, fee types, and fee matrix configuration.
- **Data verification** — automatic validation during import (NIS format, duplicates, required fields) with error reporting.
- **[Assumption]** Historical transactions before system implementation will not be migrated (clean start). Opening balance will be entered as an opening transaction.

### 10. Roles & Responsibilities

#### 10.1 System Roles (Operational)

(Source: section 4)

| Role | Responsibility |
|------|---------------|
| **Super Admin** | Full system control, sensitive user/role management, system configuration |
| **Bendahara** (Treasurer) | Daily financial transaction operations, receipt printing, reports |
| **Kepala Sekolah** (Principal) | Dashboard and report oversight, policy approval |
| **Operator TU** (Admin Staff) | Student and class master data management, operational support |
| **Auditor** | Read-only review of all data, audit log review |

#### 10.2 Project Roles (Implementation)

| Role | Responsibility |
|------|---------------|
| Project Manager | Timeline coordination, stakeholder communication, risk management |
| Solution Architect | Architecture design, technical review, technology decisions |
| Backend Developer | Implementation of all modules, API, business logic |
| QA/Tester | Testing (unit, feature, browser, manual), bug reporting |
| DevOps | Server setup, deployment, monitoring, backup, CI/CD |
| Trainer | Manual creation, video tutorials, training sessions |

**[Assumption]** The school provides designated representatives (Treasurer/TU/Principal) for requirement validation, UAT, and sign-off.

### 11. Risks & Mitigation

| # | Risk | Impact | Mitigation |
|---|------|--------|------------|
| 1 | Direct, uncontrolled production changes | Data corruption, downtime | Mandatory staging + UAT + deployment checklist; environment isolation (source: Environment Separation Strategy) |
| 2 | Data loss / server incidents | Operations halted | AES-256 encrypted daily backups; tiered retention; DR runbook with 3 scenarios; quarterly drills (source: section 8.6–8.7) |
| 3 | Privilege misuse | Financial data manipulation | RBAC with 5 roles; role escalation protection; permanent audit logs; email alerts (source: section 4) |
| 4 | Incorrect transaction correction | Financial data inconsistency | Financial immutability + database trigger; cancellation + replacement workflow (source: section 6.0) |
| 5 | Increased operational load | Performance degradation | Indexing strategy; caching; 2 queue workers; health check every 5 min; alerting (source: sections 2.1, 8.3–8.5) |
| 6 | Slow user adoption | System underutilized | Structured training; manuals + video tutorials; 4-week post-go-live guidance (source: section 9, Weeks 16–20) |
| 7 | WhatsApp gateway instability | Notifications fail to send | Delivery status logging; manual retry; notification status tracking in database (source: section 5, WhatsAppService; section 2.1, `notifications` table) |
| 8 | Duplicate transaction numbers during concurrent access | Data inconsistency | Pessimistic locking (`lockForUpdate()`); load test with 100 concurrent requests (source: section 5, TransactionService; Implementation Checklist) |

### 12. Maintenance & Support Plan

#### 12.1 Hypercare Period (Weeks 17–20)
- Intensive monitoring of application, web server, database, and queue logs.
- Priority bug fixing.
- Minor enhancements based on user feedback.

**[Assumption]** Critical/high priority bugs will be addressed within 24 working hours during the hypercare period.

#### 12.2 Routine Maintenance (Post-Hypercare)
- Periodic application and server log monitoring.
- Backup validation and restore testing (monthly) (source: section 8.7, Recovery Testing Schedule).
- Security review and user activity audit (quarterly) (source: section 8.7, Recovery Testing Schedule).
- Incident handling procedures per DR runbook (source: section 8.7).
- Dependency updates (security patches) as needed.

**[Assumption]** Detailed SLA terms (service hours, response time, severity matrix) will be defined in a separate operational support contract.

### 13. Future Scalability & Roadmap

Based on the documented architecture and roadmap (source: section 9):
- **Quality hardening** via minimum test coverage targets and automated testing in CI/CD pipeline (source: section 7).
- **Continuous performance tuning** — indexing, query optimization, caching strategy (source: section 9, Week 15).
- **Stronger observability** using health check endpoint for uptime monitoring integration (source: section 3, HealthCheckController; section 8.5).
- **Better governance** through configuration-as-code via centralized database settings (source: section 6.7).
- **Ongoing release readiness** across local/staging/production environments (source: Environment Separation Strategy).

**[Assumption]** The same architecture and deployment model can be replicated for other school/madrasah units under the same foundation for phased multi-unit expansion.

### 14. Conclusion

This project provides a strong foundation for schools/foundations to manage finance with higher transparency, accountability, and efficiency. With **10 complete modules**, a modern architecture (**Laravel 11.x + PostgreSQL 15+**), layered security controls (5-role RBAC, financial immutability, permanent audit trail), operational automation (monthly obligations, WhatsApp notifications, encrypted backups), and a phased implementation model over **20 weeks** including post-go-live support — this solution is fit to serve as a modern, secure, and scalable financial management platform for MI/SD institutions.

This proposal is ready to be converted to PDF and presented to school leadership and foundation boards.

---

## Appendices

### Appendix A: Investment Estimate [Assumption]

**[Assumption]** The following cost components are not sourced from the technical specification. They are provided as a framing reference for budgeting discussions.

| Component | Description |
|-----------|------------|
| **Application development** | Development cost for 16 weeks (Phase 1–4) |
| **Server infrastructure** | VPS/cloud hosting (2–4 GB RAM, 50 GB storage), domain, SSL |
| **WhatsApp gateway** | Third-party WhatsApp Business API subscription |
| **Training & documentation** | Manual creation, video tutorials, training sessions |
| **Hypercare & support** | 4-week post-go-live support (Phase 5) |
| **Annual maintenance** | Monitoring, updates, backup management, security patches (optional) |

Detailed cost breakdown per component will be provided in a separate quotation document after specific requirements are confirmed.

### Appendix B: Suggested Next Steps [Assumption]

**[Assumption]** The following next steps are not sourced from the technical specification. They are provided as a practical implementation guide.

1. **Proposal presentation & discussion** with school leadership and foundation board.
2. **Scope and requirements confirmation** — validate required modules, feature priorities, and data to be migrated.
3. **Quotation preparation and approval** based on agreed scope.
4. **Designation of school representatives** — Treasurer, Admin Staff, and Principal for requirement validation, UAT, and sign-off.
5. **Project kick-off** — environment setup, repository access, and start Phase 1 (Foundation).
