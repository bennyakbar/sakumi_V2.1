<?php

namespace App\Http\Controllers\Report;

use App\Exports\ArrearsAgingExport;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\SchoolClass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // --- Settlements ---
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

        // --- Direct Transactions ---
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

        // --- Merge & sort ---
        $entries = $settlementEntries->concat($transactionEntries)
            ->sortByDesc('sort_at')
            ->values();

        $totalAmount = $entries->sum('amount');

        return view('reports.daily', compact('entries', 'date', 'totalAmount', 'scope', 'consolidated'));
    }

    public function monthly(Request $request): View
    {
        [$scope, $consolidated] = $this->resolveScope($request);
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        // --- Transactions ---
        $txQuery = Transaction::query();

        if ($consolidated) {
            $txQuery->withoutGlobalScope('unit')->with([
                'unit',
                'student' => fn ($q) => $q->withoutGlobalScope('unit'),
                'student.schoolClass' => fn ($q) => $q->withoutGlobalScope('unit'),
                'items.feeType' => fn ($q) => $q->withoutGlobalScope('unit'),
            ]);
        } else {
            $txQuery->with(['student.schoolClass', 'items.feeType']);
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

        // --- Settlements ---
        $stlQuery = Settlement::query();

        if ($consolidated) {
            $stlQuery->withoutGlobalScope('unit')->with([
                'unit',
                'student' => fn ($q) => $q->withoutGlobalScope('unit'),
                'student.schoolClass' => fn ($q) => $q->withoutGlobalScope('unit'),
                'allocations.invoice' => fn ($q) => $q->withoutGlobalScope('unit'),
            ]);
        } else {
            $stlQuery->with(['student.schoolClass', 'allocations.invoice']);
        }

        $settlements = $stlQuery
            ->whereMonth('payment_date', $month)
            ->whereYear('payment_date', $year)
            ->where('status', 'completed')
            ->orderBy('payment_date')
            ->get();

        // --- Build unified entries for detail table ---
        $txEntries = $transactions->map(fn (Transaction $tx) => (object) [
            'date' => $tx->transaction_date,
            'code' => $tx->transaction_number,
            'source' => 'Transaksi Langsung',
            'model_type' => 'transaction',
            'model' => $tx,
            'student_name' => $tx->student?->name ?? '-',
            'unit_code' => $tx->unit->code ?? null,
            'type' => $tx->type,
            'amount' => $tx->type === 'expense' ? -1 * (float) $tx->total_amount : (float) $tx->total_amount,
        ]);

        $stlEntries = $settlements->map(fn (Settlement $stl) => (object) [
            'date' => \Carbon\Carbon::parse($stl->payment_date),
            'code' => $stl->settlement_number,
            'source' => 'Settlement',
            'model_type' => 'settlement',
            'model' => $stl,
            'student_name' => $stl->student?->name ?? '-',
            'unit_code' => $stl->unit->code ?? null,
            'type' => 'income',
            'amount' => (float) $stl->allocated_amount,
        ]);

        $entries = $txEntries->concat($stlEntries)->sortBy('date')->values();

        // --- Daily summary (combined) ---
        $dailySummary = $entries->groupBy(fn ($e) => $e->date->format('Y-m-d'))
            ->map(fn ($dayEntries) => $dayEntries->sum('amount'));

        $totalAmount = $entries->sum('amount');

        return view('reports.monthly', compact('entries', 'dailySummary', 'month', 'year', 'totalAmount', 'scope', 'consolidated'));
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
}
