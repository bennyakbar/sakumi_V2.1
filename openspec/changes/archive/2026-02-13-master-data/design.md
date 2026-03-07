# Design: master-data

## Architecture

### Controllers (app/Http/Controllers/Master/)
| Controller | Entity | Routes Prefix |
|-----------|--------|---------------|
| `ClassController` | SchoolClass | `/master/classes` |
| `CategoryController` | StudentCategory | `/master/categories` |
| `FeeTypeController` | FeeType | `/master/fee-types` |
| `FeeMatrixController` | FeeMatrix | `/master/fee-matrix` |
| `StudentController` | Student | `/master/students` |

All controllers follow standard Laravel resource convention (index, create, store, edit, update, destroy).

### Form Requests (app/Http/Requests/Master/)
All extend `BaseRequest` (HTML tag stripping, trimming).
- `StoreClassRequest` / `UpdateClassRequest`
- `StoreCategoryRequest` / `UpdateCategoryRequest`
- `StoreFeeTypeRequest` / `UpdateFeeTypeRequest`
- `StoreFeeMatrixRequest` / `UpdateFeeMatrixRequest`
- `StoreStudentRequest` / `UpdateStudentRequest`
- `ImportStudentRequest` (Excel file validation)

### Views (resources/views/master/)
```
master/
├── classes/        index, create, edit
├── categories/     index, create, edit
├── fee-types/      index, create, edit
├── fee-matrix/     index, create, edit
├── students/       index, create, edit, show, import
└── _partials/      shared form components
```

### Fee Matrix Resolution Logic
Location: `app/Services/FeeMatrixService.php`

```
getFeeMatrix(fee_type_id, class_id, category_id, ?date) → FeeMatrix|null
```

Query:
1. Filter: `fee_type_id` match, `is_active = true`
2. Filter: `class_id` match OR NULL, `category_id` match OR NULL
3. Filter: `effective_from <= date` AND (`effective_to IS NULL` OR `effective_to >= date`)
4. Sort: `ORDER BY class_id DESC NULLS LAST, category_id DESC NULLS LAST`
5. Take first result (most specific match wins)

### Student Import/Export
- Import: `app/Imports/StudentImport.php` (maatwebsite/excel)
  - Validates: nis (unique), name (required), class (exists), category (exists)
  - Returns validation errors per row
- Export: `app/Exports/StudentExport.php`
  - Exports active students with class and category names

### RBAC Route Groups
```php
// operator_tu + bendahara + super_admin
Route::middleware(['auth', 'role:super_admin|operator_tu'])->group(...)
  → classes, categories, students

// bendahara + super_admin
Route::middleware(['auth', 'role:super_admin|bendahara'])->group(...)
  → fee-types, fee-matrix
```

## Key Decisions
- Fee matrix uses specificity-based priority (class+category > class-only > global)
- Student soft delete via `deleted_at` (SoftDeletes trait) — graduated students remain queryable
- Import uses `WithValidation` + `SkipsOnError` for row-level error reporting
- All list views use pagination (15 per page)
- Search on students: NIS, NISN, name (trigram-friendly `ILIKE`)

## Risks & Mitigations
| Risk | Mitigation |
|------|-----------|
| Ambiguous fee matrix when multiple entries match | Deterministic ORDER BY with NULLS LAST |
| Import creates duplicates on retry | Unique constraint on `nis`, upsert-safe |
| Large imports timeout | Queue-based import for > 500 rows |
