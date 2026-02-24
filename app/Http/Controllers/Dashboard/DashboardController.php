<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\Unit;
use App\Services\ReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    public function index(Request $request): View
    {
        $scope = $request->input('scope', 'unit');
        $consolidated = $scope === 'all' && auth()->user()->hasRole('super_admin');
        $scope = $consolidated ? 'all' : 'unit';
        $now = now();
        $today = $now->toDateString();
        $scopeCacheKey = $consolidated
            ? 'all'
            : 'unit-' . (string) session('current_unit_id', auth()->user()->unit_id ?? 'none');
        $cacheBucket = intdiv((int) $now->format('i'), 5);
        $cacheVersion = (int) Cache::get('cache-version:dashboard-metrics', 1);

        $metrics = Cache::remember(
            "dashboard-metrics:v{$cacheVersion}:{$scopeCacheKey}:{$now->format('Y-m-d-H')}:{$cacheBucket}",
            now()->addMinutes(5),
            function () use ($consolidated, $today, $now): array {
                $todayDirectIncomeQuery = Transaction::where('status', 'completed')
                    ->where('type', 'income')
                    ->whereNull('student_id')
                    ->whereDate('transaction_date', $today);

                $monthDirectIncomeQuery = Transaction::where('status', 'completed')
                    ->where('type', 'income')
                    ->whereNull('student_id')
                    ->whereMonth('transaction_date', $now->month)
                    ->whereYear('transaction_date', $now->year);

                $todayExpenseQuery = Transaction::where('status', 'completed')
                    ->where('type', 'expense')
                    ->whereDate('transaction_date', $today);

                $monthExpenseQuery = Transaction::where('status', 'completed')
                    ->where('type', 'expense')
                    ->whereMonth('transaction_date', $now->month)
                    ->whereYear('transaction_date', $now->year);

                $todaySettlementQuery = Settlement::where('status', 'completed')
                    ->whereDate('payment_date', $today);

                $monthSettlementQuery = Settlement::where('status', 'completed')
                    ->whereMonth('payment_date', $now->month)
                    ->whereYear('payment_date', $now->year);

                if ($consolidated) {
                    $todayDirectIncomeQuery->withoutGlobalScope('unit');
                    $monthDirectIncomeQuery->withoutGlobalScope('unit');
                    $todayExpenseQuery->withoutGlobalScope('unit');
                    $monthExpenseQuery->withoutGlobalScope('unit');
                    $todaySettlementQuery->withoutGlobalScope('unit');
                    $monthSettlementQuery->withoutGlobalScope('unit');
                }

                $todayIncome = $todaySettlementQuery->sum('allocated_amount')
                    + $todayDirectIncomeQuery->sum('total_amount')
                    - $todayExpenseQuery->sum('total_amount');
                $monthIncome = $monthSettlementQuery->sum('allocated_amount')
                    + $monthDirectIncomeQuery->sum('total_amount')
                    - $monthExpenseQuery->sum('total_amount');
                $totalArrears = $this->buildArrearsTotalsQuery($consolidated, $today)->value('total_arrears') ?? 0;

                return [
                    'todayIncome' => $todayIncome,
                    'monthIncome' => $monthIncome,
                    'totalArrears' => (float) $totalArrears,
                    'unitBreakdown' => $consolidated ? $this->buildUnitBreakdown($today, $now) : [],
                ];
            }
        );

        $recentQuery = Transaction::query()
            ->where('status', 'completed')
            ->latest('transaction_date')
            ->limit(10);

        if ($consolidated) {
            $recentQuery->withoutGlobalScope('unit')->with([
                'unit',
                'student' => fn ($q) => $q->withoutGlobalScope('unit'),
                'student.schoolClass' => fn ($q) => $q->withoutGlobalScope('unit'),
                'creator',
            ]);
        } else {
            $recentQuery->with(['student.schoolClass', 'creator']);
        }

        $todayIncome = $metrics['todayIncome'];
        $monthIncome = $metrics['monthIncome'];
        $totalArrears = (float) $metrics['totalArrears'];
        $recentTransactions = $recentQuery->get();

        $chartData = $this->reportService->getChartData(6, $consolidated);
        $unitBreakdown = $metrics['unitBreakdown'];

        return view('dashboard', compact(
            'todayIncome',
            'monthIncome',
            'totalArrears',
            'recentTransactions',
            'chartData',
            'scope',
            'consolidated',
            'unitBreakdown',
        ));
    }

    private function buildArrearsTotalsQuery(bool $consolidated, string $today)
    {
        $settledByInvoice = DB::table('settlement_allocations as sa')
            ->join('settlements as s', 's.id', '=', 'sa.settlement_id')
            ->where('s.status', 'completed')
            ->selectRaw('sa.invoice_id, SUM(sa.amount) as settled_amount')
            ->groupBy('sa.invoice_id');

        $query = Invoice::query();
        if ($consolidated) {
            $query->withoutGlobalScope('unit');
        }

        return $query->leftJoinSub($settledByInvoice, 'paid', function ($join) {
            $join->on('paid.invoice_id', '=', 'invoices.id');
        })
            ->whereDate('invoices.due_date', '<', $today)
            ->whereRaw('invoices.total_amount > COALESCE(paid.settled_amount, 0)')
            ->selectRaw('COALESCE(SUM(invoices.total_amount - COALESCE(paid.settled_amount, 0)), 0) as total_arrears');
    }

    private function buildUnitBreakdown(string $today, Carbon $now): array
    {
        $units = Unit::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'name', 'code']);

        $todayTxByUnit = Transaction::withoutGlobalScope('unit')
            ->where('status', 'completed')
            ->whereDate('transaction_date', $today)
            ->selectRaw("unit_id, SUM(CASE WHEN type = 'income' AND student_id IS NULL THEN total_amount ELSE 0 END) as direct_income, SUM(CASE WHEN type = 'expense' THEN total_amount ELSE 0 END) as expense")
            ->groupBy('unit_id')
            ->get()
            ->keyBy('unit_id');

        $monthTxByUnit = Transaction::withoutGlobalScope('unit')
            ->where('status', 'completed')
            ->whereMonth('transaction_date', $now->month)
            ->whereYear('transaction_date', $now->year)
            ->selectRaw("unit_id, SUM(CASE WHEN type = 'income' AND student_id IS NULL THEN total_amount ELSE 0 END) as direct_income, SUM(CASE WHEN type = 'expense' THEN total_amount ELSE 0 END) as expense")
            ->groupBy('unit_id')
            ->get()
            ->keyBy('unit_id');

        $todaySettlementByUnit = Settlement::withoutGlobalScope('unit')
            ->where('status', 'completed')
            ->whereDate('payment_date', $today)
            ->groupBy('unit_id')
            ->selectRaw('unit_id, SUM(allocated_amount) as settlement_income')
            ->pluck('settlement_income', 'unit_id');

        $monthSettlementByUnit = Settlement::withoutGlobalScope('unit')
            ->where('status', 'completed')
            ->whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->groupBy('unit_id')
            ->selectRaw('unit_id, SUM(allocated_amount) as settlement_income')
            ->pluck('settlement_income', 'unit_id');

        $settledByInvoice = DB::table('settlement_allocations as sa')
            ->join('settlements as s', 's.id', '=', 'sa.settlement_id')
            ->where('s.status', 'completed')
            ->selectRaw('sa.invoice_id, SUM(sa.amount) as settled_amount')
            ->groupBy('sa.invoice_id');

        $arrearsByUnit = Invoice::withoutGlobalScope('unit')
            ->leftJoinSub($settledByInvoice, 'paid', function ($join) {
                $join->on('paid.invoice_id', '=', 'invoices.id');
            })
            ->whereDate('invoices.due_date', '<', $today)
            ->whereRaw('invoices.total_amount > COALESCE(paid.settled_amount, 0)')
            ->selectRaw('invoices.unit_id, SUM(invoices.total_amount - COALESCE(paid.settled_amount, 0)) as arrears')
            ->groupBy('invoices.unit_id')
            ->pluck('arrears', 'invoices.unit_id');

        return $units->map(function (Unit $unit) use (
            $todayTxByUnit,
            $monthTxByUnit,
            $todaySettlementByUnit,
            $monthSettlementByUnit,
            $arrearsByUnit
        ): array {
            $todayTx = $todayTxByUnit->get($unit->id);
            $monthTx = $monthTxByUnit->get($unit->id);

            $todayIncome = (float) ($todaySettlementByUnit[$unit->id] ?? 0)
                + (float) ($todayTx->direct_income ?? 0)
                - (float) ($todayTx->expense ?? 0);

            $monthIncome = (float) ($monthSettlementByUnit[$unit->id] ?? 0)
                + (float) ($monthTx->direct_income ?? 0)
                - (float) ($monthTx->expense ?? 0);

            return [
                'name' => $unit->name,
                'code' => $unit->code,
                'today_income' => $todayIncome,
                'month_income' => $monthIncome,
                'arrears' => (float) ($arrearsByUnit[$unit->id] ?? 0),
            ];
        })->values()->all();
    }
}
