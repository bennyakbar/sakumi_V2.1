# Monthly Invoice Generation System

## Overview

The Monthly Invoice Generation System allows recurring fees (SPP, Activity Fees, Facility Fees, etc.) to be generated automatically each month using **Invoice Templates**. This is an additive feature that does not modify existing invoice or settlement logic.

## Invoice Templates

Templates define recurring billing items. Each template is linked to a `FeeType` and specifies a fixed amount and billing cycle.

**Table:** `invoice_templates`

| Field         | Description                                      |
|---------------|--------------------------------------------------|
| `unit_id`     | School unit (MI, RA, DTA)                        |
| `fee_type_id` | Links to fee_types table                         |
| `name`        | Display name (e.g., "SPP Bulanan")               |
| `amount`      | Template amount (actual amount comes from obligation) |
| `billing_cycle` | Currently supports `monthly`                   |
| `is_active`   | Only active templates are used for generation    |
| `created_by`  | User who created the template                    |

## How Monthly Generation Works

1. The system fetches all active templates with `billing_cycle = 'monthly'`
2. For each template, it ensures student obligations exist for the target month (via `ArrearsService`)
3. For each active student + template combination:
   - **Duplicate check**: Skips if an active invoice already exists for that student, fee type, and month
   - **Obligation check**: Skips if no unpaid obligation exists for that fee type and month
   - Creates an invoice with a single item linked to the student obligation
   - Posts the accounting journal entry via `AccountingEngine`

### Idempotency

Running generation multiple times for the same month is safe. The duplicate check prevents creating duplicate invoices based on:
- `student_id`
- `fee_type_id` (via invoice items)
- `period_identifier` (e.g., "2026-04")
- Invoice `status != 'cancelled'`

## Manual Trigger (Web UI)

TU staff can trigger monthly generation from the web interface:

**URL:** `/invoices/generate-monthly`

**Permission required:** `invoices.generate`

Select a month, year, and optionally a class filter, then click "Generate Monthly Invoices".

## Artisan Command

```bash
# Generate for current month, all units
php artisan invoices:generate-monthly

# Generate for a specific month
php artisan invoices:generate-monthly --month=4 --year=2026

# Generate for a specific unit
php artisan invoices:generate-monthly --month=4 --year=2026 --unit=MI

# Generate for a specific class
php artisan invoices:generate-monthly --month=4 --year=2026 --class=5
```

## Scheduler

Automatic generation is scheduled to run on the 1st of every month at 00:30, after obligation generation (which runs at 00:00).

**File:** `routes/console.php`

```php
Schedule::command('invoices:generate-monthly')->monthlyOn(1, '00:30');
```

## File Locations

| File | Purpose |
|------|---------|
| `database/migrations/2026_03_09_110000_create_invoice_templates_table.php` | Migration |
| `app/Models/InvoiceTemplate.php` | Eloquent model |
| `app/Services/InvoiceGenerationService.php` | Core generation logic |
| `app/Console/Commands/GenerateMonthlyInvoices.php` | Artisan command |
| `app/Http/Controllers/Invoice/MonthlyInvoiceController.php` | Web controller |
| `resources/views/invoices/generate-monthly.blade.php` | Blade view |
