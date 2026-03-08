# Proposal: master-data

## Summary
Implement full master data management module — the prerequisite for all financial operations (transactions, obligations, reporting). Covers CRUD for 5 entities, fee matrix resolution logic, student Excel import/export, and role-gated access.

## Problem
Transactions and obligations cannot be created without clean master data. The foundation layer (migrations, models, middleware, services) is in place but has no controllers, form requests, views, or routes to manage the data.

## What Changes
- Controllers for 5 master entities: classes, student categories, fee types, fee matrix, students
- Form request validation classes with BaseRequest HTML stripping
- Blade views with Tailwind + Alpine.js for all CRUD operations
- Fee matrix resolver service method (`getFeeMatrix`)
- Student Excel import/export with validation feedback
- Routes with RBAC middleware per design.md section 4.7

## Capabilities
- **classes-crud**: Full CRUD for school classes (name, level, academic year, is_active)
- **categories-crud**: Full CRUD for student categories (code, name, discount_percentage)
- **fee-types-crud**: Full CRUD for fee types (code, name, is_monthly, is_active)
- **fee-matrix-crud**: CRUD for fee matrix with specificity-based resolution and effective dates
- **students-crud**: Full CRUD with search, filter, pagination, soft delete, Excel import/export

## Impact
- Routes: ~25 new routes under `/master/*`
- Controllers: 5 new controllers in `app/Http/Controllers/Master/`
- Views: ~15 new Blade templates in `resources/views/master/`
- Form Requests: 5+ new request classes
- Services: fee matrix resolver added to existing service or standalone

## Out of Scope
- Transaction posting (separate change)
- Obligation generation (separate change)
- Reporting, dashboard, notifications

## Dependencies
- Foundation layer (complete): migrations, models, middleware, seeders

## Success Criteria
- All 5 master entities are fully manageable via UI
- Fee matrix resolution returns correct rate for any student+fee_type combination
- Student Excel import handles 100+ records with validation errors reported
- RBAC enforced: operator_tu can manage students/classes, bendahara can manage fee matrix
