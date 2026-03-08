# Tasks: master-data

## Form Requests
- [x] Create form requests for classes (StoreClassRequest, UpdateClassRequest)
- [x] Create form requests for categories (StoreCategoryRequest, UpdateCategoryRequest)
- [x] Create form requests for fee types (StoreFeeTypeRequest, UpdateFeeTypeRequest)
- [x] Create form requests for fee matrix (StoreFeeMatrixRequest, UpdateFeeMatrixRequest)
- [x] Create form requests for students (StoreStudentRequest, UpdateStudentRequest, ImportStudentRequest)

## Services
- [x] Create FeeMatrixService with getFeeMatrix() resolver method

## Import/Export
- [x] Create StudentImport class (maatwebsite/excel with row validation)
- [x] Create StudentExport class (maatwebsite/excel)

## Controllers
- [x] Create ClassController with full resource CRUD
- [x] Create CategoryController with full resource CRUD
- [x] Create FeeTypeController with full resource CRUD
- [x] Create FeeMatrixController with full resource CRUD
- [x] Create StudentController with CRUD + import/export actions

## Routes
- [x] Register all master data routes with RBAC middleware

## Views
- [x] Create shared layout component for master data pages
- [x] Create class views (index, create, edit)
- [x] Create category views (index, create, edit)
- [x] Create fee type views (index, create, edit)
- [x] Create fee matrix views (index, create, edit)
- [x] Create student views (index, create, edit, show, import)

## Tests
- [x] Add FeeMatrixService unit tests (resolution priority, date ranges, edge cases)
- [x] Add controller feature tests for all 5 entities
- [x] Add student import/export tests
