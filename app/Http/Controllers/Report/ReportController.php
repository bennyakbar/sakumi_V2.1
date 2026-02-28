<?php

namespace App\Http\Controllers\Report;

use App\Exports\ArrearsAgingExport;
use App\Exports\ReportRowsExport;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\Settlement;
use App\Models\SettlementAllocation;
use App\Models\Transaction;
use App\Models\SchoolClass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    private const AGING_BUCKETS = [
        'current' => ['label_key' => 'message.aging_0_30', 'min' => 0, 'max' => 30],
        'd31_60' => ['label_key' => 'message.aging_31_60', 'min' => 31, 'max' => 60],
        'd61_90' => ['label_key' => 'message.aging_61_90', 'min' => 61, 'max' => 90],
        'd90_plus' => ['label_key' => 'message.aging_90_plus', 'min' => 91, 'max' => null],
    ];

    private static function agingLabel(string $bucketKey): string
    {
        return __((self::AGING_BUCKETS[$bucketKey] ?? self::AGING_BUCKETS['d90_plus'])['label_key']);
    }

    private function resolveScope(Request $request): array
    {
        $scope = $request->input('scope', 'unit');
        $consolidated = $scope === 'all' && auth()->user()->hasRole('super_admin');

        return [$consolidated ? 'all' : 'unit', $consolidated];
    }

    public function daily(Request $request): View
    {
        [$scope, $consolidated] = $this->resolveScope($request);
        $date = $request->input('date', date('Y-m-d'));
        $entries = $this->buildDailyEntries($consolidated, $date);

        $totalAmount = $entries->sum('amount');

        return view('reports.daily', compact('entries', 'date', 'totalAmount', 'scope', 'consolidated'));
    }

    public function dailyExport(Request $request): BinaryFileResponse
    {
        [$scope, $consolidated] = $this->resolveScope($request);
        $date = $request->input('date', date('Y-m-d'));
        $format = strtolower((string) $request->input('format', 'xlsx'));
        if (!in_array($format, ['xlsx', 'csv'], true)) {
            abort(422, 'Unsupported export format.');
        }

        $rows = [[
            'Date',
            'Time',
            'Unit',
            'Source',
            'Code',
            'Type',
            'Payment Method',
            'Cashier',
            'Student',
            'Class',
            'Description',
            'Cash In',
            'Cash Out',
            'Net',
        ]];
        $totalIn = 0.0;
        $totalOut = 0.0;
        $net = 0.0;

        foreach ($this->buildDailyEntries($consolidated, $date) as $entry) {
            $amount = (float) ($entry['amount'] ?? 0);
            $cashIn = $amount > 0 ? $amount : 0.0;
            $cashOut = $amount < 0 ? abs($amount) : 0.0;
            $rows[] = [
                Carbon::parse($date)->format('Y-m-d'),
                (string) ($entry['time'] ?? '-'),
                (string) ($entry['unit_code'] ?? '-'),
                (string) ($entry['source'] ?? '-'),
                (string) ($entry['code'] ?? '-'),
                (string) ($entry['type'] ?? '-'),
                (string) ($entry['payment_method'] ?? '-'),
                (string) ($entry['cashier'] ?? '-'),
                (string) ($entry['student'] ?? '-'),
                (string) ($entry['class'] ?? '-'),
                (string) collect($entry['items'] ?? [])->implode('; '),
                $cashIn,
                $cashOut,
                $amount,
            ];
            $totalIn += $cashIn;
            $totalOut += $cashOut;
            $net += $amount;
        }
        $rows[] = ['TOTAL', '', '', '', '', '', '', '', '', '', '', $totalIn, $totalOut, $net];

        $filename = sprintf('daily-report-%s-%s.%s', $scope, Carbon::parse($date)->format('Ymd'), $format);
        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
        $totalRow = count($rows);

        return Excel::download(
            new ReportRowsExport($rows, [
                'L' => ReportRowsExport::accountingNumberFormat(),
                'M' => ReportRowsExport::accountingNumberFormat(),
                'N' => ReportRowsExport::accountingNumberFormat(),
            ], [$totalRow]),
            $filename,
            $writerType
        );
    }

    public function monthly(Request $request): View
    {
        [$scope, $consolidated] = $this->resolveScope($request);
        $month = (int) $request->input('month', date('m'));
        $year = (int) $request->input('year', date('Y'));
        $entriesRaw = $this->buildMonthlyEntries($consolidated, $month, $year);
        $entries = $entriesRaw->map(fn (array $row) => (object) $row)->values();

        // --- Daily summary (combined) ---
        $dailySummary = $entriesRaw->groupBy(fn ($e) => $e['date']->format('Y-m-d'))
            ->map(fn ($dayEntries) => $dayEntries->sum('amount'));

        $totalAmount = $entriesRaw->sum('amount');

        return view('reports.monthly', compact('entries', 'dailySummary', 'month', 'year', 'totalAmount', 'scope', 'consolidated'));
    }

    public function monthlyExport(Request $request): BinaryFileResponse
    {
        [$scope, $consolidated] = $this->resolveScope($request);
        $month = (int) $request->input('month', date('m'));
        $year = (int) $request->input('year', date('Y'));
        $format = strtolower((string) $request->input('format', 'xlsx'));
        if (!in_array($format, ['xlsx', 'csv'], true)) {
            abort(422, 'Unsupported export format.');
        }

        $rows = [[
            'Date',
            'Unit',
            'Source',
            'Code',
            'Type',
            'Payment Method',
            'Cashier',
            'Student',
            'Class',
            'Description',
            'Cash In',
            'Cash Out',
            'Net',
        ]];
        $totalIn = 0.0;
        $totalOut = 0.0;
        $net = 0.0;

        foreach ($this->buildMonthlyEntries($consolidated, $month, $year) as $entry) {
            $amount = (float) ($entry['amount'] ?? 0);
            $cashIn = $amount > 0 ? $amount : 0.0;
            $cashOut = $amount < 0 ? abs($amount) : 0.0;
            $rows[] = [
                $entry['date']->format('Y-m-d'),
                (string) ($entry['unit_code'] ?? '-'),
                (string) ($entry['source'] ?? '-'),
                (string) ($entry['code'] ?? '-'),
                (string) ($entry['type'] ?? '-'),
                (string) ($entry['payment_method'] ?? '-'),
                (string) ($entry['cashier'] ?? '-'),
                (string) ($entry['student_name'] ?? '-'),
                (string) ($entry['class_name'] ?? '-'),
                (string) ($entry['description'] ?? '-'),
                $cashIn,
                $cashOut,
                $amount,
            ];
            $totalIn += $cashIn;
            $totalOut += $cashOut;
            $net += $amount;
        }
        $rows[] = ['TOTAL', '', '', '', '', '', '', '', '', '', $totalIn, $totalOut, $net];

        $filename = sprintf('monthly-report-%s-%04d%02d.%s', $scope, $year, $month, $format);
        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
        $totalRow = count($rows);

        return Excel::download(
            new ReportRowsExport($rows, [
                'K' => ReportRowsExport::accountingNumberFormat(),
                'L' => ReportRowsExport::accountingNumberFormat(),
                'M' => ReportRowsExport::accountingNumberFormat(),
            ], [$totalRow]),
            $filename,
            $writerType
        );
    }

    public function arOutstanding(Request $request): View
    {
        [$scope, $consolidated] = $this->resolveScope($request);
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, 30);

        $classId = $request->input('class_id');
        $categoryId = $request->input('category_id');
        $studentId = $request->input('student_id');

        $query = $this->buildArOutstandingQuery($consolidated, $dateFrom, $dateTo, $classId, $categoryId, $studentId);

        $summary = (clone $query)
            ->selectRaw('COALESCE(SUM(invoices.total_amount), 0) as total_invoice')
            ->selectRaw('COALESCE(SUM(COALESCE(paid.settled_amount, 0)), 0) as total_settled')
            ->selectRaw('COALESCE(SUM(invoices.total_amount - COALESCE(paid.settled_amount, 0)), 0) as total_outstanding')
            ->first();

        $rows = $query->paginate(20)->withQueryString();
        $classes = ($consolidated ? SchoolClass::withoutGlobalScope('unit') : SchoolClass::query())->orderBy('name')->get();
        $categories = ($consolidated ? StudentCategory::withoutGlobalScope('unit') : StudentCategory::query())->orderBy('name')->get();
        $students = ($consolidated ? Student::withoutGlobalScope('unit') : Student::query())
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('reports.ar-outstanding', compact(
            'rows',
            'classes',
            'categories',
            'students',
            'classId',
            'categoryId',
            'studentId',
            'dateFrom',
            'dateTo',
            'summary',
            'scope',
            'consolidated'
        ));
    }

    public function arOutstandingExport(Request $request): BinaryFileResponse
    {
        [$scope, $consolidated] = $this->resolveScope($request);
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, 30);
        $format = strtolower((string) $request->input('format', 'xlsx'));
        if (!in_array($format, ['xlsx', 'csv'], true)) {
            abort(422, 'Unsupported export format.');
        }

        $query = $this->buildArOutstandingQuery(
            $consolidated,
            $dateFrom,
            $dateTo,
            $request->input('class_id'),
            $request->input('category_id'),
            $request->input('student_id')
        );

        $rows = [[
            'Invoice',
            'Student',
            'Class',
            'Category',
            'Due Date',
            'Total',
            'Settled',
            'Outstanding',
        ]];

        foreach ($query->get() as $invoice) {
            $rows[] = [
                $invoice->invoice_number,
                $invoice->student?->name ?? '-',
                $invoice->student?->schoolClass?->name ?? '-',
                $invoice->student?->category?->name ?? '-',
                optional($invoice->due_date)->format('Y-m-d') ?? '-',
                (float) $invoice->total_amount,
                (float) ($invoice->settled_amount ?? 0),
                (float) ($invoice->outstanding_amount ?? 0),
            ];
        }

        $filename = sprintf('ar-outstanding-%s.%s', $scope, $format);
        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;

        return Excel::download(new ReportRowsExport($rows), $filename, $writerType);
    }

    public function collection(Request $request): View
    {
        [$scope, $consolidated] = $this->resolveScope($request);
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, 30);
        $paymentMethod = $request->input('payment_method');
        $cashierId = $request->input('cashier_id');

        $entries = $this->buildCollectionEntries($consolidated, $dateFrom, $dateTo, $paymentMethod, $cashierId);
        $paginated = $this->paginateCollection($entries, 25, $request);

        $totalIncome = (float) $entries->where('amount', '>', 0)->sum('amount');
        $totalExpense = (float) abs($entries->where('amount', '<', 0)->sum('amount'));
        $net = $totalIncome - $totalExpense;
        $cashiers = DB::table('users')->orderBy('name')->get(['id', 'name']);

        return view('reports.collection', compact(
            'paginated',
            'dateFrom',
            'dateTo',
            'paymentMethod',
            'cashierId',
            'cashiers',
            'totalIncome',
            'totalExpense',
            'net',
            'scope',
            'consolidated'
        ));
    }

    public function collectionExport(Request $request): BinaryFileResponse
    {
        [$scope, $consolidated] = $this->resolveScope($request);
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, 30);
        $paymentMethod = $request->input('payment_method');
        $cashierId = $request->input('cashier_id');
        $format = strtolower((string) $request->input('format', 'xlsx'));
        if (!in_array($format, ['xlsx', 'csv'], true)) {
            abort(422, 'Unsupported export format.');
        }

        $rows = [[
            'Date',
            'Source',
            'Code',
            'Student',
            'Payment Method',
            'Cashier',
            'Amount',
        ]];

        foreach ($this->buildCollectionEntries($consolidated, $dateFrom, $dateTo, $paymentMethod, $cashierId) as $entry) {
            $rows[] = [
                Carbon::parse($entry['date'])->format('Y-m-d'),
                $entry['source'],
                $entry['code'],
                $entry['student'],
                $entry['payment_method'],
                $entry['cashier'],
                (float) $entry['amount'],
            ];
        }

        $filename = sprintf('collection-%s.%s', $scope, $format);
        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;

        return Excel::download(new ReportRowsExport($rows), $filename, $writerType);
    }

    public function studentStatement(Request $request): View
    {
        [$scope, $consolidated] = $this->resolveScope($request);
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, 30);
        $studentId = (int) $request->input('student_id', 0);

        $students = ($consolidated ? Student::withoutGlobalScope('unit') : Student::query())
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $statementRows = collect();
        $summary = ['opening_balance' => 0.0, 'total_debit' => 0.0, 'total_credit' => 0.0, 'closing_balance' => 0.0];
        $selectedStudent = null;

        if ($studentId > 0) {
            $selectedStudent = ($consolidated ? Student::withoutGlobalScope('unit') : Student::query())->findOrFail($studentId);
            [$statementRows, $summary] = $this->buildStudentStatement($consolidated, $studentId, $dateFrom, $dateTo);
        }

        return view('reports.student-statement', compact(
            'students',
            'selectedStudent',
            'studentId',
            'statementRows',
            'summary',
            'dateFrom',
            'dateTo',
            'scope',
            'consolidated'
        ));
    }

    public function studentStatementExport(Request $request): BinaryFileResponse
    {
        [$scope, $consolidated] = $this->resolveScope($request);
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, 30);
        $studentId = (int) $request->input('student_id');
        if ($studentId <= 0) {
            abort(422, 'Student is required for statement export.');
        }

        $format = strtolower((string) $request->input('format', 'xlsx'));
        if (!in_array($format, ['xlsx', 'csv'], true)) {
            abort(422, 'Unsupported export format.');
        }

        [$statementRows, $summary] = $this->buildStudentStatement($consolidated, $studentId, $dateFrom, $dateTo);

        $rows = [[
            'Date',
            'Reference',
            'Description',
            'Debit',
            'Credit',
            'Balance',
        ]];
        $rows[] = ['', '', 'Opening Balance', '', '', (float) $summary['opening_balance']];
        foreach ($statementRows as $row) {
            $rows[] = [
                Carbon::parse($row['date'])->format('Y-m-d'),
                $row['reference'],
                $row['description'],
                (float) $row['debit'],
                (float) $row['credit'],
                (float) $row['balance'],
            ];
        }

        $filename = sprintf('student-statement-%s.%s', $scope, $format);
        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;

        return Excel::download(new ReportRowsExport($rows), $filename, $writerType);
    }

    public function cashBook(Request $request): View
    {
        [$scope, $consolidated] = $this->resolveScope($request);
        $date = Carbon::parse((string) $request->input('date', now()->toDateString()))->startOfDay();

        [$entries, $summary] = $this->buildCashBook($consolidated, $date);

        return view('reports.cash-book', compact('entries', 'summary', 'date', 'scope', 'consolidated'));
    }

    public function cashBookExport(Request $request): BinaryFileResponse
    {
        [$scope, $consolidated] = $this->resolveScope($request);
        $date = Carbon::parse((string) $request->input('date', now()->toDateString()))->startOfDay();
        $format = strtolower((string) $request->input('format', 'xlsx'));
        if (!in_array($format, ['xlsx', 'csv'], true)) {
            abort(422, 'Unsupported export format.');
        }

        [$entries, $summary] = $this->buildCashBook($consolidated, $date);
        $rows = [[
            'Date',
            'Source',
            'Code',
            'Description',
            'Debit',
            'Credit',
            'Balance',
        ]];
        $rows[] = [$date->format('Y-m-d'), '', '', 'Opening Balance', '', '', (float) $summary['opening_balance']];
        foreach ($entries as $entry) {
            $rows[] = [
                $date->format('Y-m-d'),
                $entry['source'],
                $entry['code'],
                $entry['description'],
                (float) $entry['debit'],
                (float) $entry['credit'],
                (float) $entry['balance'],
            ];
        }
        $rows[] = [$date->format('Y-m-d'), '', '', 'Closing Balance', '', '', (float) $summary['closing_balance']];

        $filename = sprintf('cash-book-%s.%s', $scope, $format);
        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;

        return Excel::download(new ReportRowsExport($rows), $filename, $writerType);
    }

    public function arrears(Request $request): View
    {
        [$scope, $consolidated] = $this->resolveScope($request);
        $classId = $request->input('class_id');
        $asOfDate = Carbon::today()->startOfDay();

        $query = $this->buildArrearsQuery($consolidated, $classId, $asOfDate);
        [$agingSummary, $classAgingSummary] = $this->buildAgingSummaries((clone $query)->get(), $asOfDate, $consolidated);

        $arrears = $query->paginate(20);
        $arrears->setCollection(
            $arrears->getCollection()->map(function (Invoice $invoice) use ($asOfDate) {
                $dueDate = Carbon::parse($invoice->due_date)->startOfDay();
                $agingDays = $this->computeAgingDaysFromDueDate($dueDate, $asOfDate);
                $bucketKey = $this->resolveAgingBucket($agingDays);

                $invoice->aging_days = $agingDays;
                $invoice->aging_bucket = self::agingLabel($bucketKey);
                $invoice->aging_bucket_key = $bucketKey;

                return $invoice;
            })
        );

        $classQuery = SchoolClass::query();
        if ($consolidated) {
            $classQuery->withoutGlobalScope('unit');
        }
        $classes = $classQuery->get();

        return view('reports.arrears', compact('arrears', 'classes', 'classId', 'scope', 'consolidated', 'agingSummary', 'classAgingSummary', 'asOfDate'));
    }

    public function arrearsExport(Request $request): BinaryFileResponse
    {
        [$scope, $consolidated] = $this->resolveScope($request);
        $classId = $request->input('class_id');
        $asOfDate = Carbon::today()->startOfDay();
        $format = strtolower((string) $request->input('format', 'xlsx'));
        if (!in_array($format, ['xlsx', 'csv'], true)) {
            $format = 'xlsx';
        }

        $arrears = $this->buildArrearsQuery($consolidated, $classId, $asOfDate)->get();
        [$agingSummary, $classAgingSummary] = $this->buildAgingSummaries($arrears, $asOfDate, $consolidated);

        $rows = [];
        $rows[] = ['ARREARS AGING ANALYSIS'];
        $rows[] = ['As Of', $asOfDate->format('Y-m-d')];
        $rows[] = ['Scope', strtoupper($scope)];
        $rows[] = [];

        $rows[] = ['SUMMARY BY AGING BUCKET'];
        $rows[] = ['Bucket', 'Count', 'Amount'];
        foreach (array_keys(self::AGING_BUCKETS) as $bucketKey) {
            $bucket = $agingSummary[$bucketKey];
            $rows[] = [$bucket['label'], $bucket['count'], (float) $bucket['amount']];
        }
        $rows[] = [];

        $rows[] = ['SUMMARY BY CLASS'];
        $rows[] = [
            'Class',
            '0-30 Count',
            '0-30 Amount',
            '31-60 Count',
            '31-60 Amount',
            '61-90 Count',
            '61-90 Amount',
            '>90 Count',
            '>90 Amount',
            'Total Count',
            'Total Amount',
        ];

        foreach ($classAgingSummary as $classLabel => $summary) {
            $totalCount = array_sum(array_column($summary, 'count'));
            $totalAmount = array_sum(array_column($summary, 'amount'));
            $rows[] = [
                $classLabel,
                $summary['current']['count'],
                (float) $summary['current']['amount'],
                $summary['d31_60']['count'],
                (float) $summary['d31_60']['amount'],
                $summary['d61_90']['count'],
                (float) $summary['d61_90']['amount'],
                $summary['d90_plus']['count'],
                (float) $summary['d90_plus']['amount'],
                $totalCount,
                (float) $totalAmount,
            ];
        }
        $rows[] = [];

        $rows[] = ['DETAIL'];
        $rows[] = ['Invoice', 'Student', 'Class', 'Due Date', 'Aging Days', 'Aging Bucket', 'Total', 'Already Paid', 'Outstanding'];

        foreach ($arrears as $invoice) {
            $dueDate = Carbon::parse($invoice->due_date)->startOfDay();
            $agingDays = $this->computeAgingDaysFromDueDate($dueDate, $asOfDate);
            $bucketKey = $this->resolveAgingBucket($agingDays);
            $rows[] = [
                $invoice->invoice_number,
                $invoice->student?->name ?? '-',
                $this->buildClassLabel($invoice, $consolidated),
                $dueDate->format('Y-m-d'),
                $agingDays,
                self::agingLabel($bucketKey),
                (float) $invoice->total_amount,
                (float) ($invoice->settled_amount ?? 0),
                (float) ($invoice->outstanding_amount ?? 0),
            ];
        }

        $filename = sprintf('arrears-aging-%s.%s', $scope, $format);
        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;

        return Excel::download(new ArrearsAgingExport($rows), $filename, $writerType);
    }

    private function buildDailyEntries(bool $consolidated, string $date): Collection
    {
        $settlementQuery = Settlement::query();
        if ($consolidated) {
            $settlementQuery->withoutGlobalScope('unit')->with([
                'unit',
                'student' => fn ($q) => $q->withoutGlobalScope('unit'),
                'student.schoolClass' => fn ($q) => $q->withoutGlobalScope('unit'),
                'allocations.invoice' => fn ($q) => $q->withoutGlobalScope('unit'),
                'creator',
            ]);
        } else {
            $settlementQuery->with(['student.schoolClass', 'allocations.invoice', 'creator']);
        }
        $settlements = $settlementQuery
            ->whereDate('payment_date', $date)
            ->where('status', 'completed')
            ->latest()
            ->get();

        $settlementEntries = $settlements->map(function (Settlement $settlement): array {
            return [
                'source' => __('message.source_settlement'),
                'unit_code' => $settlement->unit->code ?? null,
                'time' => $settlement->created_at?->format('H:i') ?? '-',
                'code' => $settlement->settlement_number,
                'model' => $settlement,
                'model_type' => 'settlement',
                'student' => $settlement->student?->name ?? '-',
                'class' => $settlement->student?->schoolClass?->name ?? '-',
                'type' => 'income',
                'payment_method' => $settlement->payment_method ?? '-',
                'cashier' => $settlement->creator?->name ?? '-',
                'items' => $settlement->allocations
                    ->map(function ($allocation) {
                        $invoiceNumber = $allocation->invoice->invoice_number ?? ('Invoice #' . $allocation->invoice_id);
                        return $invoiceNumber . ' - Rp ' . number_format((float) $allocation->amount, 0, ',', '.');
                    })
                    ->values()
                    ->all(),
                'amount' => (float) $settlement->allocated_amount,
                'sort_at' => $settlement->created_at,
            ];
        });

        $transactionQuery = Transaction::query();
        if ($consolidated) {
            $transactionQuery->withoutGlobalScope('unit')->with([
                'unit',
                'student' => fn ($q) => $q->withoutGlobalScope('unit'),
                'student.schoolClass' => fn ($q) => $q->withoutGlobalScope('unit'),
                'items.feeType' => fn ($q) => $q->withoutGlobalScope('unit'),
                'creator',
            ]);
        } else {
            $transactionQuery->with(['student.schoolClass', 'items.feeType', 'creator']);
        }
        $transactions = $transactionQuery
            ->whereDate('transaction_date', $date)
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->where('type', 'expense')
                    ->orWhere(function ($iq) {
                        $iq->where('type', 'income')
                            ->whereNull('student_id');
                    });
            })
            ->latest()
            ->get();

        $transactionEntries = $transactions->map(function (Transaction $transaction): array {
            return [
                'source' => __('message.source_direct_transaction'),
                'unit_code' => $transaction->unit->code ?? null,
                'time' => $transaction->created_at?->format('H:i') ?? '-',
                'code' => $transaction->transaction_number,
                'model' => $transaction,
                'model_type' => 'transaction',
                'student' => $transaction->student?->name ?? '-',
                'class' => $transaction->student?->schoolClass?->name ?? '-',
                'type' => $transaction->type,
                'payment_method' => $transaction->payment_method ?? '-',
                'cashier' => $transaction->creator?->name ?? '-',
                'items' => $transaction->items
                    ->map(fn ($item) => ($item->feeType->name ?? 'Item') . ' - Rp ' . number_format((float) $item->amount, 0, ',', '.'))
                    ->values()
                    ->all(),
                'amount' => $transaction->type === 'expense'
                    ? -1 * (float) $transaction->total_amount
                    : (float) $transaction->total_amount,
                'sort_at' => $transaction->created_at,
            ];
        });

        return $settlementEntries->concat($transactionEntries)
            ->sortByDesc('sort_at')
            ->values();
    }

    private function buildMonthlyEntries(bool $consolidated, int $month, int $year): Collection
    {
        $txQuery = Transaction::query();
        if ($consolidated) {
            $txQuery->withoutGlobalScope('unit')->with([
                'unit',
                'student' => fn ($q) => $q->withoutGlobalScope('unit'),
                'student.schoolClass' => fn ($q) => $q->withoutGlobalScope('unit'),
                'items.feeType' => fn ($q) => $q->withoutGlobalScope('unit'),
                'creator',
            ]);
        } else {
            $txQuery->with(['student.schoolClass', 'items.feeType', 'creator']);
        }
        $transactions = $txQuery
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->where('type', 'expense')
                    ->orWhere(function ($iq) {
                        $iq->where('type', 'income')
                            ->whereNull('student_id');
                    });
            })
            ->orderBy('transaction_date')
            ->get();

        $stlQuery = Settlement::query();
        if ($consolidated) {
            $stlQuery->withoutGlobalScope('unit')->with([
                'unit',
                'student' => fn ($q) => $q->withoutGlobalScope('unit'),
                'student.schoolClass' => fn ($q) => $q->withoutGlobalScope('unit'),
                'allocations.invoice' => fn ($q) => $q->withoutGlobalScope('unit'),
                'creator',
            ]);
        } else {
            $stlQuery->with(['student.schoolClass', 'allocations.invoice', 'creator']);
        }
        $settlements = $stlQuery
            ->whereMonth('payment_date', $month)
            ->whereYear('payment_date', $year)
            ->where('status', 'completed')
            ->orderBy('payment_date')
            ->get();

        $txEntries = $transactions->map(fn (Transaction $tx) => [
            'date' => Carbon::parse($tx->transaction_date),
            'code' => $tx->transaction_number,
            'source' => __('message.source_direct_transaction'),
            'model_type' => 'transaction',
            'model' => $tx,
            'student_name' => $tx->student?->name ?? '-',
            'class_name' => $tx->student?->schoolClass?->name ?? '-',
            'unit_code' => $tx->unit->code ?? null,
            'type' => $tx->type,
            'payment_method' => $tx->payment_method ?? '-',
            'cashier' => $tx->creator?->name ?? '-',
            'description' => $tx->items
                ->map(fn ($item) => ($item->feeType->name ?? 'Item') . ' - Rp ' . number_format((float) $item->amount, 0, ',', '.'))
                ->implode('; '),
            'amount' => $tx->type === 'expense' ? -1 * (float) $tx->total_amount : (float) $tx->total_amount,
        ]);

        $stlEntries = $settlements->map(fn (Settlement $stl) => [
            'date' => Carbon::parse($stl->payment_date),
            'code' => $stl->settlement_number,
            'source' => __('message.source_settlement'),
            'model_type' => 'settlement',
            'model' => $stl,
            'student_name' => $stl->student?->name ?? '-',
            'class_name' => $stl->student?->schoolClass?->name ?? '-',
            'unit_code' => $stl->unit->code ?? null,
            'type' => 'income',
            'payment_method' => $stl->payment_method ?? '-',
            'cashier' => $stl->creator?->name ?? '-',
            'description' => $stl->allocations
                ->map(fn ($allocation) => ($allocation->invoice?->invoice_number ?? ('Invoice #' . $allocation->invoice_id)) . ' - Rp ' . number_format((float) $allocation->amount, 0, ',', '.'))
                ->implode('; '),
            'amount' => (float) $stl->allocated_amount,
        ]);

        return $txEntries->concat($stlEntries)->sortBy('date')->values();
    }

    private function computeAgingDaysFromDueDate(Carbon $dueDate, Carbon $asOfDate): int
    {
        return max(0, $dueDate->diffInDays($asOfDate, false));
    }

    private function resolveAgingBucket(int $agingDays): string
    {
        foreach (self::AGING_BUCKETS as $key => $bucket) {
            $min = $bucket['min'];
            $max = $bucket['max'];
            if ($agingDays >= $min && ($max === null || $agingDays <= $max)) {
                return $key;
            }
        }

        return 'd90_plus';
    }

    private function buildArrearsQuery(bool $consolidated, mixed $classId, Carbon $asOfDate)
    {
        $settledByInvoice = DB::table('settlement_allocations as sa')
            ->join('settlements as s', 's.id', '=', 'sa.settlement_id')
            ->where('s.status', 'completed')
            ->selectRaw('sa.invoice_id, SUM(sa.amount) as settled_amount')
            ->groupBy('sa.invoice_id');

        $query = Invoice::query();

        if ($consolidated) {
            $query->withoutGlobalScope('unit')->with([
                'unit',
                'student' => fn ($q) => $q->withoutGlobalScope('unit'),
                'student.schoolClass' => fn ($q) => $q->withoutGlobalScope('unit'),
            ]);
        } else {
            $query->with(['student.schoolClass']);
        }

        $query->leftJoinSub($settledByInvoice, 'paid', function ($join) {
            $join->on('paid.invoice_id', '=', 'invoices.id');
        })
            ->whereDate('invoices.due_date', '<', $asOfDate->toDateString())
            ->whereRaw('invoices.total_amount > COALESCE(paid.settled_amount, 0)')
            ->select('invoices.*')
            ->selectRaw('COALESCE(paid.settled_amount, 0) as settled_amount')
            ->selectRaw('(invoices.total_amount - COALESCE(paid.settled_amount, 0)) as outstanding_amount')
            ->orderBy('invoices.due_date');

        if ($classId) {
            $query->whereHas('student', function ($q) use ($classId, $consolidated) {
                if ($consolidated) {
                    $q->withoutGlobalScope('unit');
                }
                $q->where('class_id', $classId);
            });
        }

        return $query;
    }

    private function buildAgingSummaries($invoices, Carbon $asOfDate, bool $consolidated): array
    {
        $agingSummary = collect(self::AGING_BUCKETS)->mapWithKeys(fn ($bucket, $key) => [
            $key => [
                'label' => __($bucket['label_key']),
                'count' => 0,
                'amount' => 0.0,
            ],
        ])->all();

        $classAgingSummary = [];

        foreach ($invoices as $invoice) {
            $dueDate = Carbon::parse($invoice->due_date)->startOfDay();
            $agingDays = $this->computeAgingDaysFromDueDate($dueDate, $asOfDate);
            $bucketKey = $this->resolveAgingBucket($agingDays);
            $amount = (float) ($invoice->outstanding_amount ?? 0);

            $agingSummary[$bucketKey]['count']++;
            $agingSummary[$bucketKey]['amount'] += $amount;

            $classLabel = $this->buildClassLabel($invoice, $consolidated);
            if (!isset($classAgingSummary[$classLabel])) {
                $classAgingSummary[$classLabel] = collect(self::AGING_BUCKETS)->mapWithKeys(fn ($bucket, $key) => [
                    $key => ['count' => 0, 'amount' => 0.0],
                ])->all();
            }
            $classAgingSummary[$classLabel][$bucketKey]['count']++;
            $classAgingSummary[$classLabel][$bucketKey]['amount'] += $amount;
        }

        ksort($classAgingSummary);

        return [$agingSummary, $classAgingSummary];
    }

    private function buildClassLabel(Invoice $invoice, bool $consolidated): string
    {
        $className = $invoice->student?->schoolClass?->name ?? '-';
        if (!$consolidated) {
            return $className;
        }

        $unitCode = $invoice->unit?->code ?? 'NA';

        return "{$unitCode} - {$className}";
    }

    private function resolveDateRange(Request $request, int $defaultDays = 30): array
    {
        $dateTo = Carbon::parse((string) $request->input('date_to', now()->toDateString()))->endOfDay();
        $dateFrom = Carbon::parse((string) $request->input('date_from', now()->subDays($defaultDays)->toDateString()))->startOfDay();

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        return [$dateFrom, $dateTo];
    }

    private function buildArOutstandingQuery(bool $consolidated, Carbon $dateFrom, Carbon $dateTo, mixed $classId, mixed $categoryId, mixed $studentId)
    {
        $settledByInvoice = DB::table('settlement_allocations as sa')
            ->join('settlements as s', 's.id', '=', 'sa.settlement_id')
            ->where('s.status', 'completed')
            ->selectRaw('sa.invoice_id, SUM(sa.amount) as settled_amount')
            ->groupBy('sa.invoice_id');

        $query = Invoice::query();
        if ($consolidated) {
            $query->withoutGlobalScope('unit')->with([
                'unit',
                'student' => fn ($q) => $q->withoutGlobalScope('unit'),
                'student.schoolClass' => fn ($q) => $q->withoutGlobalScope('unit'),
                'student.category' => fn ($q) => $q->withoutGlobalScope('unit'),
            ]);
        } else {
            $query->with(['student.schoolClass', 'student.category']);
        }

        $query->leftJoinSub($settledByInvoice, 'paid', function ($join) {
            $join->on('paid.invoice_id', '=', 'invoices.id');
        })
            ->whereBetween('invoices.due_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereRaw('invoices.total_amount > COALESCE(paid.settled_amount, 0)')
            ->select('invoices.*')
            ->selectRaw('COALESCE(paid.settled_amount, 0) as settled_amount')
            ->selectRaw('(invoices.total_amount - COALESCE(paid.settled_amount, 0)) as outstanding_amount')
            ->orderBy('invoices.due_date');

        if ($classId) {
            $query->whereHas('student', function ($q) use ($classId, $consolidated) {
                if ($consolidated) {
                    $q->withoutGlobalScope('unit');
                }
                $q->where('class_id', $classId);
            });
        }
        if ($categoryId) {
            $query->whereHas('student', function ($q) use ($categoryId, $consolidated) {
                if ($consolidated) {
                    $q->withoutGlobalScope('unit');
                }
                $q->where('category_id', $categoryId);
            });
        }
        if ($studentId) {
            $query->where('invoices.student_id', $studentId);
        }

        return $query;
    }

    private function buildCollectionEntries(bool $consolidated, Carbon $dateFrom, Carbon $dateTo, ?string $paymentMethod, mixed $cashierId): Collection
    {
        $settlementQuery = Settlement::query();
        if ($consolidated) {
            $settlementQuery->withoutGlobalScope('unit')->with(['unit', 'student' => fn ($q) => $q->withoutGlobalScope('unit'), 'creator']);
        } else {
            $settlementQuery->with(['student', 'creator']);
        }
        $settlementQuery->where('status', 'completed')
            ->whereBetween('payment_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);
        if ($paymentMethod) {
            $settlementQuery->where('payment_method', $paymentMethod);
        }
        if ($cashierId) {
            $settlementQuery->where('created_by', $cashierId);
        }
        $settlements = $settlementQuery->get()->map(fn (Settlement $s) => [
            'date' => $s->payment_date,
            'datetime' => $s->created_at,
            'source' => 'Settlement',
            'code' => $s->settlement_number,
            'student' => $s->student?->name ?? '-',
            'payment_method' => $s->payment_method,
            'cashier' => $s->creator?->name ?? '-',
            'amount' => (float) $s->allocated_amount,
            'unit_code' => $s->unit->code ?? null,
        ]);

        $transactionQuery = Transaction::query();
        if ($consolidated) {
            $transactionQuery->withoutGlobalScope('unit')->with(['unit', 'creator']);
        } else {
            $transactionQuery->with(['creator']);
        }
        $transactionQuery->where('status', 'completed')
            ->whereBetween('transaction_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->where(function ($q) {
                $q->where('type', 'expense')
                    ->orWhere(function ($iq) {
                        $iq->where('type', 'income')->whereNull('student_id');
                    });
            });
        if ($paymentMethod) {
            $transactionQuery->where('payment_method', $paymentMethod);
        }
        if ($cashierId) {
            $transactionQuery->where('created_by', $cashierId);
        }
        $transactions = $transactionQuery->get()->map(fn (Transaction $t) => [
            'date' => $t->transaction_date,
            'datetime' => $t->created_at,
            'source' => 'Direct Transaction',
            'code' => $t->transaction_number,
            'student' => '-',
            'payment_method' => $t->payment_method,
            'cashier' => $t->creator?->name ?? '-',
            'amount' => $t->type === 'expense' ? -1 * (float) $t->total_amount : (float) $t->total_amount,
            'unit_code' => $t->unit->code ?? null,
        ]);

        return $settlements->concat($transactions)->sortByDesc('datetime')->values();
    }

    private function buildStudentStatement(bool $consolidated, int $studentId, Carbon $dateFrom, Carbon $dateTo): array
    {
        $invoiceQuery = Invoice::query();
        if ($consolidated) {
            $invoiceQuery->withoutGlobalScope('unit');
        }

        $openingDebit = (float) (clone $invoiceQuery)
            ->where('student_id', $studentId)
            ->whereDate('invoice_date', '<', $dateFrom->toDateString())
            ->sum('total_amount');

        $openingCreditQuery = SettlementAllocation::query()
            ->join('settlements', 'settlements.id', '=', 'settlement_allocations.settlement_id')
            ->join('invoices', 'invoices.id', '=', 'settlement_allocations.invoice_id')
            ->where('settlements.status', 'completed')
            ->where('invoices.student_id', $studentId)
            ->whereDate('settlements.payment_date', '<', $dateFrom->toDateString());
        if (!$consolidated) {
            $openingCreditQuery->where('settlements.unit_id', session('current_unit_id'));
        }
        $openingCredit = (float) $openingCreditQuery->sum('settlement_allocations.amount');
        $openingBalance = $openingDebit - $openingCredit;

        $invoiceRows = (clone $invoiceQuery)
            ->where('student_id', $studentId)
            ->whereBetween('invoice_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->get()
            ->map(fn (Invoice $invoice) => [
                'date' => $invoice->invoice_date,
                'reference' => $invoice->invoice_number,
                'description' => 'Invoice',
                'debit' => (float) $invoice->total_amount,
                'credit' => 0.0,
                'sort_at' => $invoice->created_at,
            ]);

        $settlementRowsQuery = SettlementAllocation::query()
            ->join('settlements', 'settlements.id', '=', 'settlement_allocations.settlement_id')
            ->join('invoices', 'invoices.id', '=', 'settlement_allocations.invoice_id')
            ->where('settlements.status', 'completed')
            ->where('invoices.student_id', $studentId)
            ->whereBetween('settlements.payment_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->select([
                'settlements.payment_date',
                'settlements.settlement_number',
                'settlement_allocations.amount',
                'settlements.created_at',
            ]);
        if (!$consolidated) {
            $settlementRowsQuery->where('settlements.unit_id', session('current_unit_id'));
        }
        $settlementRows = $settlementRowsQuery->get()->map(fn ($row) => [
            'date' => $row->payment_date,
            'reference' => $row->settlement_number,
            'description' => 'Settlement',
            'debit' => 0.0,
            'credit' => (float) $row->amount,
            'sort_at' => $row->created_at,
        ]);

        $rows = $invoiceRows->concat($settlementRows)
            ->sortBy([['date', 'asc'], ['sort_at', 'asc']])
            ->values();

        $running = $openingBalance;
        $rows = $rows->map(function (array $row) use (&$running) {
            $running += (float) $row['debit'];
            $running -= (float) $row['credit'];
            $row['balance'] = $running;
            return $row;
        });

        $summary = [
            'opening_balance' => $openingBalance,
            'total_debit' => (float) $rows->sum('debit'),
            'total_credit' => (float) $rows->sum('credit'),
            'closing_balance' => $running,
        ];

        return [$rows, $summary];
    }

    private function buildCashBook(bool $consolidated, Carbon $date): array
    {
        $settlementBase = Settlement::query()->where('status', 'completed')->where('payment_method', 'cash');
        $transactionBase = Transaction::query()->where('status', 'completed')->where('payment_method', 'cash');

        if ($consolidated) {
            $settlementBase->withoutGlobalScope('unit')->with(['unit']);
            $transactionBase->withoutGlobalScope('unit')->with(['unit']);
        }

        $openingSettlement = (clone $settlementBase)
            ->whereDate('payment_date', '<', $date->toDateString())
            ->sum('allocated_amount');
        $openingIncome = (clone $transactionBase)
            ->whereDate('transaction_date', '<', $date->toDateString())
            ->where('type', 'income')
            ->whereNull('student_id')
            ->sum('total_amount');
        $openingExpense = (clone $transactionBase)
            ->whereDate('transaction_date', '<', $date->toDateString())
            ->where('type', 'expense')
            ->sum('total_amount');
        $openingBalance = (float) $openingSettlement + (float) $openingIncome - (float) $openingExpense;

        $settlementRows = (clone $settlementBase)
            ->whereDate('payment_date', $date->toDateString())
            ->get()
            ->map(fn (Settlement $s) => [
                'time' => $s->created_at?->format('H:i') ?? '-',
                'source' => 'Settlement',
                'code' => $s->settlement_number,
                'description' => $s->notes ?: 'Cash collection',
                'debit' => (float) $s->allocated_amount,
                'credit' => 0.0,
                'sort_at' => $s->created_at,
                'unit_code' => $s->unit->code ?? null,
            ]);

        $transactionRows = (clone $transactionBase)
            ->whereDate('transaction_date', $date->toDateString())
            ->where(function ($q) {
                $q->where('type', 'expense')
                    ->orWhere(function ($iq) {
                        $iq->where('type', 'income')->whereNull('student_id');
                    });
            })
            ->get()
            ->map(fn (Transaction $t) => [
                'time' => $t->created_at?->format('H:i') ?? '-',
                'source' => 'Transaction',
                'code' => $t->transaction_number,
                'description' => $t->description ?: ($t->type === 'expense' ? 'Cash expense' : 'Cash income'),
                'debit' => $t->type === 'income' ? (float) $t->total_amount : 0.0,
                'credit' => $t->type === 'expense' ? (float) $t->total_amount : 0.0,
                'sort_at' => $t->created_at,
                'unit_code' => $t->unit->code ?? null,
            ]);

        $rows = $settlementRows->concat($transactionRows)->sortBy('sort_at')->values();
        $running = $openingBalance;
        $rows = $rows->map(function (array $row) use (&$running) {
            $running += (float) $row['debit'];
            $running -= (float) $row['credit'];
            $row['balance'] = $running;
            return $row;
        });

        $summary = [
            'opening_balance' => $openingBalance,
            'total_debit' => (float) $rows->sum('debit'),
            'total_credit' => (float) $rows->sum('credit'),
            'closing_balance' => $running,
        ];

        return [$rows, $summary];
    }

    private function paginateCollection(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->input('page', 1));
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }
}
