<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\PermanentDeleteController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\ClassController;
use App\Http\Controllers\Master\FeeMatrixController;
use App\Http\Controllers\Master\FeeTypeController;
use App\Http\Controllers\Master\PromotionBatchController;
use App\Http\Controllers\Master\StudentController;
use App\Http\Controllers\Master\StudentFeeMappingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admission\AdmissionPeriodController;
use App\Http\Controllers\Admission\ApplicantController;
use App\Http\Controllers\Expense\ExpenseController;
use App\Http\Controllers\Reconciliation\BankReconciliationController;
use App\Http\Controllers\UserManagement\UserController;
use Illuminate\Support\Facades\Route;

// Public liveness probe
Route::get('/health/live', [HealthCheckController::class, 'live']);
Route::get('/verify-receipt/{code}', [\App\Http\Controllers\ReceiptController::class, 'verifyByCode'])
    ->name('receipts.verify.public');
Route::get('/receipts/verify/{transactionNumber}', [\App\Http\Controllers\ReceiptController::class, 'verify'])
    ->name('receipts.verify');

// Authenticated diagnostics
Route::get('/health', [HealthCheckController::class, 'check'])
    ->middleware(['auth', 'role:super_admin']);

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::post('/locale', function (\Illuminate\Http\Request $request) {
    $locale = $request->input('locale');
    if (in_array($locale, ['id', 'en'], true)) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }
    return back();
})->name('locale.switch');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'can:dashboard.view', 'throttle:dashboard-read'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/unit/switch', \App\Http\Controllers\UnitSwitchController::class)->name('unit.switch');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('master')->name('master.')->group(function () {
        Route::middleware('role:super_admin,admin_tu_mi,admin_tu_ra,admin_tu_dta,operator_tu')->group(function () {
            Route::get('students/import', [StudentController::class, 'import'])
                ->middleware('can:master.students.import')
                ->name('students.import');
            Route::post('students/import', [StudentController::class, 'processImport'])
                ->middleware('can:master.students.import')
                ->name('students.processImport');
            Route::get('students/export', [StudentController::class, 'export'])
                ->middleware('can:master.students.export')
                ->name('students.export');
            Route::get('students/template', [StudentController::class, 'downloadTemplate'])
                ->middleware('can:master.students.import')
                ->name('students.template');
            Route::get('students', [StudentController::class, 'index'])
                ->middleware('can:master.students.view')
                ->name('students.index');
            Route::get('students/create', [StudentController::class, 'create'])
                ->middleware('can:master.students.create')
                ->name('students.create');
            Route::post('students', [StudentController::class, 'store'])
                ->middleware('can:master.students.create')
                ->name('students.store');
            Route::get('students/{student}', [StudentController::class, 'show'])
                ->middleware('can:master.students.view')
                ->name('students.show');
            Route::get('students/{student}/edit', [StudentController::class, 'edit'])
                ->middleware('can:master.students.edit')
                ->name('students.edit');
            Route::put('students/{student}', [StudentController::class, 'update'])
                ->middleware('can:master.students.edit')
                ->name('students.update');
            Route::delete('students/{student}', [StudentController::class, 'destroy'])
                ->middleware('can:master.students.delete')
                ->name('students.destroy');
            Route::post('students/{student}/fee-mappings', [StudentFeeMappingController::class, 'store'])
                ->middleware('can:master.student-fee-mappings.create')
                ->name('students.fee-mappings.store');
            Route::put('students/{student}/fee-mappings/{studentFeeMapping}', [StudentFeeMappingController::class, 'update'])
                ->middleware('can:master.student-fee-mappings.edit')
                ->name('students.fee-mappings.update');
            Route::delete('students/{student}/fee-mappings/{studentFeeMapping}', [StudentFeeMappingController::class, 'destroy'])
                ->middleware('can:master.student-fee-mappings.delete')
                ->name('students.fee-mappings.destroy');

            Route::get('promotions', [PromotionBatchController::class, 'index'])
                ->middleware('can:master.students.view')
                ->name('promotions.index');
            Route::get('promotions/create', [PromotionBatchController::class, 'create'])
                ->middleware('can:master.students.edit')
                ->name('promotions.create');
            Route::post('promotions', [PromotionBatchController::class, 'store'])
                ->middleware('can:master.students.edit')
                ->name('promotions.store');
            Route::get('promotions/{promotion}', [PromotionBatchController::class, 'show'])
                ->middleware('can:master.students.view')
                ->name('promotions.show');
            Route::post('promotions/{promotion}/approve', [PromotionBatchController::class, 'approve'])
                ->middleware('can:master.students.edit')
                ->name('promotions.approve');
            Route::post('promotions/{promotion}/apply', [PromotionBatchController::class, 'apply'])
                ->middleware('can:master.students.edit')
                ->name('promotions.apply');

            Route::get('classes', [ClassController::class, 'index'])
                ->middleware('can:master.classes.view')
                ->name('classes.index');
            Route::get('classes/create', [ClassController::class, 'create'])
                ->middleware('can:master.classes.create')
                ->name('classes.create');
            Route::post('classes', [ClassController::class, 'store'])
                ->middleware('can:master.classes.create')
                ->name('classes.store');
            Route::get('classes/{class}/edit', [ClassController::class, 'edit'])
                ->middleware('can:master.classes.edit')
                ->name('classes.edit');
            Route::put('classes/{class}', [ClassController::class, 'update'])
                ->middleware('can:master.classes.edit')
                ->name('classes.update');
            Route::delete('classes/{class}', [ClassController::class, 'destroy'])
                ->middleware('can:master.classes.delete')
                ->name('classes.destroy');

            Route::get('categories', [CategoryController::class, 'index'])
                ->middleware('can:master.categories.view')
                ->name('categories.index');
            Route::get('categories/create', [CategoryController::class, 'create'])
                ->middleware('can:master.categories.create')
                ->name('categories.create');
            Route::post('categories', [CategoryController::class, 'store'])
                ->middleware('can:master.categories.create')
                ->name('categories.store');
            Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])
                ->middleware('can:master.categories.edit')
                ->name('categories.edit');
            Route::put('categories/{category}', [CategoryController::class, 'update'])
                ->middleware('can:master.categories.edit')
                ->name('categories.update');
            Route::delete('categories/{category}', [CategoryController::class, 'destroy'])
                ->middleware('can:master.categories.delete')
                ->name('categories.destroy');
        });

        Route::middleware('role:super_admin,admin_tu_mi,admin_tu_ra,admin_tu_dta,bendahara')->group(function () {
            Route::get('fee-types', [FeeTypeController::class, 'index'])
                ->middleware('can:master.fee-types.view')
                ->name('fee-types.index');
            Route::get('fee-types/create', [FeeTypeController::class, 'create'])
                ->middleware('can:master.fee-types.create')
                ->name('fee-types.create');
            Route::post('fee-types', [FeeTypeController::class, 'store'])
                ->middleware('can:master.fee-types.create')
                ->name('fee-types.store');
            Route::get('fee-types/{fee_type}/edit', [FeeTypeController::class, 'edit'])
                ->middleware('can:master.fee-types.edit')
                ->name('fee-types.edit');
            Route::put('fee-types/{fee_type}', [FeeTypeController::class, 'update'])
                ->middleware('can:master.fee-types.edit')
                ->name('fee-types.update');
            Route::delete('fee-types/{fee_type}', [FeeTypeController::class, 'destroy'])
                ->middleware('can:master.fee-types.delete')
                ->name('fee-types.destroy');

            Route::get('fee-matrix', [FeeMatrixController::class, 'index'])
                ->middleware('can:master.fee-matrix.view')
                ->name('fee-matrix.index');
            Route::get('fee-matrix/create', [FeeMatrixController::class, 'create'])
                ->middleware('can:master.fee-matrix.create')
                ->name('fee-matrix.create');
            Route::post('fee-matrix', [FeeMatrixController::class, 'store'])
                ->middleware('can:master.fee-matrix.create')
                ->name('fee-matrix.store');
            Route::get('fee-matrix/{fee_matrix}/edit', [FeeMatrixController::class, 'edit'])
                ->middleware('can:master.fee-matrix.edit')
                ->name('fee-matrix.edit');
            Route::put('fee-matrix/{fee_matrix}', [FeeMatrixController::class, 'update'])
                ->middleware('can:master.fee-matrix.edit')
                ->name('fee-matrix.update');
            Route::delete('fee-matrix/{fee_matrix}', [FeeMatrixController::class, 'destroy'])
                ->middleware('can:master.fee-matrix.delete')
                ->name('fee-matrix.destroy');
        });
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])
            ->middleware('can:users.view')
            ->name('index');
        Route::get('/create', [UserController::class, 'create'])
            ->middleware('can:users.create')
            ->name('create');
        Route::post('/', [UserController::class, 'store'])
            ->middleware('can:users.create')
            ->name('store');
        Route::get('/export', [UserController::class, 'export'])
            ->middleware('can:users.view')
            ->name('export');
        Route::post('/bulk-status', [UserController::class, 'bulkUpdateStatus'])
            ->middleware('can:users.edit')
            ->name('bulk-status');
        Route::get('/{user}/edit', [UserController::class, 'edit'])
            ->middleware('can:users.edit')
            ->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])
            ->middleware('can:users.edit')
            ->name('update');
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])
            ->middleware('can:users.edit')
            ->name('reset-password');
        Route::get('/{user}', [UserController::class, 'show'])
            ->middleware('can:users.view')
            ->name('show');
        Route::delete('/{user}', [UserController::class, 'destroy'])
            ->middleware('can:users.delete')
            ->name('destroy');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'edit'])
            ->middleware('can:settings.view')
            ->name('edit');
        Route::put('/', [SettingsController::class, 'update'])
            ->middleware('can:settings.edit')
            ->name('update');
        Route::post('/permanent-delete/preview', [PermanentDeleteController::class, 'preview'])
            ->middleware('role:super_admin')
            ->name('permanent-delete.preview');
    });

    // Transactions
    Route::middleware('role:super_admin,admin_tu_mi,admin_tu_ra,admin_tu_dta,bendahara,kepala_sekolah,operator_tu,auditor,cashier,admin_tu')->group(function () {
        Route::get('transactions', [\App\Http\Controllers\Transaction\TransactionController::class, 'index'])
            ->middleware('can:transactions.view')
            ->name('transactions.index');
        Route::get('transactions/create', [\App\Http\Controllers\Transaction\TransactionController::class, 'create'])
            ->middleware('can:transactions.create')
            ->name('transactions.create');
        Route::post('transactions', [\App\Http\Controllers\Transaction\TransactionController::class, 'store'])
            ->middleware('can:transactions.create')
            ->name('transactions.store');
        Route::get('transactions/{transaction}', [\App\Http\Controllers\Transaction\TransactionController::class, 'show'])
            ->middleware('can:transactions.view')
            ->name('transactions.show');
        Route::delete('transactions/{transaction}', [\App\Http\Controllers\Transaction\TransactionController::class, 'destroy'])
            ->middleware('can:transactions.cancel')
            ->name('transactions.destroy');

        Route::get('/receipts/{transaction}/print', [\App\Http\Controllers\ReceiptController::class, 'print'])
            ->middleware('can:receipts.print')
            ->name('receipts.print');
    });

    // Invoices
    Route::prefix('invoices')->name('invoices.')->middleware('role:super_admin,admin_tu_mi,admin_tu_ra,admin_tu_dta,bendahara,kepala_sekolah,operator_tu,auditor')->group(function () {
        Route::get('/', [\App\Http\Controllers\Invoice\InvoiceController::class, 'index'])
            ->middleware('can:invoices.view')
            ->name('index');
        Route::get('/create', [\App\Http\Controllers\Invoice\InvoiceController::class, 'create'])
            ->middleware('can:invoices.create')
            ->name('create');
        Route::post('/', [\App\Http\Controllers\Invoice\InvoiceController::class, 'store'])
            ->middleware('can:invoices.create')
            ->name('store');
        Route::get('/generate', [\App\Http\Controllers\Invoice\InvoiceController::class, 'generate'])
            ->middleware('can:invoices.generate')
            ->name('generate');
        Route::post('/generate', [\App\Http\Controllers\Invoice\InvoiceController::class, 'runGeneration'])
            ->middleware('can:invoices.generate')
            ->name('runGeneration');
        Route::get('/{invoice}', [\App\Http\Controllers\Invoice\InvoiceController::class, 'show'])
            ->middleware('can:invoices.view')
            ->name('show');
        Route::get('/{invoice}/print', [\App\Http\Controllers\Invoice\InvoiceController::class, 'print'])
            ->middleware('can:invoices.print')
            ->name('print');
        Route::delete('/{invoice}', [\App\Http\Controllers\Invoice\InvoiceController::class, 'destroy'])
            ->middleware('can:invoices.cancel')
            ->name('destroy');
    });

    // Settlements
    Route::prefix('settlements')->name('settlements.')->middleware('role:super_admin,admin_tu_mi,admin_tu_ra,admin_tu_dta,bendahara,kepala_sekolah,operator_tu,auditor')->group(function () {
        Route::get('/', [\App\Http\Controllers\Settlement\SettlementController::class, 'index'])
            ->middleware('can:settlements.view')
            ->name('index');
        Route::get('/create', [\App\Http\Controllers\Settlement\SettlementController::class, 'create'])
            ->middleware('can:settlements.create')
            ->name('create');
        Route::post('/', [\App\Http\Controllers\Settlement\SettlementController::class, 'store'])
            ->middleware('can:settlements.create')
            ->name('store');
        Route::get('/{settlement}', [\App\Http\Controllers\Settlement\SettlementController::class, 'show'])
            ->middleware('can:settlements.view')
            ->name('show');
        Route::get('/{settlement}/print', [\App\Http\Controllers\Settlement\SettlementController::class, 'print'])
            ->middleware('can:settlements.view')
            ->name('print');
        Route::post('/{settlement}/void', [\App\Http\Controllers\Settlement\SettlementController::class, 'void'])
            ->middleware('can:settlements.void')
            ->name('void');
        Route::delete('/{settlement}', [\App\Http\Controllers\Settlement\SettlementController::class, 'destroy'])
            ->middleware('can:settlements.cancel')
            ->name('destroy');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->middleware('role:super_admin,admin_tu_mi,admin_tu_ra,admin_tu_dta,bendahara,kepala_sekolah,operator_tu,auditor')->group(function () {
        Route::get('/daily', [\App\Http\Controllers\Report\ReportController::class, 'daily'])
            ->middleware(['can:reports.daily', 'throttle:reports-read'])
            ->name('daily');
        Route::get('/daily/export', [\App\Http\Controllers\Report\ReportController::class, 'dailyExport'])
            ->middleware(['can:reports.daily', 'throttle:reports-read'])
            ->name('daily.export');
        Route::get('/monthly', [\App\Http\Controllers\Report\ReportController::class, 'monthly'])
            ->middleware(['can:reports.monthly', 'throttle:reports-read'])
            ->name('monthly');
        Route::get('/monthly/export', [\App\Http\Controllers\Report\ReportController::class, 'monthlyExport'])
            ->middleware(['can:reports.monthly', 'throttle:reports-read'])
            ->name('monthly.export');
        Route::get('/arrears', [\App\Http\Controllers\Report\ReportController::class, 'arrears'])
            ->middleware(['can:reports.arrears', 'throttle:reports-read'])
            ->name('arrears');
        Route::get('/arrears/export', [\App\Http\Controllers\Report\ReportController::class, 'arrearsExport'])
            ->middleware(['can:reports.arrears', 'throttle:reports-read'])
            ->name('arrears.export');
        Route::get('/ar-outstanding', [\App\Http\Controllers\Report\ReportController::class, 'arOutstanding'])
            ->middleware(['can:reports.ar-outstanding', 'throttle:reports-read'])
            ->name('ar-outstanding');
        Route::get('/ar-outstanding/export', [\App\Http\Controllers\Report\ReportController::class, 'arOutstandingExport'])
            ->middleware(['can:reports.ar-outstanding', 'throttle:reports-read'])
            ->name('ar-outstanding.export');
        Route::get('/collection', [\App\Http\Controllers\Report\ReportController::class, 'collection'])
            ->middleware(['can:reports.collection', 'throttle:reports-read'])
            ->name('collection');
        Route::get('/collection/export', [\App\Http\Controllers\Report\ReportController::class, 'collectionExport'])
            ->middleware(['can:reports.collection', 'throttle:reports-read'])
            ->name('collection.export');
        Route::get('/student-statement', [\App\Http\Controllers\Report\ReportController::class, 'studentStatement'])
            ->middleware(['can:reports.student-statement', 'throttle:reports-read'])
            ->name('student-statement');
        Route::get('/student-statement/export', [\App\Http\Controllers\Report\ReportController::class, 'studentStatementExport'])
            ->middleware(['can:reports.student-statement', 'throttle:reports-read'])
            ->name('student-statement.export');
        Route::get('/cash-book', [\App\Http\Controllers\Report\ReportController::class, 'cashBook'])
            ->middleware(['can:reports.cash-book', 'throttle:reports-read'])
            ->name('cash-book');
        Route::get('/cash-book/export', [\App\Http\Controllers\Report\ReportController::class, 'cashBookExport'])
            ->middleware(['can:reports.cash-book', 'throttle:reports-read'])
            ->name('cash-book.export');
    });

    // Expense Management v2
    Route::prefix('expenses')->name('expenses.')->middleware('role:super_admin,admin_tu_mi,admin_tu_ra,admin_tu_dta,bendahara,kepala_sekolah,operator_tu,auditor')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])
            ->middleware('can:expenses.view')
            ->name('index');
        Route::post('/', [ExpenseController::class, 'store'])
            ->middleware('can:expenses.create')
            ->name('store');
        Route::post('/{expense}/approve', [ExpenseController::class, 'approve'])
            ->middleware('can:expenses.approve')
            ->name('approve');
        Route::get('/budget-report', [ExpenseController::class, 'budgetVsRealization'])
            ->middleware('can:expenses.report.view')
            ->name('budget-report');
        Route::post('/budgets', [ExpenseController::class, 'storeBudget'])
            ->middleware('can:expenses.budget.manage')
            ->name('budgets.store');
    });

    // Admission (PSB)
    Route::prefix('admission')->name('admission.')->middleware('role:super_admin,admin_tu_mi,admin_tu_ra,admin_tu_dta,operator_tu,kepala_sekolah,bendahara,auditor')->group(function () {
        // Periods
        Route::get('periods', [AdmissionPeriodController::class, 'index'])
            ->middleware('can:admission.periods.view')
            ->name('periods.index');
        Route::get('periods/create', [AdmissionPeriodController::class, 'create'])
            ->middleware('can:admission.periods.create')
            ->name('periods.create');
        Route::post('periods', [AdmissionPeriodController::class, 'store'])
            ->middleware('can:admission.periods.create')
            ->name('periods.store');
        Route::get('periods/{period}', [AdmissionPeriodController::class, 'show'])
            ->middleware('can:admission.periods.view')
            ->name('periods.show');
        Route::get('periods/{period}/edit', [AdmissionPeriodController::class, 'edit'])
            ->middleware('can:admission.periods.edit')
            ->name('periods.edit');
        Route::put('periods/{period}', [AdmissionPeriodController::class, 'update'])
            ->middleware('can:admission.periods.edit')
            ->name('periods.update');
        Route::delete('periods/{period}', [AdmissionPeriodController::class, 'destroy'])
            ->middleware('can:admission.periods.delete')
            ->name('periods.destroy');

        // Applicants
        Route::get('applicants', [ApplicantController::class, 'index'])
            ->middleware('can:admission.applicants.view')
            ->name('applicants.index');
        Route::get('applicants/create', [ApplicantController::class, 'create'])
            ->middleware('can:admission.applicants.create')
            ->name('applicants.create');
        Route::post('applicants', [ApplicantController::class, 'store'])
            ->middleware('can:admission.applicants.create')
            ->name('applicants.store');
        Route::get('applicants/{applicant}', [ApplicantController::class, 'show'])
            ->middleware('can:admission.applicants.view')
            ->name('applicants.show');
        Route::get('applicants/{applicant}/edit', [ApplicantController::class, 'edit'])
            ->middleware('can:admission.applicants.edit')
            ->name('applicants.edit');
        Route::put('applicants/{applicant}', [ApplicantController::class, 'update'])
            ->middleware('can:admission.applicants.edit')
            ->name('applicants.update');
        Route::delete('applicants/{applicant}', [ApplicantController::class, 'destroy'])
            ->middleware('can:admission.applicants.delete')
            ->name('applicants.destroy');

        // Workflow actions
        Route::post('applicants/{applicant}/review', [ApplicantController::class, 'review'])
            ->middleware('can:admission.applicants.review')
            ->name('applicants.review');
        Route::post('applicants/{applicant}/accept', [ApplicantController::class, 'accept'])
            ->middleware('can:admission.applicants.accept')
            ->name('applicants.accept');
        Route::post('applicants/{applicant}/reject', [ApplicantController::class, 'reject'])
            ->middleware('can:admission.applicants.reject')
            ->name('applicants.reject');
        Route::post('applicants/{applicant}/enroll', [ApplicantController::class, 'enroll'])
            ->middleware('can:admission.applicants.enroll')
            ->name('applicants.enroll');
        Route::post('applicants/bulk-status', [ApplicantController::class, 'bulkStatus'])
            ->middleware('can:admission.applicants.accept')
            ->name('applicants.bulk-status');
    });

    // Bank Reconciliation
    Route::prefix('bank-reconciliation')->name('bank-reconciliation.')->middleware('role:super_admin,admin_tu_mi,admin_tu_ra,admin_tu_dta,bendahara,kepala_sekolah,operator_tu,auditor')->group(function () {
        Route::get('/', [BankReconciliationController::class, 'index'])
            ->middleware('can:bank-reconciliation.view')
            ->name('index');
        Route::post('/', [BankReconciliationController::class, 'storeSession'])
            ->middleware('can:bank-reconciliation.manage')
            ->name('store');
        Route::get('/{bankReconciliation}', [BankReconciliationController::class, 'show'])
            ->middleware('can:bank-reconciliation.view')
            ->name('show');
        Route::post('/{bankReconciliation}/import', [BankReconciliationController::class, 'import'])
            ->middleware('can:bank-reconciliation.manage')
            ->name('import');
        Route::post('/{bankReconciliation}/lines/{line}/match', [BankReconciliationController::class, 'match'])
            ->middleware('can:bank-reconciliation.manage')
            ->name('match');
        Route::post('/{bankReconciliation}/lines/{line}/unmatch', [BankReconciliationController::class, 'unmatch'])
            ->middleware('can:bank-reconciliation.manage')
            ->name('unmatch');
        Route::post('/{bankReconciliation}/close', [BankReconciliationController::class, 'close'])
            ->middleware('can:bank-reconciliation.close')
            ->name('close');
    });
});

require __DIR__ . '/auth.php';
