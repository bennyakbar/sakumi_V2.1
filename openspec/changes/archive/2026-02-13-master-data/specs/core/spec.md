# Spec: Master Data Module

## Classes
- CRUD: name (varchar 100), level (1-6), academic_year (varchar 9, e.g. "2025/2026"), is_active (boolean)
- Unique constraint: (name, academic_year)
- Soft toggle: is_active flag (no physical delete if referenced by students)
- Validation: name required, level 1-6, academic_year format `YYYY/YYYY`

## Student Categories
- CRUD: code (varchar 20, unique), name (varchar 100), description (text), discount_percentage (decimal 5,2, 0-100)
- Validation: code required unique uppercase, discount 0-100

## Fee Types
- CRUD: code (varchar 20, unique), name (varchar 100), description (text), is_monthly (boolean), is_active (boolean)
- Validation: code required unique uppercase
- is_monthly determines whether obligations are auto-generated monthly

## Fee Matrix
- CRUD: fee_type_id (required), class_id (nullable = all), category_id (nullable = all), amount (decimal 15,2), effective_from (date, required), effective_to (date, nullable), is_active, notes
- CHECK: effective_to IS NULL OR effective_to >= effective_from
- Resolution: specificity-based priority — ORDER BY class_id DESC NULLS LAST, category_id DESC NULLS LAST, take first
- Validation: amount > 0, dates valid range, fee_type exists

## Students
- CRUD: nis (unique, required), nisn (unique, nullable), name, class_id, category_id, gender (L/P), birth_date, birth_place, parent_name, parent_phone, parent_whatsapp (628x format), address, status (active/graduated/dropout/transferred), enrollment_date
- Soft delete via deleted_at
- Search: NIS, NISN, name (ILIKE)
- Filter: class_id, category_id, status
- Pagination: 15 per page
- Import: Excel with row-level validation errors
- Export: Excel with class and category names
