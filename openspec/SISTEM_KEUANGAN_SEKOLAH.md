# DOKUMENTASI TEKNIS SISTEM KEUANGAN SEKOLAH (REFINED VERSION)

**Nama Proyek:** Aplikasi Sistem Keuangan Madrasah Ibtidaiyah (MI)  
**Stack:** Laravel 11.x + PostgreSQL + Tailwind CSS  
**Version:** 2.0 (Refined)  
**Status:** Production Ready  
**Last Updated:** February 9, 2026

---

## EXECUTIVE SUMMARY

Sistem manajemen keuangan sekolah berbasis web untuk mendigitalisasi pencatatan transaksi, tracking tunggakan, laporan keuangan, dan generate kwitansi otomatis. Dokumen ini mencakup spesifikasi teknis lengkap, API endpoints, security requirements, testing strategy, dan deployment guide.

**Key Features:**
- ✅ Multi-role access (Super Admin, Bendahara, Kepala Sekolah, TU, Auditor)
- ✅ Dynamic fee matrix (tarif per kelas & kategori)
- ✅ Auto-generate PDF receipts (kwitansi)
- ✅ Auto-tracking tunggakan dengan reminder WhatsApp
- ✅ Comprehensive reporting (daily, monthly, yearly)
- ✅ Full audit trail
- ✅ Automated backup
- ✅ Financial immutability (transaction cannot be edited/deleted)
- ✅ Concurrency-safe transaction numbering
- ✅ Multi-environment deployment strategy

---

## ENVIRONMENT SEPARATION STRATEGY

### Environment Overview

This system uses **three isolated environments** to ensure safe development and deployment:

```
┌─────────────┐      ┌─────────────┐      ┌─────────────┐
│    LOCAL    │ -->  │   STAGING   │ -->  │ PRODUCTION  │
│             │      │             │      │             │
│ Development │      │   Testing   │      │ Live System │
│  per Dev    │      │    UAT      │      │  Real Data  │
└─────────────┘      └─────────────┘      └─────────────┘
```

| Environment | Purpose | Who Access | Data |
|-------------|---------|-----------|------|
| **local** | Feature development, debugging | Developers only | Fake/seeded data |
| **staging** | Pre-production testing, UAT | Developers, Testers, Client | Anonymized production copy |
| **production** | Live system for end users | End users, Support team | Real data |

### Environment Rules

#### CRITICAL RULES - NEVER VIOLATE

```yaml
Rule 1: NO Direct Production Deployment
  - All changes MUST pass staging first
  - Production deploys only from main/release branch
  
Rule 2: Environment Isolation
  - Each environment has its own:
    ✓ Database (separate PostgreSQL instance)
    ✓ Storage (separate S3 bucket / local disk)
    ✓ API Keys (WhatsApp, backup services)
    ✓ Queue workers
    ✓ Domain/URL
  
Rule 3: Database Safety
  - NEVER run migrations directly on production console
  - NEVER point local .env to production database
  - Use migration scripts in deployment process
  
Rule 4: Configuration Management
  - NO sensitive data in Git (.env files in .gitignore)
  - Use .env.example as template
  - Each environment maintains its own .env file
```

### Environment Configuration Files

```bash
# Repository structure
.env.example          # Template (committed to Git)
.env.local           # Developer's local config (ignored)
.env.staging         # Staging server config (ignored, stored securely)
.env.production      # Production server config (ignored, stored securely)

.gitignore
  .env
  .env.local
  .env.staging
  .env.production
  .env.*.local
```

### Environment-Specific Settings

#### Local Environment (.env.local)

```env
APP_NAME="Sistem Keuangan MI [LOCAL]"
APP_ENV=local
APP_DEBUG=true  # Enable detailed errors
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=school_finance_local
DB_USERNAME=postgres
DB_PASSWORD=local_password

QUEUE_CONNECTION=sync  # No queue worker needed

MAIL_MAILER=log  # Emails go to log file
WHATSAPP_GATEWAY_URL=https://mock-api.test  # Mock service

# No backup needed in local
```

#### Staging Environment (.env.staging)

```env
APP_NAME="Sistem Keuangan MI [STAGING]"
APP_ENV=staging
APP_DEBUG=true  # Still allow debugging
APP_URL=https://staging.keuangan-mi.example.com

DB_CONNECTION=pgsql
DB_HOST=staging-db.internal
DB_DATABASE=school_finance_staging
DB_USERNAME=staging_user
DB_PASSWORD=<secure-staging-password>

QUEUE_CONNECTION=database  # Real queue

MAIL_MAILER=smtp  # Real emails but to test addresses
WHATSAPP_GATEWAY_URL=https://sandbox.whatsapp-api.com
WHATSAPP_API_KEY=<test-api-key>

# Daily backup
BACKUP_DISK=staging_backups
```

#### Production Environment (.env.production)

```env
APP_NAME="Sistem Keuangan MI"
APP_ENV=production
APP_DEBUG=false  # MUST be false
APP_URL=https://keuangan-mi.example.com

DB_CONNECTION=pgsql
DB_HOST=production-db.internal
DB_DATABASE=school_finance
DB_USERNAME=prod_user
DB_PASSWORD=<very-secure-production-password>

QUEUE_CONNECTION=database

MAIL_MAILER=smtp
WHATSAPP_GATEWAY_URL=https://api.whatsapp-gateway.com
WHATSAPP_API_KEY=<production-api-key>

# Daily encrypted backup
BACKUP_DISK=production_backups
BACKUP_ENCRYPTION_PASSWORD=<encryption-key>

# Security
SESSION_SECURE_COOKIE=true
FORCE_HTTPS=true
```

### Deployment Workflow

```
┌──────────────────────────────────────────────────────┐
│  Developer Workflow                                  │
└──────────────────────────────────────────────────────┘

1. Develop on LOCAL
   git checkout -b feature/new-feature
   [write code]
   php artisan test
   
2. Commit & Push
   git add .
   git commit -m "Add new feature"
   git push origin feature/new-feature
   
3. Create Pull Request
   Review by team
   Run automated tests
   
4. Merge to staging branch
   git checkout staging
   git merge feature/new-feature
   
5. Deploy to STAGING
   ssh staging-server
   cd /var/www/school-finance
   git pull origin staging
   composer install --no-dev
   php artisan migrate
   php artisan config:cache
   [UAT testing]
   
6. If staging OK, merge to main
   git checkout main
   git merge staging
   git tag v1.2.3
   
7. Deploy to PRODUCTION
   ssh production-server
   cd /var/www/school-finance
   php artisan down  # Maintenance mode
   git pull origin main
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan up  # Exit maintenance
   
8. Monitor production
   tail -f storage/logs/laravel.log
   [verify critical features]
```

### Environment Verification Checklist

Before deployment to each environment:

```markdown
## Local Development
- [ ] .env.local points to local database
- [ ] Migrations run successfully
- [ ] Seeders populate test data
- [ ] Unit tests pass
- [ ] Feature tests pass

## Staging Deployment
- [ ] .env.staging configured correctly
- [ ] Database is copy of production (anonymized)
- [ ] Migrations tested
- [ ] UAT scenarios documented
- [ ] Client approval obtained

## Production Deployment
- [ ] Backup created before deployment
- [ ] .env.production verified
- [ ] Migrations are reversible
- [ ] Rollback plan documented
- [ ] Off-hours deployment scheduled
- [ ] Monitoring alerts configured
- [ ] Health check endpoint tested
```

### Preventing Accidents

```php
// config/app.php - Add environment indicator
'env_indicator' => [
    'local' => ['color' => 'green', 'label' => 'LOCAL'],
    'staging' => ['color' => 'yellow', 'label' => 'STAGING'],
    'production' => ['color' => 'red', 'label' => 'PRODUCTION'],
],

// Display in layout
@if(config('app.env') !== 'production')
    <div class="fixed top-0 left-0 right-0 bg-{{ config('app.env_indicator')[config('app.env')]['color'] }}-500 text-white text-center py-1 text-sm font-bold z-50">
        {{ config('app.env_indicator')[config('app.env')]['label'] }} ENVIRONMENT
    </div>
@endif

// Middleware to prevent production accidents
class PreventProductionDestruction
{
    public function handle($request, Closure $next)
    {
        if (app()->environment('production')) {
            // Require confirmation for destructive actions
            if ($request->isMethod('delete') && !$request->has('confirmed')) {
                abort(403, 'Production deletion requires confirmation');
            }
        }
        
        return $next($request);
    }
}
```

---

## TABLE OF CONTENTS

1. [System Architecture](#1-system-architecture)
2. [Database Design](#2-database-design)
3. [API Specifications](#3-api-specifications)
4. [Authentication & Authorization](#4-authentication--authorization)
5. [Core Modules](#5-core-modules)
6. [Security Requirements](#6-security-requirements)
7. [Testing Strategy](#7-testing-strategy)
8. [Deployment Guide](#8-deployment-guide)
9. [Development Roadmap](#9-development-roadmap)

---

## 1. SYSTEM ARCHITECTURE

### 1.1 Technology Stack

```yaml
Backend:
  Framework: Laravel 11.x LTS
  PHP: 8.2+
  
Database:
  Primary: PostgreSQL 15+ (production)
  Alternative: MySQL 8.0+ (acceptable)
  
Frontend:
  Template: Blade
  CSS: Tailwind CSS 3.x
  JS: Alpine.js 3.x, Chart.js 4.x
  Icons: Heroicons

Key Packages:
  Authentication: laravel/breeze ^2.0
  Permissions: spatie/laravel-permission ^6.0
  PDF: barryvdh/laravel-dompdf ^3.0
  Excel: maatwebsite/excel ^3.1
  Backup: spatie/laravel-backup ^9.0
  Audit: spatie/laravel-activitylog ^4.8
  Queue: Laravel Horizon (optional)
```

### 1.2 Application Architecture

```
┌──────────────────────────────────────────────┐
│              Client Layer                     │
│  (Browser: Chrome, Firefox, Safari, Edge)    │
└────────────────┬─────────────────────────────┘
                 │ HTTPS/TLS 1.3
                 ▼
┌──────────────────────────────────────────────┐
│         Presentation Layer                    │
│  Blade Templates + Tailwind + Alpine.js      │
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

### 1.3 System Components

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/               # Authentication
│   │   ├── Master/             # Master data (Student, Class, Category, FeeType, FeeMatrix)
│   │   ├── Transaction/        # Income & Expense
│   │   ├── Report/             # Daily, Monthly, Yearly, Arrears
│   │   ├── Admin/              # User management, Settings, Backup
│   │   └── Dashboard/          # Main dashboard
│   ├── Middleware/
│   │   ├── CheckRole.php
│   │   ├── AuditLog.php
│   │   └── ForceHttps.php
│   ├── Requests/              # Form validation
│   └── Resources/             # API resources (JSON responses)
├── Models/
│   ├── User.php
│   ├── Student.php
│   ├── Transaction.php
│   ├── StudentObligation.php
│   └── ...
├── Services/
│   ├── TransactionService.php
│   ├── ReceiptService.php
│   ├── ReportService.php
│   ├── ArrearsService.php
│   └── WhatsAppService.php
├── Events/
│   ├── TransactionCreated.php
│   └── ObligationGenerated.php
├── Listeners/
│   ├── SendPaymentNotification.php
│   └── UpdateArrearsStatus.php
├── Console/
│   └── Commands/
│       ├── GenerateMonthlyObligations.php
│       └── SendArrearsReminder.php
└── Exports/
    ├── DailyReportExport.php
    ├── MonthlyReportExport.php
    └── ArrearsReportExport.php
```

---

## 2. DATABASE DESIGN

### 2.1 Complete ERD Schema

```sql
-- ============================================
-- AUTHENTICATION & AUTHORIZATION
-- ============================================

CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id BIGINT,
    is_active BOOLEAN DEFAULT true,
    email_verified_at TIMESTAMP,
    last_login_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE roles (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    guard_name VARCHAR(100) NOT NULL DEFAULT 'web',
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE permissions (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    guard_name VARCHAR(100) NOT NULL DEFAULT 'web',
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE role_has_permissions (
    permission_id BIGINT NOT NULL,
    role_id BIGINT NOT NULL,
    PRIMARY KEY (permission_id, role_id),
    CONSTRAINT fk_rhp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    CONSTRAINT fk_rhp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- ============================================
-- MASTER DATA
-- ============================================

CREATE TABLE classes (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    level SMALLINT NOT NULL, -- 1-6
    academic_year VARCHAR(9) NOT NULL, -- "2025/2026"
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(name, academic_year)
);

CREATE TABLE student_categories (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL, -- "REGULER", "YATIM", "DHUAFA"
    name VARCHAR(100) NOT NULL,
    description TEXT,
    discount_percentage DECIMAL(5,2) DEFAULT 0, -- 0-100
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE students (
    id BIGSERIAL PRIMARY KEY,
    nis VARCHAR(20) UNIQUE NOT NULL,
    nisn VARCHAR(20) UNIQUE,
    name VARCHAR(255) NOT NULL,
    class_id BIGINT NOT NULL,
    category_id BIGINT NOT NULL,
    gender CHAR(1) CHECK (gender IN ('L', 'P')),
    birth_date DATE,
    birth_place VARCHAR(100),
    parent_name VARCHAR(255),
    parent_phone VARCHAR(20),
    parent_whatsapp VARCHAR(20),
    address TEXT,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'graduated', 'dropout', 'transferred')),
    enrollment_date DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP, -- Soft delete
    CONSTRAINT fk_students_class FOREIGN KEY (class_id) REFERENCES classes(id),
    CONSTRAINT fk_students_category FOREIGN KEY (category_id) REFERENCES student_categories(id)
);

CREATE INDEX idx_students_nis ON students(nis);
CREATE INDEX idx_students_status ON students(status);
CREATE INDEX idx_students_class ON students(class_id);

CREATE TABLE fee_types (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL, -- "SPP", "DAFTAR_ULANG", "SERAGAM"
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_monthly BOOLEAN DEFAULT false, -- true untuk SPP
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE fee_matrix (
    id BIGSERIAL PRIMARY KEY,
    fee_type_id BIGINT NOT NULL,
    class_id BIGINT, -- NULL = applies to all classes
    category_id BIGINT, -- NULL = applies to all categories
    amount DECIMAL(15,2) NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE, -- NULL = no end date
    is_active BOOLEAN DEFAULT true,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_feematrix_feetype FOREIGN KEY (fee_type_id) REFERENCES fee_types(id),
    CONSTRAINT fk_feematrix_class FOREIGN KEY (class_id) REFERENCES classes(id),
    CONSTRAINT fk_feematrix_category FOREIGN KEY (category_id) REFERENCES student_categories(id),
    CONSTRAINT chk_effective_dates CHECK (effective_to IS NULL OR effective_to >= effective_from)
);

CREATE INDEX idx_feematrix_lookup ON fee_matrix(fee_type_id, class_id, category_id, effective_from);

-- ============================================
-- TRANSACTIONS
-- ============================================

CREATE TABLE transactions (
    id BIGSERIAL PRIMARY KEY,
    transaction_number VARCHAR(50) UNIQUE NOT NULL, -- "NF-2026-000001" or "NK-2026-000001"
    transaction_date DATE NOT NULL,
    transaction_type VARCHAR(20) NOT NULL CHECK (transaction_type IN ('income', 'expense')),
    student_id BIGINT, -- NULL for expense
    payment_method VARCHAR(20) CHECK (payment_method IN ('cash', 'transfer', 'qris')),
    total_amount DECIMAL(15,2) NOT NULL,
    notes TEXT,
    receipt_path VARCHAR(255), -- Path to PDF receipt
    proof_path VARCHAR(255), -- Path to payment proof (for expense)
    status VARCHAR(20) DEFAULT 'completed' CHECK (status IN ('completed', 'cancelled')),
    cancelled_at TIMESTAMP,
    cancelled_by BIGINT,
    cancellation_reason TEXT,
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_transactions_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_transactions_creator FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_transactions_canceller FOREIGN KEY (cancelled_by) REFERENCES users(id)
);

CREATE INDEX idx_transactions_date ON transactions(transaction_date);
CREATE INDEX idx_transactions_number ON transactions(transaction_number);
CREATE INDEX idx_transactions_student ON transactions(student_id);
CREATE INDEX idx_transactions_type ON transactions(transaction_type, status);

CREATE TABLE transaction_items (
    id BIGSERIAL PRIMARY KEY,
    transaction_id BIGINT NOT NULL,
    fee_type_id BIGINT NOT NULL,
    description VARCHAR(255), -- e.g., "SPP Januari 2026"
    amount DECIMAL(15,2) NOT NULL,
    month SMALLINT, -- 1-12 (for monthly fees)
    year SMALLINT, -- e.g., 2026
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_transitem_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    CONSTRAINT fk_transitem_feetype FOREIGN KEY (fee_type_id) REFERENCES fee_types(id)
);

CREATE INDEX idx_transitem_transaction ON transaction_items(transaction_id);

-- ============================================
-- ARREARS TRACKING
-- ============================================

CREATE TABLE student_obligations (
    id BIGSERIAL PRIMARY KEY,
    student_id BIGINT NOT NULL,
    fee_type_id BIGINT NOT NULL,
    month SMALLINT NOT NULL, -- 1-12
    year SMALLINT NOT NULL, -- e.g., 2026
    amount DECIMAL(15,2) NOT NULL,
    is_paid BOOLEAN DEFAULT false,
    paid_amount DECIMAL(15,2) DEFAULT 0,
    paid_at TIMESTAMP,
    transaction_item_id BIGINT, -- Link to payment
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_obligations_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_obligations_feetype FOREIGN KEY (fee_type_id) REFERENCES fee_types(id),
    CONSTRAINT fk_obligations_transitem FOREIGN KEY (transaction_item_id) REFERENCES transaction_items(id),
    UNIQUE(student_id, fee_type_id, month, year)
);

CREATE INDEX idx_obligations_unpaid ON student_obligations(student_id, is_paid) WHERE is_paid = false;
CREATE INDEX idx_obligations_period ON student_obligations(year, month);

-- ============================================
-- NOTIFICATIONS
-- ============================================

CREATE TABLE notifications (
    id BIGSERIAL PRIMARY KEY,
    student_id BIGINT NOT NULL,
    type VARCHAR(50) NOT NULL, -- "payment_success", "arrears_reminder"
    message TEXT NOT NULL,
    recipient_phone VARCHAR(20),
    whatsapp_status VARCHAR(20) DEFAULT 'pending' CHECK (whatsapp_status IN ('pending', 'sent', 'failed')),
    whatsapp_sent_at TIMESTAMP,
    whatsapp_response TEXT,
    is_read BOOLEAN DEFAULT false,
    read_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_notifications_student FOREIGN KEY (student_id) REFERENCES students(id)
);

CREATE INDEX idx_notifications_status ON notifications(whatsapp_status);
CREATE INDEX idx_notifications_student ON notifications(student_id);

-- ============================================
-- AUDIT & SYSTEM
-- ============================================

CREATE TABLE activity_log (
    id BIGSERIAL PRIMARY KEY,
    log_name VARCHAR(255),
    description TEXT NOT NULL,
    subject_type VARCHAR(255),
    subject_id BIGINT,
    causer_type VARCHAR(255),
    causer_id BIGINT,
    properties JSONB, -- Old & new values
    event VARCHAR(255),
    batch_uuid UUID,
    created_at TIMESTAMP
);

CREATE INDEX idx_activitylog_subject ON activity_log(subject_type, subject_id);
CREATE INDEX idx_activitylog_causer ON activity_log(causer_type, causer_id);
CREATE INDEX idx_activitylog_created ON activity_log(created_at);

CREATE TABLE settings (
    id BIGSERIAL PRIMARY KEY,
    key VARCHAR(100) UNIQUE NOT NULL,
    value TEXT,
    type VARCHAR(20) DEFAULT 'string' CHECK (type IN ('string', 'number', 'boolean', 'json')),
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Default settings
INSERT INTO settings (key, value, type, description) VALUES
('school_name', 'Madrasah Ibtidaiyah', 'string', 'Nama sekolah'),
('school_address', '', 'string', 'Alamat sekolah'),
('school_phone', '', 'string', 'Nomor telepon sekolah'),
('school_logo', '', 'string', 'Path to logo file'),
('whatsapp_gateway_url', '', 'string', 'WhatsApp gateway API URL'),
('whatsapp_api_key', '', 'string', 'WhatsApp API key'),
('reminder_day', 'monday', 'string', 'Day to send arrears reminder'),
('receipt_footer_text', 'Terima kasih atas pembayarannya', 'string', 'Footer text on receipts');
```

### 2.2 Database Indexes Strategy

**Primary Indexes (Already Created):**
- All primary keys (BIGSERIAL)
- Unique constraints (nis, nisn, email, transaction_number)

**Performance Indexes:**
```sql
-- High-frequency queries
CREATE INDEX idx_students_active_class ON students(class_id, status) WHERE status = 'active';
CREATE INDEX idx_transactions_daily ON transactions(transaction_date, transaction_type);
CREATE INDEX idx_feematrix_active ON fee_matrix(fee_type_id, is_active) WHERE is_active = true;

-- Reporting queries
CREATE INDEX idx_transitem_monthly ON transaction_items(year, month);
CREATE INDEX idx_obligations_summary ON student_obligations(year, month, is_paid);

-- Search optimization
CREATE INDEX idx_students_name_trgm ON students USING gin (name gin_trgm_ops);
-- Requires: CREATE EXTENSION pg_trgm;
```

### 2.3 Database Migration Governance

**CRITICAL RULES for Production Database Changes**

#### Golden Rules

```yaml
Rule 1: NEVER Edit Existing Migrations
  - Once a migration is run in production, it is IMMUTABLE
  - Always create NEW migration for schema changes
  - Reason: Git history + rollback capability
  
Rule 2: All Migrations Must Be Reversible
  - Every up() must have a corresponding down()
  - Test rollback before deploying
  - Exception: Data migrations (document carefully)
  
Rule 3: Use Descriptive Names
  - Format: YYYY_MM_DD_HHMMSS_descriptive_action
  - Good: 2026_02_09_create_transactions_table
  - Good: 2026_02_10_add_index_to_students_status
  - Bad: 2026_02_11_update_table
  
Rule 4: Test Migrations in Sequence
  - Fresh install: php artisan migrate:fresh
  - Incremental: php artisan migrate
  - Rollback: php artisan migrate:rollback
  - All must work without errors
```

#### Migration Workflow

```bash
# 1. Create migration with descriptive name
php artisan make:migration add_payment_reference_to_transactions_table

# 2. Implement both up() and down()
# database/migrations/2026_02_10_100000_add_payment_reference_to_transactions_table.php
public function up()
{
    Schema::table('transactions', function (Blueprint $table) {
        $table->string('payment_reference', 50)->nullable()->after('payment_method');
        $table->index('payment_reference');
    });
}

public function down()
{
    Schema::table('transactions', function (Blueprint $table) {
        $table->dropIndex(['payment_reference']);
        $table->dropColumn('payment_reference');
    });
}

# 3. Test locally
php artisan migrate
php artisan migrate:rollback
php artisan migrate

# 4. Test on staging
ssh staging
php artisan migrate

# 5. Deploy to production (scheduled maintenance window)
ssh production
php artisan down
php artisan migrate --force
php artisan up
```

#### Example Migrations

**Adding a Column:**

```php
// Good ✅
class AddStatusToStudentObligations extends Migration
{
    public function up()
    {
        Schema::table('student_obligations', function (Blueprint $table) {
            $table->string('status', 20)
                ->default('pending')
                ->after('is_paid')
                ->comment('Payment status: pending, partial, paid');
            
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::table('student_obligations', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
}
```

**Adding an Index:**

```php
class AddIndexToTransactionsStatus extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['status', 'transaction_date'], 'idx_transactions_status_date');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_status_date');
        });
    }
}
```

**Renaming a Column (CAREFUL!):**

```php
class RenameParentPhoneToParentContactInStudents extends Migration
{
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('parent_phone', 'parent_contact');
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('parent_contact', 'parent_phone');
        });
    }
}

// WARNING: May require downtime. Test thoroughly.
```

**Data Migration (Populate Default Values):**

```php
class PopulateDefaultCategoryForStudents extends Migration
{
    public function up()
    {
        $defaultCategory = StudentCategory::firstOrCreate(
            ['code' => 'REGULER'],
            ['name' => 'Reguler', 'discount_percentage' => 0]
        );
        
        // Update students without category
        DB::table('students')
            ->whereNull('category_id')
            ->update(['category_id' => $defaultCategory->id]);
    }

    public function down()
    {
        // Cannot reverse data changes safely
        // Document this in migration notes
    }
}
```

#### Migration Testing Checklist

```markdown
Before deploying ANY migration to production:

- [ ] Migration runs successfully on fresh database
- [ ] Migration runs successfully on copy of production database
- [ ] Rollback works (down() method tested)
- [ ] No data loss occurs
- [ ] Indexes are created properly
- [ ] Foreign key constraints work
- [ ] Application still functions after migration
- [ ] Backup created before production migration
- [ ] Rollback plan documented
```

#### Dangerous Operations

```php
// ❌ NEVER DO IN PRODUCTION without extreme caution:

// 1. Dropping columns with data
Schema::table('transactions', function (Blueprint $table) {
    $table->dropColumn('important_data'); // DATA LOSS!
});

// 2. Changing column type without migration path
$table->string('amount')->change(); // Was decimal, now string - CORRUPTION!

// 3. Dropping tables
Schema::dropIfExists('transactions'); // CATASTROPHIC!

// ✅ SAFER APPROACHES:

// 1. Rename column instead of drop (keep old data)
$table->renameColumn('old_name', 'deprecated_old_name');
$table->string('new_name')->nullable();
// Migrate data gradually, then drop old column in future migration

// 2. Create new column, migrate data, drop old
$table->decimal('amount_new', 15, 2)->nullable();
// Run artisan command to copy data
DB::statement('UPDATE transactions SET amount_new = amount::decimal');
// In next migration, drop old column

// 3. Never drop tables in production. Mark as deprecated instead.
Schema::table('transactions', function (Blueprint $table) {
    $table->boolean('is_deprecated')->default(true);
});
```

#### Migration History Tracking

```sql
-- View migration history
SELECT * FROM migrations ORDER BY batch DESC, migration;

-- Check what will be rolled back
SELECT * FROM migrations WHERE batch = (SELECT MAX(batch) FROM migrations);

-- Count pending migrations (in production)
-- Should always be 0 in production
```

---

## 3. API SPECIFICATIONS

### 3.1 RESTful Endpoints

#### System Health Check

```http
GET /health
Authorization: None (public endpoint)

Response 200:
{
  "status": "ok",
  "timestamp": "2026-02-09T10:30:00Z",
  "environment": "production",
  "checks": {
    "database": {
      "status": "connected",
      "response_time_ms": 45
    },
    "storage": {
      "status": "ok",
      "disk_usage_percent": 35
    },
    "queue": {
      "status": "running",
      "pending_jobs": 3,
      "failed_jobs": 0
    },
    "cache": {
      "status": "ok"
    }
  },
  "version": "1.0.0"
}

Response 503 (Service Unavailable):
{
  "status": "degraded",
  "timestamp": "2026-02-09T10:30:00Z",
  "checks": {
    "database": {
      "status": "error",
      "message": "Connection timeout"
    },
    "storage": {
      "status": "ok"
    },
    "queue": {
      "status": "warning",
      "pending_jobs": 150,
      "failed_jobs": 5
    }
  }
}
```

**Implementation:**

```php
// app/Http/Controllers/HealthCheckController.php
class HealthCheckController extends Controller
{
    public function check()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
            'cache' => $this->checkCache(),
        ];
        
        $overallStatus = $this->determineOverallStatus($checks);
        $httpStatus = $overallStatus === 'ok' ? 200 : 503;
        
        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'environment' => config('app.env'),
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
        ], $httpStatus);
    }
    
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000);
            
            return [
                'status' => 'connected',
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Connection failed',
            ];
        }
    }
    
    protected function checkStorage(): array
    {
        try {
            $totalSpace = disk_total_space(storage_path());
            $freeSpace = disk_free_space(storage_path());
            $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100);
            
            return [
                'status' => $usedPercent < 90 ? 'ok' : 'warning',
                'disk_usage_percent' => $usedPercent,
            ];
        } catch (\Exception $e) {
            return ['status' => 'error'];
        }
    }
    
    protected function checkQueue(): array
    {
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            
            $status = 'ok';
            if ($failedJobs > 10) $status = 'warning';
            if ($failedJobs > 50 || $pendingJobs > 100) $status = 'error';
            
            return [
                'status' => $status,
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
            ];
        } catch (\Exception $e) {
            return ['status' => 'error'];
        }
    }
    
    protected function checkCache(): array
    {
        try {
            Cache::put('health_check', true, 10);
            $canRead = Cache::get('health_check') === true;
            
            return ['status' => $canRead ? 'ok' : 'error'];
        } catch (\Exception $e) {
            return ['status' => 'error'];
        }
    }
    
    protected function determineOverallStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');
        
        if (in_array('error', $statuses)) return 'degraded';
        if (in_array('warning', $statuses)) return 'warning';
        
        return 'ok';
    }
}

// routes/api.php
Route::get('/health', [HealthCheckController::class, 'check']);
```

**Usage for Monitoring:**

```bash
# Uptime monitoring (UptimeRobot, Pingdom, etc.)
curl https://keuangan-mi.example.com/health

# Automated health checks (cron)
*/5 * * * * curl -f https://keuangan-mi.example.com/health || echo "Health check failed" | mail -s "Alert" admin@example.com
```

#### Authentication

```http
POST /login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password123"
}

Response 200:
{
  "success": true,
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com",
    "role": "super_admin"
  },
  "redirect": "/dashboard"
}

Response 422:
{
  "success": false,
  "message": "Email atau password salah",
  "errors": {
    "email": ["Kredensial tidak valid"]
  }
}
```

```http
POST /logout
Authorization: Session Cookie

Response 302: Redirect to /login
```

#### Students

```http
GET /api/students?search=ahmad&class_id=1&status=active&per_page=20
Authorization: Bearer token / Session

Response 200:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nis": "2024001",
      "name": "Ahmad Fauzi",
      "class": {
        "id": 1,
        "name": "Kelas 1A"
      },
      "category": {
        "id": 1,
        "name": "Reguler"
      },
      "status": "active"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150
  }
}
```

```http
POST /api/students
Content-Type: application/json

{
  "nis": "2024001",
  "nisn": "0123456789",
  "name": "Ahmad Fauzi",
  "class_id": 1,
  "category_id": 1,
  "gender": "L",
  "birth_date": "2015-05-10",
  "birth_place": "Jakarta",
  "parent_name": "Budi Santoso",
  "parent_phone": "08123456789",
  "parent_whatsapp": "628123456789"
}

Response 201:
{
  "success": true,
  "message": "Siswa berhasil ditambahkan",
  "data": {
    "id": 1,
    "nis": "2024001",
    "name": "Ahmad Fauzi"
  }
}

Response 422:
{
  "success": false,
  "message": "Validasi gagal",
  "errors": {
    "nis": ["NIS sudah digunakan"],
    "parent_whatsapp": ["Format nomor WhatsApp harus 628xxxxxxxxxx"]
  }
}
```

#### Fee Matrix

```http
GET /api/fee-matrix?fee_type_id=1&class_id=1&category_id=1&date=2026-02-09
Authorization: Session

Response 200:
{
  "success": true,
  "data": {
    "id": 1,
    "fee_type": {
      "id": 1,
      "code": "SPP",
      "name": "SPP Bulanan"
    },
    "class": {
      "id": 1,
      "name": "Kelas 1A"
    },
    "category": {
      "id": 1,
      "name": "Reguler"
    },
    "amount": 150000,
    "effective_from": "2026-01-01",
    "effective_to": null
  }
}
```

#### Transactions

```http
POST /api/transactions/income
Content-Type: application/json

{
  "transaction_date": "2026-02-09",
  "student_id": 1,
  "payment_method": "cash",
  "items": [
    {
      "fee_type_id": 1,
      "amount": 150000,
      "month": 2,
      "year": 2026,
      "description": "SPP Februari 2026"
    },
    {
      "fee_type_id": 2,
      "amount": 50000,
      "description": "Uang Buku"
    }
  ],
  "notes": "Pembayaran tepat waktu"
}

Response 201:
{
  "success": true,
  "message": "Transaksi berhasil disimpan",
  "data": {
    "id": 1,
    "transaction_number": "NF-2026-000001",
    "total_amount": 200000,
    "receipt_url": "/receipts/NF-2026-000001.pdf"
  },
  "notification": {
    "whatsapp_sent": true,
    "whatsapp_status": "sent"
  }
}

Response 422:
{
  "success": false,
  "message": "Validasi gagal",
  "errors": {
    "items.0.amount": ["Nominal harus sesuai tarif (Rp 150.000)"],
    "student_id": ["Siswa tidak ditemukan atau tidak aktif"]
  }
}
```

```http
GET /api/transactions?type=income&date_from=2026-02-01&date_to=2026-02-09&student_id=1
Authorization: Session

Response 200:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "transaction_number": "NF-2026-000001",
      "transaction_date": "2026-02-09",
      "student": {
        "id": 1,
        "name": "Ahmad Fauzi",
        "class": "Kelas 1A"
      },
      "total_amount": 200000,
      "status": "completed",
      "items": [
        {
          "description": "SPP Februari 2026",
          "amount": 150000
        }
      ]
    }
  ],
  "meta": {
    "total": 1,
    "sum_amount": 200000
  }
}
```

#### Reports

```http
GET /api/reports/daily?date=2026-02-09
Authorization: Session

Response 200:
{
  "success": true,
  "data": {
    "date": "2026-02-09",
    "summary": {
      "total_income": 1500000,
      "total_expense": 300000,
      "net_balance": 1200000,
      "transaction_count": 15
    },
    "income_breakdown": [
      {
        "fee_type": "SPP Bulanan",
        "count": 10,
        "amount": 1500000
      }
    ],
    "expense_breakdown": [
      {
        "description": "ATK",
        "amount": 300000
      }
    ]
  }
}
```

```http
GET /api/reports/arrears?class_id=1&month=2&year=2026
Authorization: Session

Response 200:
{
  "success": true,
  "data": [
    {
      "student": {
        "id": 1,
        "nis": "2024001",
        "name": "Ahmad Fauzi",
        "class": "Kelas 1A",
        "parent_whatsapp": "628123456789"
      },
      "arrears": [
        {
          "fee_type": "SPP Bulanan",
          "month": 1,
          "year": 2026,
          "amount": 150000,
          "days_overdue": 40
        },
        {
          "fee_type": "SPP Bulanan",
          "month": 2,
          "year": 2026,
          "amount": 150000,
          "days_overdue": 9
        }
      ],
      "total_arrears": 300000
    }
  ],
  "summary": {
    "total_students": 5,
    "total_amount": 750000
  }
}
```

### 3.2 Validation Rules

```php
// StudentRequest.php
class StoreStudentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nis' => ['required', 'string', 'max:20', 'unique:students,nis'],
            'nisn' => ['nullable', 'string', 'max:20', 'unique:students,nisn'],
            'name' => ['required', 'string', 'max:255'],
            'class_id' => ['required', 'exists:classes,id'],
            'category_id' => ['required', 'exists:student_categories,id'],
            'gender' => ['required', 'in:L,P'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'parent_name' => ['required', 'string', 'max:255'],
            'parent_phone' => ['required', 'string', 'regex:/^08[0-9]{8,11}$/'],
            'parent_whatsapp' => ['required', 'string', 'regex:/^628[0-9]{8,11}$/'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'nis.unique' => 'NIS sudah digunakan oleh siswa lain',
            'parent_whatsapp.regex' => 'Format WhatsApp harus 628xxxxxxxxxx',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini',
        ];
    }
}

// TransactionRequest.php
class StoreIncomeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'student_id' => ['required', 'exists:students,id'],
            'payment_method' => ['required', 'in:cash,transfer,qris'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.fee_type_id' => ['required', 'exists:fee_types,id'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
            'items.*.month' => ['nullable', 'integer', 'between:1,12'],
            'items.*.year' => ['nullable', 'integer', 'min:2020', 'max:2050'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Custom validation: Check if student is active
            $student = Student::find($this->student_id);
            if ($student && $student->status !== 'active') {
                $validator->errors()->add('student_id', 'Siswa tidak aktif');
            }

            // Validate amounts match fee matrix
            foreach ($this->items ?? [] as $index => $item) {
                $expectedAmount = $this->getExpectedAmount($item);
                if ($expectedAmount && $item['amount'] != $expectedAmount) {
                    $validator->errors()->add(
                        "items.{$index}.amount",
                        "Nominal harus Rp " . number_format($expectedAmount, 0, ',', '.')
                    );
                }
            }
        });
    }
}
```

---

## 4. AUTHENTICATION & AUTHORIZATION

### 4.1 Role Definitions

```php
// database/seeders/RolePermissionSeeder.php

$roles = [
    'super_admin' => [
        'description' => 'Full system access',
        'permissions' => ['*'], // All permissions
    ],
    'bendahara' => [
        'description' => 'Treasurer - handle all financial transactions',
        'permissions' => [
            'view_dashboard',
            'manage_students',
            'manage_transactions',
            'view_reports',
            'export_reports',
            'manage_fee_matrix',
            'generate_receipts',
        ],
    ],
    'kepala_sekolah' => [
        'description' => 'Principal - view reports and dashboard only',
        'permissions' => [
            'view_dashboard',
            'view_students',
            'view_transactions',
            'view_reports',
            'export_reports',
        ],
    ],
    'operator_tu' => [
        'description' => 'Administration staff - manage master data',
        'permissions' => [
            'view_dashboard',
            'manage_students',
            'manage_classes',
            'view_transactions',
            'view_reports',
        ],
    ],
    'auditor' => [
        'description' => 'Auditor - read-only access to all data',
        'permissions' => [
            'view_dashboard',
            'view_students',
            'view_transactions',
            'view_reports',
            'view_audit_log',
            'export_reports',
        ],
    ],
];
```

### 4.2 Role Escalation Protection

**CRITICAL SECURITY RULE:** Prevent unauthorized privilege escalation.

#### Protection Rules

```yaml
Rule 1: Super Admin Exclusivity
  Only super_admin can:
    - Create new super_admin users
    - Assign/remove super_admin role
    - Modify super_admin permissions
    - Delete super_admin users
  
Rule 2: Role Assignment Limits
  - bendahara CANNOT assign roles
  - operator_tu CANNOT assign roles
  - kepala_sekolah CANNOT assign roles
  - auditor CANNOT assign roles
  - Only super_admin can manage users & roles
  
Rule 3: Self-Modification Prevention
  - Users CANNOT change their own role
  - Users CANNOT deactivate themselves
  - Requires another admin to make changes
  
Rule 4: Audit All Role Changes
  - All role assignments logged
  - All permission changes logged
  - Cannot be deleted from audit log
```

#### Implementation

```php
// app/Http/Middleware/RestrictRoleManagement.php
class RestrictRoleManagement
{
    public function handle(Request $request, Closure $next)
    {
        // Only super_admin can access user/role management
        if (!auth()->user()->hasRole('super_admin')) {
            abort(403, 'Only Super Admin can manage users and roles');
        }
        
        return $next($request);
    }
}

// app/Http/Controllers/UserController.php
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', RestrictRoleManagement::class]);
    }
    
    public function assignRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|exists:roles,name',
        ]);
        
        // CRITICAL: Prevent non-super-admin from creating super-admin
        if ($validated['role'] === 'super_admin') {
            if (!auth()->user()->hasRole('super_admin')) {
                abort(403, 'Only Super Admin can assign Super Admin role');
            }
        }
        
        // CRITICAL: Prevent self-role modification
        if ($user->id === auth()->id()) {
            abort(403, 'You cannot change your own role');
        }
        
        // CRITICAL: Prevent modifying another super admin (unless you are super admin)
        if ($user->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')) {
            abort(403, 'You cannot modify Super Admin users');
        }
        
        DB::beginTransaction();
        
        try {
            $oldRole = $user->roles->first()?->name;
            
            // Remove all roles and assign new one
            $user->syncRoles([$validated['role']]);
            
            // Audit log
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_role' => $oldRole,
                    'new_role' => $validated['role'],
                ])
                ->log('Role changed');
            
            DB::commit();
            
            return redirect()->back()->with('success', 'Role updated successfully');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update role');
        }
    }
    
    public function destroy(User $user)
    {
        // CRITICAL: Prevent self-deletion
        if ($user->id === auth()->id()) {
            abort(403, 'You cannot delete your own account');
        }
        
        // CRITICAL: Prevent deleting super admin
        if ($user->hasRole('super_admin')) {
            abort(403, 'Super Admin accounts cannot be deleted');
        }
        
        // CRITICAL: Ensure at least one active super admin remains
        $activeSuperAdmins = User::role('super_admin')
            ->where('is_active', true)
            ->count();
        
        if ($user->hasRole('super_admin') && $activeSuperAdmins <= 1) {
            abort(403, 'Cannot delete the last active Super Admin');
        }
        
        $user->delete();
        
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log('User deleted');
        
        return redirect()->back()->with('success', 'User deleted successfully');
    }
}

// app/Policies/UserPolicy.php
class UserPolicy
{
    public function assignRole(User $currentUser, User $targetUser)
    {
        // Only super_admin can assign roles
        if (!$currentUser->hasRole('super_admin')) {
            return false;
        }
        
        // Cannot modify your own role
        if ($currentUser->id === $targetUser->id) {
            return false;
        }
        
        return true;
    }
    
    public function assignSuperAdmin(User $currentUser)
    {
        // Only super_admin can create other super_admins
        return $currentUser->hasRole('super_admin');
    }
    
    public function delete(User $currentUser, User $targetUser)
    {
        // Only super_admin can delete users
        if (!$currentUser->hasRole('super_admin')) {
            return false;
        }
        
        // Cannot delete yourself
        if ($currentUser->id === $targetUser->id) {
            return false;
        }
        
        // Cannot delete super_admin users
        if ($targetUser->hasRole('super_admin')) {
            return false;
        }
        
        return true;
    }
}

// Register policy in AuthServiceProvider
protected $policies = [
    User::class => UserPolicy::class,
];

// Usage in controller
public function assignRole(Request $request, User $user)
{
    $this->authorize('assignRole', $user);
    
    // ... rest of the code
}
```

#### Routes Protection

```php
// routes/web.php

// User management routes - SUPER ADMIN ONLY
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->group(function () {
    Route::resource('users', UserController::class);
    Route::post('users/{user}/assign-role', [UserController::class, 'assignRole'])->name('users.assign-role');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
});

// Other role-based routes
Route::middleware(['auth', 'role:super_admin,bendahara'])->group(function () {
    Route::resource('transactions', TransactionController::class);
});
```

#### Frontend Protection

```blade
{{-- resources/views/admin/users/index.blade.php --}}

@can('assignRole', $user)
    <form method="POST" action="{{ route('users.assign-role', $user) }}">
        @csrf
        <select name="role">
            <option value="bendahara">Bendahara</option>
            <option value="operator_tu">Operator TU</option>
            <option value="kepala_sekolah">Kepala Sekolah</option>
            <option value="auditor">Auditor</option>
            
            @can('assignSuperAdmin', Auth::user())
                <option value="super_admin">Super Admin</option>
            @endcan
        </select>
        <button type="submit">Assign Role</button>
    </form>
@else
    <span class="text-gray-500">No permission to assign role</span>
@endcan

@can('delete', $user)
    <form method="POST" action="{{ route('users.destroy', $user) }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-red-600">Delete</button>
    </form>
@endcan
```

#### Monitoring & Alerts

```php
// app/Listeners/NotifyRoleChange.php
class NotifyRoleChange
{
    public function handle($event)
    {
        $user = $event->user;
        $changes = $event->changes;
        
        // Alert if super_admin role assigned
        if (isset($changes['new_role']) && $changes['new_role'] === 'super_admin') {
            // Send email to all super admins
            $superAdmins = User::role('super_admin')->get();
            
            foreach ($superAdmins as $admin) {
                Mail::to($admin->email)->send(new SuperAdminAssignedAlert($user, $changes));
            }
            
            // Log to separate security log
            Log::channel('security')->warning('Super Admin role assigned', [
                'target_user' => $user->email,
                'assigned_by' => auth()->user()->email,
                'ip_address' => request()->ip(),
                'timestamp' => now(),
            ]);
        }
    }
}

// config/logging.php
'channels' => [
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 90, // Keep for 90 days
    ],
],
```

### 4.3 Middleware Implementation

```php
// app/Http/Middleware/CheckRole.php
class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda telah dinonaktifkan']);
        }

        if (!$user->hasAnyRole($roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini');
        }

        return $next($request);
    }
}

// Usage in routes/web.php
Route::middleware(['auth', 'check.role:super_admin,bendahara'])->group(function () {
    Route::resource('transactions', TransactionController::class);
});
```

### 4.3 Permission Gates

```php
// app/Providers/AuthServiceProvider.php
class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Transaction permissions
        Gate::define('cancel-transaction', function (User $user, Transaction $transaction) {
            // Only creator or super admin can cancel
            return $user->id === $transaction->created_by 
                || $user->hasRole('super_admin');
        });

        Gate::define('reprint-receipt', function (User $user) {
            return $user->hasAnyPermission(['manage_transactions', 'generate_receipts']);
        });

        // Report permissions
        Gate::define('export-sensitive-data', function (User $user) {
            return $user->hasAnyRole(['super_admin', 'bendahara', 'auditor']);
        });
    }
}

// Usage in Controller
public function cancel(Transaction $transaction)
{
    $this->authorize('cancel-transaction', $transaction);
    
    // Proceed with cancellation...
}
```

---

## 5. CORE MODULES

### 5.1 Transaction Service

```php
// app/Services/TransactionService.php
class TransactionService
{
    public function generateTransactionNumber(string $type): string
    {
        $prefix = $type === 'income' ? 'NF' : 'NK';
        $year = date('Y');
        
        // CRITICAL: Use database-level locking to prevent race conditions
        // This ensures unique transaction numbers even under high concurrency
        return DB::transaction(function() use ($prefix, $year) {
            $lastTransaction = Transaction::where('transaction_number', 'LIKE', "{$prefix}-{$year}-%")
                ->orderByDesc('id')
                ->lockForUpdate() // PESSIMISTIC LOCK - Prevents concurrent access
                ->first();

            $sequence = $lastTransaction 
                ? (int) substr($lastTransaction->transaction_number, -6) + 1 
                : 1;

            return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
        });
    }
    
    /**
     * CONCURRENCY SAFETY EXPLANATION
     * 
     * Without lockForUpdate(), this can happen:
     * 
     * Time | User A                    | User B
     * -----|---------------------------|---------------------------
     * T1   | Read last number: 000050  |
     * T2   |                           | Read last number: 000050
     * T3   | Generate: 000051          |
     * T4   |                           | Generate: 000051
     * T5   | Save: NF-2026-000051      |
     * T6   |                           | Save: NF-2026-000051 ❌ DUPLICATE!
     * 
     * With lockForUpdate():
     * 
     * Time | User A                    | User B
     * -----|---------------------------|---------------------------
     * T1   | Lock & read: 000050       |
     * T2   |                           | Wait... (locked)
     * T3   | Generate: 000051          |
     * T4   | Save & release lock       |
     * T5   |                           | Lock & read: 000051
     * T6   |                           | Generate: 000052 ✅ UNIQUE!
     * 
     * This is CRITICAL for financial systems where duplicate transaction
     * numbers would violate accounting principles and cause audit failures.
     */

    public function createIncome(array $data): Transaction
    {
        DB::beginTransaction();
        
        try {
            // 1. Create transaction
            $transaction = Transaction::create([
                'transaction_number' => $this->generateTransactionNumber('income'),
                'transaction_date' => $data['transaction_date'],
                'transaction_type' => 'income',
                'student_id' => $data['student_id'],
                'payment_method' => $data['payment_method'],
                'total_amount' => collect($data['items'])->sum('amount'),
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // 2. Create transaction items
            foreach ($data['items'] as $item) {
                $transactionItem = $transaction->items()->create($item);

                // 3. Update student obligations if monthly fee
                if (!empty($item['month']) && !empty($item['year'])) {
                    StudentObligation::where([
                        'student_id' => $data['student_id'],
                        'fee_type_id' => $item['fee_type_id'],
                        'month' => $item['month'],
                        'year' => $item['year'],
                    ])->update([
                        'is_paid' => true,
                        'paid_amount' => $item['amount'],
                        'paid_at' => now(),
                        'transaction_item_id' => $transactionItem->id,
                    ]);
                }
            }

            // 4. Generate receipt
            $receiptPath = app(ReceiptService::class)->generate($transaction);
            $transaction->update(['receipt_path' => $receiptPath]);

            // 5. Fire event for notifications
            event(new TransactionCreated($transaction));

            DB::commit();
            
            return $transaction;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function cancelTransaction(Transaction $transaction, string $reason): bool
    {
        DB::beginTransaction();
        
        try {
            // 1. Update transaction status
            $transaction->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => $reason,
            ]);

            // 2. Revert student obligations
            foreach ($transaction->items as $item) {
                if ($item->month && $item->year) {
                    StudentObligation::where([
                        'student_id' => $transaction->student_id,
                        'fee_type_id' => $item->fee_type_id,
                        'month' => $item->month,
                        'year' => $item->year,
                    ])->update([
                        'is_paid' => false,
                        'paid_amount' => 0,
                        'paid_at' => null,
                        'transaction_item_id' => null,
                    ]);
                }
            }

            // 3. Regenerate receipt with "DIBATALKAN" watermark
            $receiptPath = app(ReceiptService::class)->generate($transaction, true);
            $transaction->update(['receipt_path' => $receiptPath]);

            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getFeeMatrix(int $studentId, int $feeTypeId, ?string $date = null): ?FeeMatrix
    {
        $student = Student::with(['class', 'category'])->findOrFail($studentId);
        $date = $date ?? today();

        return FeeMatrix::where('fee_type_id', $feeTypeId)
            ->where(function ($query) use ($student) {
                $query->whereNull('class_id')
                    ->orWhere('class_id', $student->class_id);
            })
            ->where(function ($query) use ($student) {
                $query->whereNull('category_id')
                    ->orWhere('category_id', $student->category_id);
            })
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            })
            ->where('is_active', true)
            ->orderByDesc('class_id') // More specific class first
            ->orderByDesc('category_id') // More specific category first
            ->first();
    }
}
```

### 5.2 Receipt Service

```php
// app/Services/ReceiptService.php
class ReceiptService
{
    public function generate(Transaction $transaction, bool $isCancelled = false): string
    {
        $pdf = PDF::loadView('receipts.template', [
            'transaction' => $transaction->load(['student.class', 'items.feeType', 'creator']),
            'settings' => $this->getSettings(),
            'is_cancelled' => $isCancelled,
            'terbilang' => $this->terbilang($transaction->total_amount),
        ]);

        $filename = $transaction->transaction_number . '.pdf';
        $path = 'receipts/' . date('Y/m');
        
        Storage::disk('public')->put($path . '/' . $filename, $pdf->output());

        return $path . '/' . $filename;
    }

    protected function getSettings(): array
    {
        return [
            'school_name' => Setting::get('school_name', 'Madrasah Ibtidaiyah'),
            'school_address' => Setting::get('school_address'),
            'school_phone' => Setting::get('school_phone'),
            'school_logo' => Setting::get('school_logo'),
            'receipt_footer' => Setting::get('receipt_footer_text'),
        ];
    }

    protected function terbilang(float $number): string
    {
        // Implementation of Indonesian number to words conversion
        // ... (complete implementation omitted for brevity)
        return 'Seratus lima puluh ribu rupiah'; // Example
    }
}
```

### 5.3 Arrears Service

```php
// app/Services/ArrearsService.php
class ArrearsService
{
    public function generateMonthlyObligations(): array
    {
        $month = now()->month;
        $year = now()->year;
        $generated = 0;
        $skipped = 0;

        // Get all active students
        $students = Student::with(['class', 'category'])
            ->where('status', 'active')
            ->get();

        // Get monthly fee types
        $monthlyFees = FeeType::where('is_monthly', true)
            ->where('is_active', true)
            ->get();

        foreach ($students as $student) {
            foreach ($monthlyFees as $feeType) {
                // Check if obligation already exists
                $exists = StudentObligation::where([
                    'student_id' => $student->id,
                    'fee_type_id' => $feeType->id,
                    'month' => $month,
                    'year' => $year,
                ])->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Get fee amount from matrix
                $feeMatrix = app(TransactionService::class)
                    ->getFeeMatrix($student->id, $feeType->id);

                if (!$feeMatrix) {
                    Log::warning("No fee matrix found for student {$student->id}, fee type {$feeType->id}");
                    $skipped++;
                    continue;
                }

                // Create obligation
                StudentObligation::create([
                    'student_id' => $student->id,
                    'fee_type_id' => $feeType->id,
                    'month' => $month,
                    'year' => $year,
                    'amount' => $feeMatrix->amount,
                ]);

                $generated++;
            }
        }

        return compact('generated', 'skipped', 'month', 'year');
    }

    public function getArrearsReport(array $filters = []): Collection
    {
        $query = StudentObligation::with(['student.class', 'feeType'])
            ->where('is_paid', false)
            ->orderBy('year')
            ->orderBy('month');

        if (!empty($filters['class_id'])) {
            $query->whereHas('student', fn($q) => $q->where('class_id', $filters['class_id']));
        }

        if (!empty($filters['month'])) {
            $query->where('month', $filters['month']);
        }

        if (!empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        $obligations = $query->get();

        // Group by student
        return $obligations->groupBy('student_id')->map(function ($items, $studentId) {
            $student = $items->first()->student;
            
            return [
                'student' => [
                    'id' => $student->id,
                    'nis' => $student->nis,
                    'name' => $student->name,
                    'class' => $student->class->name,
                    'parent_whatsapp' => $student->parent_whatsapp,
                ],
                'arrears' => $items->map(function ($obligation) {
                    return [
                        'fee_type' => $obligation->feeType->name,
                        'month' => $obligation->month,
                        'year' => $obligation->year,
                        'amount' => $obligation->amount,
                        'days_overdue' => now()->diffInDays(
                            Carbon::create($obligation->year, $obligation->month, 15)
                        ),
                    ];
                }),
                'total_arrears' => $items->sum('amount'),
            ];
        })->values();
    }

    public function sendArrearsReminders(): array
    {
        $sent = 0;
        $failed = 0;

        // Get students with arrears > 1 month
        $studentsWithArrears = StudentObligation::with('student')
            ->where('is_paid', false)
            ->whereRaw("DATE(CONCAT(year, '-', month, '-01')) < ?", [now()->subMonth()])
            ->get()
            ->groupBy('student_id');

        foreach ($studentsWithArrears as $studentId => $obligations) {
            $student = $obligations->first()->student;
            $totalArrears = $obligations->sum('amount');

            $message = $this->buildReminderMessage($student, $obligations, $totalArrears);

            try {
                app(WhatsAppService::class)->send($student->parent_whatsapp, $message);
                
                // Log notification
                Notification::create([
                    'student_id' => $student->id,
                    'type' => 'arrears_reminder',
                    'message' => $message,
                    'recipient_phone' => $student->parent_whatsapp,
                    'whatsapp_status' => 'sent',
                    'whatsapp_sent_at' => now(),
                ]);

                $sent++;
            } catch (\Exception $e) {
                Log::error("Failed to send arrears reminder to student {$student->id}: " . $e->getMessage());
                
                Notification::create([
                    'student_id' => $student->id,
                    'type' => 'arrears_reminder',
                    'message' => $message,
                    'recipient_phone' => $student->parent_whatsapp,
                    'whatsapp_status' => 'failed',
                    'whatsapp_response' => $e->getMessage(),
                ]);

                $failed++;
            }
        }

        return compact('sent', 'failed');
    }

    protected function buildReminderMessage($student, $obligations, $totalArrears): string
    {
        $details = $obligations->map(function ($o) {
            return "- {$o->feeType->name} " . date('F Y', strtotime("{$o->year}-{$o->month}-01")) . 
                   ": Rp " . number_format($o->amount, 0, ',', '.');
        })->join("\n");

        return <<<MSG
        *PENGINGAT TUNGGAKAN*
        
        Yth. Orang Tua/Wali dari:
        Nama: {$student->name}
        NIS: {$student->nis}
        Kelas: {$student->class->name}
        
        Terdapat tunggakan pembayaran:
        {$details}
        
        Total: Rp {total}
        
        Mohon segera melakukan pembayaran. Terima kasih.
        MSG;
    }
}
```

### 5.4 Report Service

```php
// app/Services/ReportService.php
class ReportService
{
    public function getDailyReport(string $date): array
    {
        $transactions = Transaction::whereDate('transaction_date', $date)
            ->where('status', 'completed')
            ->with('items.feeType')
            ->get();

        $income = $transactions->where('transaction_type', 'income');
        $expense = $transactions->where('transaction_type', 'expense');

        return [
            'date' => $date,
            'summary' => [
                'total_income' => $income->sum('total_amount'),
                'total_expense' => $expense->sum('total_amount'),
                'net_balance' => $income->sum('total_amount') - $expense->sum('total_amount'),
                'transaction_count' => $transactions->count(),
            ],
            'income_breakdown' => $this->getIncomeBreakdown($income),
            'expense_breakdown' => $this->getExpenseBreakdown($expense),
            'transactions' => $transactions,
        ];
    }

    public function getMonthlyReport(int $month, int $year): array
    {
        $transactions = Transaction::whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->where('status', 'completed')
            ->with('items.feeType')
            ->get();

        $income = $transactions->where('transaction_type', 'income');
        $expense = $transactions->where('transaction_type', 'expense');

        return [
            'month' => $month,
            'year' => $year,
            'summary' => [
                'total_income' => $income->sum('total_amount'),
                'total_expense' => $expense->sum('total_amount'),
                'net_balance' => $income->sum('total_amount') - $expense->sum('total_amount'),
                'transaction_count' => $transactions->count(),
            ],
            'income_breakdown' => $this->getIncomeBreakdown($income),
            'expense_breakdown' => $this->getExpenseBreakdown($expense),
            'daily_stats' => $this->getDailyStats($transactions, $month, $year),
            'chart_data' => $this->getChartData($income, $expense),
        ];
    }

    protected function getIncomeBreakdown(Collection $transactions): array
    {
        return $transactions->flatMap->items
            ->groupBy('fee_type_id')
            ->map(function ($items) {
                return [
                    'fee_type' => $items->first()->feeType->name,
                    'count' => $items->count(),
                    'amount' => $items->sum('amount'),
                ];
            })
            ->values()
            ->toArray();
    }

    protected function getExpenseBreakdown(Collection $transactions): array
    {
        return $transactions->map(function ($transaction) {
            return [
                'date' => $transaction->transaction_date,
                'description' => $transaction->notes,
                'amount' => $transaction->total_amount,
            ];
        })->toArray();
    }

    protected function getDailyStats(Collection $transactions, int $month, int $year): array
    {
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $stats = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day);
            $dayTransactions = $transactions->filter(fn($t) => 
                Carbon::parse($t->transaction_date)->isSameDay($date)
            );

            $stats[] = [
                'date' => $date->format('Y-m-d'),
                'income' => $dayTransactions->where('transaction_type', 'income')->sum('total_amount'),
                'expense' => $dayTransactions->where('transaction_type', 'expense')->sum('total_amount'),
            ];
        }

        return $stats;
    }

    protected function getChartData(Collection $income, Collection $expense): array
    {
        return [
            'labels' => ['Pemasukan', 'Pengeluaran'],
            'datasets' => [
                [
                    'label' => 'Total',
                    'data' => [
                        $income->sum('total_amount'),
                        $expense->sum('total_amount'),
                    ],
                    'backgroundColor' => ['#10b981', '#ef4444'],
                ],
            ],
        ];
    }
}
```

---

## 6. SECURITY REQUIREMENTS

### 6.0 Financial Immutability Principle

**CRITICAL BUSINESS RULE:** Financial transactions in this system follow basic accounting principles where transactions are immutable once recorded.

#### 6.0.1 Core Principles

```yaml
Transaction Immutability:
  - Transactions CANNOT be edited after creation
  - Transactions CANNOT be physically deleted
  - All corrections must create audit trail
  
Correction Methodology:
  1. Cancel original transaction (status = 'cancelled')
  2. Create new correcting transaction
  3. Both transactions remain in database
  4. Audit log captures the relationship
  
Why This Matters:
  - Accounting standards compliance
  - Audit trail integrity
  - Legal requirements
  - Fraud prevention
```

#### 6.0.2 Implementation

```php
// ❌ NEVER DO THIS
public function update(Request $request, Transaction $transaction)
{
    $transaction->update($request->all()); // FORBIDDEN!
}

public function destroy(Transaction $transaction)
{
    $transaction->delete(); // FORBIDDEN!
}

// ✅ CORRECT APPROACH
public function cancel(Request $request, Transaction $transaction)
{
    DB::beginTransaction();
    
    try {
        // Mark as cancelled
        $transaction->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id(),
            'cancellation_reason' => $request->reason,
        ]);
        
        // Revert student obligations
        $this->revertObligations($transaction);
        
        // Generate cancelled receipt (with watermark)
        $this->regenerateCancelledReceipt($transaction);
        
        // Audit log automatically captures this
        
        DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dibatalkan',
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

#### 6.0.3 Database Constraints

```sql
-- Prevent accidental deletion at database level
ALTER TABLE transactions ADD CONSTRAINT prevent_delete 
CHECK (status IN ('completed', 'cancelled'));

-- Trigger to prevent updates on critical fields
CREATE OR REPLACE FUNCTION prevent_transaction_update()
RETURNS TRIGGER AS $$
BEGIN
    IF OLD.status = 'completed' AND NEW.status = 'completed' THEN
        IF OLD.total_amount != NEW.total_amount 
           OR OLD.transaction_date != NEW.transaction_date
           OR OLD.student_id != NEW.student_id THEN
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

#### 6.0.4 Soft Delete Policy Matrix

| Table | Soft Delete | Reason | Alternative |
|-------|-------------|--------|-------------|
| **students** | ✅ YES | Historical integrity, student may return | `status = 'graduated'` |
| **transactions** | ❌ NO | Financial immutability | `status = 'cancelled'` |
| **transaction_items** | ❌ NO | Child of immutable transaction | Cancel parent transaction |
| **users** | ✅ YES | Account lifecycle, re-activation possible | `is_active = false` |
| **fee_matrix** | ✅ YES | Historical tariff records needed | `is_active = false` |
| **classes** | ✅ YES | Historical class records | `is_active = false` |
| **fee_types** | ✅ YES | May be reactivated | `is_active = false` |
| **student_categories** | ❌ NO | Referenced by active students | Mark inactive instead |
| **student_obligations** | ❌ NO | Payment history | `is_paid` flag only |
| **notifications** | ✅ YES | Cleanup old notifications | Auto-delete after 6 months |
| **activity_log** | ❌ NO | Permanent audit trail | Never delete |

**Implementation:**

```php
// Models with soft delete
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
}

// Models WITHOUT soft delete (use status flags)
class Transaction extends Model
{
    // NO SoftDeletes trait
    
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    
    protected $fillable = ['status', 'cancelled_at', 'cancelled_by'];
    
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
```

### 6.1 Input Validation & Sanitization

```php
// Global validation rules
class BaseRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        // Strip HTML tags from text inputs
        $input = $this->all();
        
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                $value = strip_tags($value);
                $value = trim($value);
            }
        });

        $this->replace($input);
    }
}

// SQL Injection Prevention
// Always use Eloquent ORM or parameter binding
// ❌ BAD:
$users = DB::select("SELECT * FROM users WHERE email = '" . $email . "'");

// ✅ GOOD:
$users = DB::select("SELECT * FROM users WHERE email = ?", [$email]);
$users = User::where('email', $email)->get();

// XSS Prevention in Blade
// Always use {{ }} instead of {!! !!}
// ❌ BAD:
{!! $user->bio !!}

// ✅ GOOD:
{{ $user->bio }}

// CSRF Protection (Laravel default)
<form method="POST">
    @csrf
    <!-- form fields -->
</form>
```

### 6.2 Password Policy

```php
// config/auth.php
'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
    ],
],

// app/Rules/StrongPassword.php
class StrongPassword implements Rule
{
    public function passes($attribute, $value)
    {
        // Min 8 characters, at least 1 uppercase, 1 lowercase, 1 number, 1 special char
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $value);
    }

    public function message()
    {
        return 'Password harus minimal 8 karakter, terdiri dari huruf besar, huruf kecil, angka, dan simbol.';
    }
}

// Usage in RegisterRequest
'password' => ['required', 'confirmed', new StrongPassword],
```

### 6.3 Rate Limiting

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // ...
        'throttle:web',
    ],
    
    'api' => [
        // ...
        'throttle:api',
    ],
];

// config/cache.php - Rate limit defaults
'limiter' => [
    'login' => [
        'maxAttempts' => 5,
        'decayMinutes' => 15,
    ],
    'api' => [
        'maxAttempts' => 100,
        'decayMinutes' => 1,
    ],
],

// Custom rate limiting in routes
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/api/transactions', [TransactionController::class, 'store']);
});
```

### 6.4 Session Security

```php
// config/session.php
return [
    'lifetime' => 120, // 2 hours
    'expire_on_close' => false,
    'encrypt' => true,
    'http_only' => true,
    'secure' => env('SESSION_SECURE_COOKIE', true), // HTTPS only
    'same_site' => 'lax',
];

// Auto logout inactive users (app/Http/Middleware/CheckInactivity.php)
class CheckInactivity
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $lastActivity = session('last_activity');
            
            if ($lastActivity && (time() - $lastActivity > 7200)) { // 2 hours
                Auth::logout();
                session()->flush();
                return redirect()->route('login')->with('message', 'Sesi Anda telah berakhir');
            }
            
            session(['last_activity' => time()]);
        }

        return $next($request);
    }
}
```

### 6.5 File Upload Security

```php
// config/filesystems.php
'disks' => [
    'receipts' => [
        'driver' => 'local',
        'root' => storage_path('app/public/receipts'),
        'visibility' => 'public',
    ],
    'backups' => [
        'driver' => 'local',
        'root' => storage_path('app/backups'),
        'visibility' => 'private',
    ],
],

// File upload validation
'proof' => [
    'required',
    'file',
    'mimes:jpg,jpeg,png,pdf',
    'max:2048', // 2MB
],

// Secure file storage
$path = $request->file('proof')->store('expense-proofs/' . date('Y/m'), 'public');

// Prevent directory traversal
$filename = basename($request->input('filename')); // Strip path components
```

### 6.6 Database Security

```php
// .env.production
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=school_finance
DB_USERNAME=school_user  // NOT root/postgres
DB_PASSWORD=<strong-random-password>

// Database user should have minimal privileges
GRANT SELECT, INSERT, UPDATE, DELETE ON school_finance.* TO 'school_user'@'localhost';
// DO NOT GRANT DROP, ALTER, CREATE

// Backup encryption
// config/backup.php
'destination' => [
    'disks' => ['backups'],
    'encryption' => 'AES-256', // Encrypt backup files
],
```

### 6.7 Configuration-as-Code Principle

**CRITICAL RULE:** All application settings must be defined in code, not hardcoded.

#### Why This Matters

```php
// ❌ BAD - Hardcoded values scattered in code
public function sendNotification($student)
{
    $schoolName = "Madrasah Ibtidaiyah Al-Hikmah"; // Hardcoded!
    $message = "Pembayaran di {$schoolName}...";
}

// ✅ GOOD - Centralized configuration
public function sendNotification($student)
{
    $schoolName = getSetting('school_name');
    $message = "Pembayaran di {$schoolName}...";
}
```

#### Configuration Sources Hierarchy

```
1. Database (settings table)        - Highest priority, user-editable via UI
2. Environment (.env)                - Environment-specific configs
3. Config files (config/*.php)       - Application defaults
4. Code constants                    - Hardcoded fallbacks (avoid!)
```

#### Implementation

**1. Database Settings Table:**

```php
// database/migrations/xxxx_create_settings_table.php
Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->string('key', 100)->unique();
    $table->text('value')->nullable();
    $table->string('type', 20)->default('string'); // string, number, boolean, json
    $table->string('category', 50)->default('general'); // Grouping
    $table->text('description')->nullable();
    $table->boolean('is_public')->default(false); // Expose to frontend?
    $table->timestamps();
});

// database/seeders/SettingSeeder.php
class SettingSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            // School Information
            [
                'key' => 'school_name',
                'value' => 'Madrasah Ibtidaiyah',
                'type' => 'string',
                'category' => 'school',
                'description' => 'Nama sekolah yang ditampilkan di header dan kwitansi',
                'is_public' => true,
            ],
            [
                'key' => 'school_address',
                'value' => 'Jl. Pendidikan No. 123, Jakarta',
                'type' => 'string',
                'category' => 'school',
                'description' => 'Alamat lengkap sekolah',
                'is_public' => true,
            ],
            [
                'key' => 'school_phone',
                'value' => '021-12345678',
                'type' => 'string',
                'category' => 'school',
                'description' => 'Nomor telepon sekolah',
                'is_public' => true,
            ],
            [
                'key' => 'school_logo',
                'value' => null,
                'type' => 'string',
                'category' => 'school',
                'description' => 'Path to logo file (relative to storage/app/public)',
                'is_public' => true,
            ],
            
            // Receipt Settings
            [
                'key' => 'receipt_footer_text',
                'value' => 'Terima kasih atas pembayarannya. Semoga berkah.',
                'type' => 'string',
                'category' => 'receipt',
                'description' => 'Teks di footer kwitansi',
                'is_public' => false,
            ],
            [
                'key' => 'receipt_show_logo',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'receipt',
                'description' => 'Tampilkan logo di kwitansi',
                'is_public' => false,
            ],
            
            // WhatsApp Settings
            [
                'key' => 'whatsapp_gateway_url',
                'value' => '',
                'type' => 'string',
                'category' => 'notification',
                'description' => 'URL WhatsApp gateway API',
                'is_public' => false,
            ],
            [
                'key' => 'whatsapp_api_key',
                'value' => '',
                'type' => 'string',
                'category' => 'notification',
                'description' => 'API key untuk WhatsApp gateway',
                'is_public' => false,
            ],
            [
                'key' => 'whatsapp_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'category' => 'notification',
                'description' => 'Aktifkan notifikasi WhatsApp',
                'is_public' => false,
            ],
            [
                'key' => 'notification_payment_template',
                'value' => "Yth. Orang Tua/Wali {student_name}\n\nPembayaran {fee_type} sejumlah {amount} telah diterima.\n\nTerima kasih.",
                'type' => 'string',
                'category' => 'notification',
                'description' => 'Template notifikasi pembayaran (gunakan {student_name}, {fee_type}, {amount})',
                'is_public' => false,
            ],
            
            // Arrears Settings
            [
                'key' => 'arrears_reminder_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'arrears',
                'description' => 'Aktifkan reminder tunggakan otomatis',
                'is_public' => false,
            ],
            [
                'key' => 'arrears_reminder_day',
                'value' => 'monday',
                'type' => 'string',
                'category' => 'arrears',
                'description' => 'Hari pengiriman reminder (monday, tuesday, dll)',
                'is_public' => false,
            ],
            [
                'key' => 'arrears_threshold_months',
                'value' => '1',
                'type' => 'number',
                'category' => 'arrears',
                'description' => 'Kirim reminder jika tunggakan lebih dari X bulan',
                'is_public' => false,
            ],
            
            // System Settings
            [
                'key' => 'academic_year',
                'value' => '2025/2026',
                'type' => 'string',
                'category' => 'system',
                'description' => 'Tahun ajaran aktif',
                'is_public' => true,
            ],
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'category' => 'system',
                'description' => 'Mode maintenance',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
```

**2. Setting Model with Helper:**

```php
// app/Models/Setting.php
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'category', 'description', 'is_public'];
    
    protected static $cache = [];
    
    /**
     * Get setting value with type casting
     */
    public static function get(string $key, $default = null)
    {
        // Check memory cache first
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }
        
        $value = self::castValue($setting->value, $setting->type);
        
        // Cache in memory
        self::$cache[$key] = $value;
        
        return $value;
    }
    
    /**
     * Set setting value
     */
    public static function set(string $key, $value): bool
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return false;
        }
        
        $setting->value = is_array($value) ? json_encode($value) : $value;
        $setting->save();
        
        // Clear cache
        unset(self::$cache[$key]);
        Cache::forget("setting.{$key}");
        
        return true;
    }
    
    /**
     * Cast value based on type
     */
    protected static function castValue($value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
                return is_numeric($value) ? (float) $value : 0;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }
    
    /**
     * Get all public settings (for frontend)
     */
    public static function getPublic(): array
    {
        return Cache::remember('settings.public', 3600, function () {
            return static::where('is_public', true)
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => self::castValue($setting->value, $setting->type)];
                })
                ->toArray();
        });
    }
}

// app/Helpers/helpers.php
if (!function_exists('getSetting')) {
    function getSetting(string $key, $default = null)
    {
        return \App\Models\Setting::get($key, $default);
    }
}

if (!function_exists('setSetting')) {
    function setSetting(string $key, $value): bool
    {
        return \App\Models\Setting::set($key, $value);
    }
}
```

**3. Settings Management UI:**

```php
// app/Http/Controllers/SettingController.php
class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::orderBy('category')->orderBy('key')->get();
        $grouped = $settings->groupBy('category');
        
        return view('admin.settings.index', compact('grouped'));
    }
    
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string|max:1000',
        ]);
        
        DB::beginTransaction();
        
        try {
            foreach ($validated['settings'] as $key => $value) {
                Setting::set($key, $value);
            }
            
            // Clear all settings cache
            Cache::forget('settings.public');
            
            DB::commit();
            
            return redirect()->back()->with('success', 'Settings updated successfully');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update settings');
        }
    }
}
```

**4. Usage in Application:**

```php
// In Controllers
$schoolName = getSetting('school_name', 'Default School');

// In Services
class ReceiptService
{
    public function generate(Transaction $transaction)
    {
        $data = [
            'school_name' => getSetting('school_name'),
            'school_address' => getSetting('school_address'),
            'school_logo' => getSetting('school_logo'),
            'footer_text' => getSetting('receipt_footer_text'),
            'show_logo' => getSetting('receipt_show_logo', true),
        ];
        
        return PDF::loadView('receipts.template', $data);
    }
}

// In Blade Views
<h1>{{ getSetting('school_name') }}</h1>

// In Config Files (for defaults)
// config/app.php
'school' => [
    'name' => env('SCHOOL_NAME', 'Madrasah Ibtidaiyah'),
    'address' => env('SCHOOL_ADDRESS', ''),
],
```

**5. Validation Rules:**

```yaml
Rules for Settings:
  1. All default settings MUST exist in SettingSeeder
  2. NO hardcoded values in business logic
  3. Use getSetting() helper everywhere
  4. Document all settings in seeder
  5. Sensitive settings (API keys) should NOT be is_public=true
  6. Use appropriate type casting (boolean, number, json)
  7. Provide sensible defaults
```

**Benefits:**

- ✅ No code changes needed for configuration updates
- ✅ Settings can be changed via admin UI
- ✅ Environment-specific configs in .env
- ✅ Default values always available
- ✅ Type-safe value retrieval
- ✅ Cache-optimized
- ✅ Audit trail (Laravel activity log can track changes)

---

## 7. TESTING STRATEGY

### 7.1 Unit Tests

```php
// tests/Unit/TransactionServiceTest.php
use Tests\TestCase;
use App\Services\TransactionService;

class TransactionServiceTest extends TestCase
{
    protected TransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TransactionService::class);
    }

    /** @test */
    public function it_generates_valid_transaction_number()
    {
        $number = $this->service->generateTransactionNumber('income');
        
        $this->assertMatchesRegularExpression('/^NF-\d{4}-\d{6}$/', $number);
    }

    /** @test */
    public function it_creates_income_transaction_with_items()
    {
        $student = Student::factory()->create();
        $feeType = FeeType::factory()->create(['is_monthly' => true]);
        
        $data = [
            'transaction_date' => today(),
            'student_id' => $student->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'fee_type_id' => $feeType->id,
                    'amount' => 150000,
                    'month' => 2,
                    'year' => 2026,
                    'description' => 'SPP Februari 2026',
                ],
            ],
        ];

        $transaction = $this->service->createIncome($data);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'total_amount' => 150000,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $transaction->id,
            'fee_type_id' => $feeType->id,
            'amount' => 150000,
        ]);
    }

    /** @test */
    public function it_updates_student_obligation_on_payment()
    {
        $student = Student::factory()->create();
        $feeType = FeeType::factory()->create(['is_monthly' => true]);
        
        $obligation = StudentObligation::factory()->create([
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'month' => 2,
            'year' => 2026,
            'is_paid' => false,
        ]);

        $data = [
            'transaction_date' => today(),
            'student_id' => $student->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'fee_type_id' => $feeType->id,
                    'amount' => 150000,
                    'month' => 2,
                    'year' => 2026,
                ],
            ],
        ];

        $this->service->createIncome($data);

        $obligation->refresh();
        
        $this->assertTrue($obligation->is_paid);
        $this->assertNotNull($obligation->paid_at);
    }
}

// tests/Unit/ArrearsServiceTest.php
class ArrearsServiceTest extends TestCase
{
    /** @test */
    public function it_generates_monthly_obligations()
    {
        $student = Student::factory()->create(['status' => 'active']);
        $feeType = FeeType::factory()->create(['is_monthly' => true]);
        
        FeeMatrix::factory()->create([
            'fee_type_id' => $feeType->id,
            'class_id' => $student->class_id,
            'category_id' => $student->category_id,
            'amount' => 150000,
            'effective_from' => now()->subMonth(),
        ]);

        $service = app(ArrearsService::class);
        $result = $service->generateMonthlyObligations();

        $this->assertGreaterThan(0, $result['generated']);
        $this->assertDatabaseHas('student_obligations', [
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'month' => now()->month,
            'year' => now()->year,
        ]);
    }
}
```

### 7.2 Feature Tests

```php
// tests/Feature/TransactionTest.php
class TransactionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_bendahara_can_create_income_transaction()
    {
        $user = User::factory()->create();
        $user->assignRole('bendahara');
        
        $student = Student::factory()->create();
        $feeType = FeeType::factory()->create();

        $response = $this->actingAs($user)->post('/api/transactions/income', [
            'transaction_date' => today()->format('Y-m-d'),
            'student_id' => $student->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'fee_type_id' => $feeType->id,
                    'amount' => 150000,
                    'description' => 'SPP',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => ['id', 'transaction_number', 'receipt_url'],
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_create_transaction()
    {
        $user = User::factory()->create();
        $user->assignRole('auditor'); // Auditor = read-only

        $response = $this->actingAs($user)->post('/api/transactions/income', []);

        $response->assertStatus(403);
    }

    /** @test */
    public function transaction_validation_fails_for_inactive_student()
    {
        $user = User::factory()->create();
        $user->assignRole('bendahara');
        
        $student = Student::factory()->create(['status' => 'graduated']);

        $response = $this->actingAs($user)->post('/api/transactions/income', [
            'transaction_date' => today()->format('Y-m-d'),
            'student_id' => $student->id,
            'payment_method' => 'cash',
            'items' => [
                ['fee_type_id' => 1, 'amount' => 150000],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['student_id']);
    }
}

// tests/Feature/DashboardTest.php
class DashboardTest extends TestCase
{
    /** @test */
    public function dashboard_displays_correct_summary_data()
    {
        $user = User::factory()->create();
        $user->assignRole('bendahara');

        // Create sample transactions
        Transaction::factory()->count(5)->create([
            'transaction_type' => 'income',
            'transaction_date' => today(),
            'total_amount' => 150000,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('summary', function ($summary) {
            return $summary['total_income_today'] == 750000;
        });
    }
}
```

### 7.3 Browser Tests (Laravel Dusk)

```php
// tests/Browser/LoginTest.php
class LoginTest extends DuskTestCase
{
    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login')
                    ->type('email', $user->email)
                    ->type('password', 'password123')
                    ->press('Login')
                    ->assertPathIs('/dashboard')
                    ->assertSee('Dashboard');
        });
    }

    /** @test */
    public function user_sees_error_with_invalid_credentials()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'wrong@example.com')
                    ->type('password', 'wrongpassword')
                    ->press('Login')
                    ->assertPathIs('/login')
                    ->assertSee('Email atau password salah');
        });
    }
}
```

### 7.4 Test Coverage Goals

```yaml
Target Coverage: 60% minimum

Priority Coverage:
  - Services: 80%+ (TransactionService, ArrearsService, ReceiptService)
  - Controllers: 60%+ (CRUD operations, validation)
  - Models: 50%+ (Relationships, scopes)
  - Commands: 70%+ (Cron jobs)

Testing Tools:
  - PHPUnit: Unit & Feature tests
  - Laravel Dusk: Browser tests
  - Pest (optional): Modern testing framework
  - PHPStan: Static analysis (level 5+)

CI/CD Integration:
  - Run tests on every commit
  - Block merge if tests fail
  - Generate coverage report
```

---

## 8. DEPLOYMENT GUIDE

### 8.1 Server Requirements

```yaml
Operating System: Ubuntu 22.04 LTS (recommended)
Web Server: Nginx 1.22+
PHP: 8.2+
Database: PostgreSQL 15+ (or MySQL 8.0+)
Memory: 2GB minimum, 4GB recommended
Storage: 50GB minimum (20GB app + 30GB backups)
SSL: Let's Encrypt (free) or commercial certificate

PHP Extensions:
  - php8.2-fpm
  - php8.2-pgsql (or php8.2-mysql)
  - php8.2-mbstring
  - php8.2-xml
  - php8.2-bcmath
  - php8.2-curl
  - php8.2-gd
  - php8.2-zip
  - php8.2-intl
```

### 8.2 Server Setup Script

```bash
#!/bin/bash
# deploy-setup.sh

# Update system
sudo apt update && sudo apt upgrade -y

# Install Nginx
sudo apt install -y nginx

# Install PHP 8.2
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2-fpm php8.2-pgsql php8.2-mbstring php8.2-xml \
  php8.2-bcmath php8.2-curl php8.2-gd php8.2-zip php8.2-intl php8.2-cli

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Create database and user
sudo -u postgres psql << EOF
CREATE DATABASE school_finance;
CREATE USER school_user WITH ENCRYPTED PASSWORD 'your-strong-password';
GRANT ALL PRIVILEGES ON DATABASE school_finance TO school_user;
\c school_finance
GRANT ALL ON SCHEMA public TO school_user;
EOF

# Install Node.js (for npm)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Configure Nginx
sudo tee /etc/nginx/sites-available/school-finance << 'EOF'
server {
    listen 80;
    server_name keuangan-mi.example.com;
    root /var/www/school-finance/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

sudo ln -s /etc/nginx/sites-available/school-finance /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx

# Install SSL (Let's Encrypt)
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d keuangan-mi.example.com

echo "Server setup complete!"
```

### 8.3 Application Deployment

```bash
#!/bin/bash
# deploy-app.sh

# Navigate to web root
cd /var/www

# Clone repository (or use FTP/SFTP)
sudo git clone https://github.com/your-org/school-finance.git
cd school-finance

# Set permissions
sudo chown -R www-data:www-data /var/www/school-finance
sudo chmod -R 755 /var/www/school-finance
sudo chmod -R 775 storage bootstrap/cache

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Environment setup
cp .env.example .env
nano .env  # Edit database credentials, APP_KEY, etc.

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed initial data (roles, permissions, settings)
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=SettingSeeder

# Link storage
php artisan storage:link

# Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Setup queue worker (using Supervisor)
sudo apt install -y supervisor

sudo tee /etc/supervisor/conf.d/school-finance-worker.conf << EOF
[program:school-finance-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/school-finance/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/school-finance/storage/logs/worker.log
stopwaitsecs=3600
EOF

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start school-finance-worker:*

# Setup cron jobs
sudo crontab -e
# Add these lines:
# 0 0 1 * * cd /var/www/school-finance && php artisan obligations:generate >> /dev/null 2>&1
# 0 9 * * 1 cd /var/www/school-finance && php artisan arrears:remind >> /dev/null 2>&1
# 0 2 * * * cd /var/www/school-finance && php artisan backup:run >> /dev/null 2>&1

echo "Application deployment complete!"
```

### 8.4 Production Environment Configuration

```env
# .env.production

APP_NAME="Sistem Keuangan MI"
APP_ENV=production
APP_KEY=base64:...  # Generated by php artisan key:generate
APP_DEBUG=false  # MUST be false in production
APP_URL=https://keuangan-mi.example.com

LOG_CHANNEL=stack
LOG_LEVEL=error  # Only log errors in production

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=school_finance
DB_USERNAME=school_user
DB_PASSWORD=your-strong-password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# WhatsApp Gateway
WHATSAPP_GATEWAY_URL=https://api.whatsapp-gateway.com
WHATSAPP_API_KEY=your-api-key

# Backup
BACKUP_DISK=backups
BACKUP_ENCRYPTION_PASSWORD=your-encryption-key
```

### 8.5 Monitoring & Logging

```bash
# Install monitoring tools
sudo apt install -y htop iotop nethogs

# Setup log rotation
sudo tee /etc/logrotate.d/school-finance << EOF
/var/www/school-finance/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 0640 www-data www-data
}
EOF

# Monitor application logs
tail -f /var/www/school-finance/storage/logs/laravel.log

# Monitor Nginx access logs
tail -f /var/log/nginx/access.log

# Monitor PHP-FPM logs
tail -f /var/log/php8.2-fpm.log

# Database monitoring
sudo -u postgres psql -c "SELECT * FROM pg_stat_activity;"

# Disk usage
df -h

# Memory usage
free -h
```

### 8.6 Backup & Recovery

```php
// config/backup.php
return [
    'backup' => [
        'name' => env('APP_NAME', 'school-finance'),
        
        'source' => [
            'files' => [
                'include' => [
                    base_path(),
                ],
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                    base_path('storage/framework/cache'),
                    base_path('storage/framework/sessions'),
                    base_path('storage/framework/views'),
                ],
            ],
            
            'databases' => [
                'pgsql',
            ],
        ],
        
        'destination' => [
            'filename_prefix' => 'backup-',
            'disks' => [
                'backups',
            ],
        ],
        
        'temporary_directory' => storage_path('app/backup-temp'),
        
        'password' => env('BACKUP_ENCRYPTION_PASSWORD'),
        
        'encryption' => 'default',
    ],
    
    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
        ],
        
        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,
        
        'mail' => [
            'to' => 'admin@example.com',
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                'name' => env('MAIL_FROM_NAME', 'School Finance Backup'),
            ],
        ],
    ],
    
    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'school-finance'),
            'disks' => ['backups'],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],
    
    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,
        
        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
    ],
];
```

**Manual Backup:**
```bash
# Create backup manually
php artisan backup:run

# List backups
php artisan backup:list

# Download backup
scp user@server:/var/www/school-finance/storage/app/backups/backup-2026-02-09.zip ./
```

**Recovery Process:**
```bash
# 1. Extract backup
unzip backup-2026-02-09.zip

# 2. Restore database
psql -U school_user -d school_finance < database-dump.sql

# 3. Restore files
cp -r backup-files/* /var/www/school-finance/

# 4. Fix permissions
sudo chown -R www-data:www-data /var/www/school-finance
sudo chmod -R 755 /var/www/school-finance

# 5. Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 6. Test application
php artisan serve  # Or access via browser
```

### 8.7 Disaster Recovery Runbook

**CRITICAL: Keep this document accessible offline (printed copy in safe)**

#### Scenario 1: Database Corruption

```
Symptoms:
- Database connection errors
- Data inconsistencies
- Application errors on every page

Recovery Steps:
1. Put application into maintenance mode
   ssh production-server
   cd /var/www/school-finance
   php artisan down --message="Database recovery in progress"

2. Assess damage
   sudo -u postgres psql school_finance
   \dt  # List tables
   SELECT COUNT(*) FROM transactions;  # Check critical tables

3. If corruption is severe, restore from backup
   # Download latest backup
   cd /var/www/school-finance/storage/app/backups
   ls -lth  # Find latest backup
   
4. Stop application & database connections
   sudo supervisorctl stop school-finance-worker:*
   
5. Restore database
   sudo -u postgres dropdb school_finance
   sudo -u postgres createdb school_finance
   sudo -u postgres psql school_finance < /path/to/backup.sql
   
6. Verify data integrity
   sudo -u postgres psql school_finance
   SELECT COUNT(*) FROM transactions;
   SELECT COUNT(*) FROM students;
   # Compare with expected counts
   
7. Run migrations (if backup is old)
   php artisan migrate --force
   
8. Restart services
   sudo supervisorctl start school-finance-worker:*
   
9. Exit maintenance mode
   php artisan up
   
10. Verify critical functions
    - Login works
    - Dashboard loads
    - Can create transaction
    - Reports generate

11. Monitor for 1 hour
    tail -f storage/logs/laravel.log
    
12. Document incident
    Create post-mortem report

Time Estimate: 30-60 minutes
RTO (Recovery Time Objective): < 1 hour
RPO (Recovery Point Objective): < 24 hours (daily backups)
```

#### Scenario 2: Server Crash / Data Center Outage

```
Symptoms:
- Website unreachable
- SSH connection fails
- Hosting provider reports outage

Recovery Steps:
1. Assess situation
   - Contact hosting provider
   - Check status page
   - Estimate downtime

2. If extended outage (> 4 hours), activate DR site
   # Restore to backup server
   
3. Setup new server (if needed)
   # Follow deployment guide section 8.2
   
4. Restore latest backup
   # Download from offsite backup (S3, Google Drive)
   scp backup-latest.zip new-server:/tmp/
   
5. Deploy application
   # Follow deployment steps 8.3
   
6. Update DNS
   # Point domain to new server IP
   # TTL typically 300s (5 min propagation)
   
7. Test thoroughly
   
8. Monitor closely
   
9. Post-mortem
   - What happened?
   - How to prevent?
   - Update runbook

Time Estimate: 2-4 hours
```

#### Scenario 3: Ransomware / Security Breach

```
Symptoms:
- Files encrypted
- Unusual admin users
- Suspicious transactions
- Database modified

IMMEDIATE ACTIONS:
1. ISOLATE - Disconnect server from network
   sudo iptables -A INPUT -j DROP
   sudo iptables -A OUTPUT -j DROP
   
2. PRESERVE EVIDENCE
   # DO NOT delete logs
   # Take screenshots
   cp -r /var/log /evidence/
   cp -r storage/logs /evidence/
   
3. ASSESS SCOPE
   # Check file modifications
   find /var/www -type f -mtime -1 -ls
   
   # Check database for unauthorized changes
   SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 100;
   SELECT * FROM users WHERE created_at > NOW() - INTERVAL '24 hours';
   
4. NOTIFY
   - Inform management
   - Contact cybersecurity expert
   - Consider law enforcement (if severe)
   
5. RESTORE FROM CLEAN BACKUP
   # Use backup from BEFORE breach
   # Check backup dates carefully
   
6. SECURITY HARDENING
   - Change ALL passwords
   - Rotate ALL API keys
   - Update SSH keys
   - Patch system
   - Enable 2FA
   
7. AUDIT
   - Review all users
   - Review all transactions
   - Check for backdoors
   
8. MONITOR
   - Enable enhanced logging
   - Watch for unusual activity

Time Estimate: 1-3 days
DO NOT PAY RANSOM
```

#### Emergency Contacts

```yaml
Primary Contact:
  Name: [System Administrator]
  Phone: +62 XXX XXXX XXXX
  Email: admin@example.com

Backup Contact:
  Name: [Technical Lead]
  Phone: +62 XXX XXXX XXXX
  Email: tech@example.com

Hosting Provider:
  Name: [Provider Name]
  Support: support@hosting.com
  Phone: [Support Number]
  
Database Expert:
  Name: [DBA Name]
  Phone: [Number]
  
Security Consultant:
  Name: [Consultant]
  Phone: [Number]
```

#### Recovery Testing Schedule

```yaml
Monthly:
  - Test backup restoration (staging server)
  - Verify backup integrity
  - Check backup storage capacity

Quarterly:
  - Full disaster recovery drill
  - Simulate database corruption
  - Test failover procedures
  - Update runbook based on findings

Annually:
  - Review and update emergency contacts
  - Audit security measures
  - Review RTO/RPO targets
```

---

## 9. DEVELOPMENT ROADMAP

### Phase 1: Foundation (Weeks 1-2)

**Week 1: Setup & Database**
```
✓ Laravel 11 installation & Git setup
✓ Database schema creation & migrations
✓ ERD finalization
✓ Seeders for roles, permissions, settings
✓ Authentication with Laravel Breeze
✓ Main layout (sidebar, navbar, footer)
✓ Tailwind configuration & components
```

**Week 2: Core Infrastructure**
```
✓ Role-based middleware setup
✓ Base controllers & services structure
✓ Form request validation classes
✓ Error handling & logging setup
✓ API resource transformers
✓ Activity log integration
```

### Phase 2: Core Features (Weeks 3-8)

**Week 3-4: Master Data Module**
```
✓ Class CRUD (with academic year)
✓ Student Category CRUD (with discount)
✓ Fee Type CRUD (monthly flag)
✓ Fee Matrix management (grid UI, effective dates)
✓ Student CRUD (search, filter, soft delete)
✓ Excel import/export for students
✓ Validation & error handling
```

**Week 5-6: Transaction Module**
```
✓ Transaction number generation
✓ Income transaction (student selection, fee selection, multi-item)
✓ Expense transaction (file upload, categorization)
✓ Transaction list (filter, search, pagination)
✓ Transaction detail view
✓ Cancel transaction feature
✓ Transaction validation & business rules
```

**Week 7: Receipt & Obligation**
```
✓ PDF receipt generation service
✓ Receipt template design (Blade)
✓ Terbilang (number to words) helper
✓ Receipt download & reprint
✓ Student obligation auto-update
✓ Obligation generation command (cron)
```

**Week 8: Basic Reporting**
```
✓ Daily report (summary, breakdown, export)
✓ Monthly report (summary, charts, export)
✓ Arrears report (by class, by month)
✓ Chart.js integration
✓ Excel/PDF export
```

### Phase 3: Enhancement (Weeks 9-12)

**Week 9: Dashboard**
```
✓ Summary cards (income, expense, balance, arrears)
✓ Bar chart (6-month income trend)
✓ Pie chart (income breakdown)
✓ Recent transactions widget
✓ Performance optimization (caching)
```

**Week 10: WhatsApp Integration**
```
✓ WhatsApp gateway research & setup
✓ WhatsApp service implementation
✓ Payment success notification
✓ Arrears reminder notification
✓ Notification log & status tracking
✓ Manual retry failed notifications
```

**Week 11: User Management**
```
✓ User CRUD (assign role, deactivate)
✓ Password reset functionality
✓ Audit log viewer
✓ Permission testing
✓ Menu visibility by role
```

**Week 12: System Settings**
```
✓ School profile settings
✓ Logo upload
✓ Receipt customization
✓ WhatsApp configuration
✓ Backup management UI
✓ Manual backup trigger
```

### Phase 4: Testing & Deployment (Weeks 13-16)

**Week 13-14: Testing**
```
✓ Unit tests (Services, Models)
✓ Feature tests (Controllers, API)
✓ Browser tests (Dusk)
✓ Manual testing by role
✓ Edge case testing
✓ Performance testing
✓ Security audit
```

**Week 15: Pre-Production**
```
✓ Bug fixes (critical, high, medium)
✓ Code optimization
✓ Database indexing
✓ Query optimization
✓ Production environment setup
✓ SSL configuration
✓ Backup automation
```

**Week 16: Deployment & Training**
```
✓ Production deployment
✓ Smoke testing
✓ User manual creation
✓ Video tutorial recording
✓ User training sessions
✓ UAT with client
✓ Data migration (if needed)
✓ Go-live
✓ Post-launch monitoring
```

### Post-Launch Support (Weeks 17-20)

```
✓ Week 1-2: Intensive monitoring
✓ Week 3-4: Bug fixes & minor enhancements
✓ Month 2-3: Regular support & feature requests
✓ Performance optimization
✓ User feedback collection
✓ Documentation updates
```

---

## APPENDIX

### A. Error Handling Examples

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $exception)
{
    // Custom error pages
    if ($exception instanceof ModelNotFoundException) {
        return response()->view('errors.404', [], 404);
    }

    if ($exception instanceof AuthorizationException) {
        return response()->view('errors.403', [], 403);
    }

    // API error responses
    if ($request->expectsJson()) {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
        ], 500);
    }

    return parent::render($request, $exception);
}
```

### B. Helper Functions

```php
// app/Helpers/helpers.php

function formatRupiah($amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatDate($date, $format = 'd/m/Y'): string
{
    return Carbon::parse($date)->format($format);
}

function formatIndonesianDate($date): string
{
    Carbon::setLocale('id');
    return Carbon::parse($date)->translatedFormat('d F Y');
}

function getAcademicYear(): string
{
    $year = date('Y');
    $month = date('n');
    
    if ($month >= 7) {
        return $year . '/' . ($year + 1);
    }
    
    return ($year - 1) . '/' . $year;
}

function getSetting($key, $default = null)
{
    return \App\Models\Setting::where('key', $key)->value('value') ?? $default;
}
```

### C. Sample Data for Testing

```php
// database/factories/StudentFactory.php
class StudentFactory extends Factory
{
    public function definition()
    {
        return [
            'nis' => fake()->unique()->numerify('2024###'),
            'nisn' => fake()->unique()->numerify('##########'),
            'name' => fake()->name(),
            'class_id' => Class::factory(),
            'category_id' => StudentCategory::factory(),
            'gender' => fake()->randomElement(['L', 'P']),
            'birth_date' => fake()->date('Y-m-d', '-7 years'),
            'birth_place' => fake()->city(),
            'parent_name' => fake()->name(),
            'parent_phone' => '08' . fake()->numerify('##########'),
            'parent_whatsapp' => '628' . fake()->numerify('##########'),
            'address' => fake()->address(),
            'status' => 'active',
            'enrollment_date' => fake()->date('Y-m-d', '-3 years'),
        ];
    }
}
```

### D. Useful Commands

```bash
# Development
php artisan serve                    # Start dev server
php artisan migrate:fresh --seed     # Reset database
php artisan tinker                   # Laravel REPL
php artisan route:list               # List all routes
php artisan make:model Student -mfsc # Model + migration + factory + seeder + controller

# Testing
php artisan test                     # Run tests
php artisan test --coverage          # Test coverage
php artisan dusk                     # Browser tests

# Optimization
php artisan optimize                 # Cache everything
php artisan optimize:clear           # Clear all caches

# Queue & Cron
php artisan queue:work               # Start queue worker
php artisan queue:failed             # List failed jobs
php artisan queue:retry all          # Retry failed jobs
php artisan schedule:work            # Run scheduler (dev)

# Backup
php artisan backup:run               # Manual backup
php artisan backup:list              # List backups
php artisan backup:clean             # Clean old backups
```

---

## CHANGELOG

**Version 2.1 (Production-Ready Enhancement) - February 10, 2026**
- ✅ **Phase 1 Critical Features:**
  - Added Financial Immutability Principle with database constraints
  - Implemented Concurrency Safety with lockForUpdate() for transaction numbers
  - Complete Environment Separation Strategy (local/staging/production)
  - Soft Delete Policy Matrix for all tables
  - Health Check Endpoint with comprehensive system monitoring
  
- ✅ **Phase 2 Deployment Features:**
  - Database Migration Governance with detailed rules and examples
  - Configuration-as-Code implementation with Settings system
  - Disaster Recovery Runbook for critical scenarios
  - Role Escalation Protection with super_admin safeguards
  - Enhanced security monitoring and audit logging

**Version 2.0 (Refined) - February 9, 2026**
- ✅ Added comprehensive API specifications
- ✅ Enhanced database schema with indexes
- ✅ Detailed security requirements
- ✅ Complete testing strategy
- ✅ Production deployment guide
- ✅ Service implementations
- ✅ Error handling patterns
- ✅ Performance optimization guidelines

**Version 1.0 (Original) - February 9, 2026**
- Initial documentation

---

## IMPLEMENTATION CHECKLIST (Phase 1 & 2)

### Phase 1: Critical Features (Pre-Development)

```markdown
## Financial Immutability
- [ ] Add status column to transactions table
- [ ] Implement cancel() method in TransactionService
- [ ] Add database trigger to prevent transaction updates
- [ ] Create cancelled receipt watermark template
- [ ] Update all DELETE routes to use status flags
- [ ] Test cancellation flow end-to-end

## Concurrency Safety
- [ ] Update generateTransactionNumber() with lockForUpdate()
- [ ] Add database transaction wrapper
- [ ] Load test transaction creation (100 concurrent requests)
- [ ] Verify no duplicate transaction numbers generated
- [ ] Document race condition prevention in code comments

## Environment Separation
- [ ] Create .env.local template
- [ ] Create .env.staging template
- [ ] Create .env.production template
- [ ] Setup staging database (separate from production)
- [ ] Configure staging domain/subdomain
- [ ] Test deployment workflow (local → staging → production)
- [ ] Add environment indicator in UI

## Soft Delete Policy
- [ ] Add deleted_at to: students, users, fee_matrix, classes
- [ ] Remove SoftDeletes from: transactions, obligations, activity_log
- [ ] Update all models per policy matrix
- [ ] Add status flags where soft delete not used
- [ ] Test cascade behavior
- [ ] Update seeders to reflect policy

## Health Check Endpoint
- [ ] Create HealthCheckController
- [ ] Implement database, storage, queue, cache checks
- [ ] Add /health route (no auth required)
- [ ] Configure uptime monitoring service
- [ ] Test health check during system load
- [ ] Setup alerts for degraded status
```

### Phase 2: Deployment Features (Pre-Production)

```markdown
## Database Migration Governance
- [ ] Document migration rules in README
- [ ] Create migration naming convention guide
- [ ] Setup migration testing workflow
- [ ] Test rollback for all existing migrations
- [ ] Create migration checklist template
- [ ] Train team on migration best practices

## Configuration-as-Code
- [ ] Create settings table migration
- [ ] Implement Setting model with type casting
- [ ] Create SettingSeeder with all defaults
- [ ] Build settings management UI
- [ ] Replace all hardcoded values with getSetting()
- [ ] Test setting updates via admin panel
- [ ] Document all available settings

## Disaster Recovery Runbook
- [ ] Print physical copy of runbook
- [ ] Store in secure location (safe)
- [ ] Test database restoration procedure
- [ ] Test server migration scenario
- [ ] Document emergency contacts
- [ ] Schedule quarterly DR drills
- [ ] Update runbook after each incident

## Role Escalation Protection
- [ ] Implement RestrictRoleManagement middleware
- [ ] Add UserPolicy with authorization rules
- [ ] Prevent self-role modification
- [ ] Prevent super_admin deletion
- [ ] Add security logging for role changes
- [ ] Test all role assignment scenarios
- [ ] Setup alerts for super_admin role assignments
```

---

## CONTACT & SUPPORT

**Developer:** [Your Name/Company]  
**Email:** developer@example.com  
**Support:** +62 xxx xxxx xxxx  
**Documentation:** https://docs.school-finance.app  
**Repository:** https://github.com/your-org/school-finance

---

**This is a living document and will be updated as the project evolves.**

**END OF REFINED DOCUMENTATION**
