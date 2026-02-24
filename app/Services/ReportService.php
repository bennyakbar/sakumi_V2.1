<?php

namespace App\Services;

use App\Models\Settlement;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getDailyReport(string $date): array
    {
        $transactions = Transaction::with('items.feeType', 'student', 'creator')
            ->where('transaction_date', $date)
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->where('type', 'expense')
                    ->orWhere(function ($iq) {
                        $iq->where('type', 'income')
                            ->whereNull('student_id');
                    });
            })
            ->orderBy('created_at')
            ->get();

        return [
            'date' => $date,
            'income' => $transactions->where('type', 'income')->sum('total_amount'),
            'expense' => $transactions->where('type', 'expense')->sum('total_amount'),
            'balance' => $transactions->where('type', 'income')->sum('total_amount')
                - $transactions->where('type', 'expense')->sum('total_amount'),
            'transactions' => $transactions,
            'income_by_type' => $transactions->where('type', 'income')
                ->flatMap->items
                ->groupBy('fee_type_id')
                ->map(fn ($group) => [
                    'fee_type' => $group->first()->feeType->name,
                    'total' => $group->sum('amount'),
                    'count' => $group->count(),
                ]),
        ];
    }

    public function getMonthlyReport(int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $transactions = Transaction::where('status', 'completed')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('type', 'expense')
                    ->orWhere(function ($iq) {
                        $iq->where('type', 'income')
                            ->whereNull('student_id');
                    });
            })
            ->get();

        $dailyStats = Transaction::where('status', 'completed')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('type', 'expense')
                    ->orWhere(function ($iq) {
                        $iq->where('type', 'income')
                            ->whereNull('student_id');
                    });
            })
            ->select(
                'transaction_date',
                'type',
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('transaction_date', 'type')
            ->orderBy('transaction_date')
            ->get();

        return [
            'month' => $month,
            'year' => $year,
            'income' => $transactions->where('type', 'income')->sum('total_amount'),
            'expense' => $transactions->where('type', 'expense')->sum('total_amount'),
            'balance' => $transactions->where('type', 'income')->sum('total_amount')
                - $transactions->where('type', 'expense')->sum('total_amount'),
            'transaction_count' => $transactions->count(),
            'daily_stats' => $dailyStats,
        ];
    }

    public function getChartData(int $months = 6, bool $consolidated = false): array
    {
        $now = now();
        $scopeCacheKey = $consolidated
            ? 'all'
            : 'unit-' . (string) session('current_unit_id', 'none');
        $cacheBucket = intdiv((int) $now->format('i'), 10);
        $cacheVersion = (int) Cache::get('cache-version:chart-data', 1);

        return Cache::remember(
            "chart-data:v{$cacheVersion}:{$scopeCacheKey}:{$months}:{$now->format('Y-m-d-H')}:{$cacheBucket}",
            now()->addMinutes(10),
            function () use ($months, $consolidated, $now): array {
                $start = $now->copy()->startOfMonth()->subMonths($months - 1);
                $end = $now->copy()->endOfMonth();

                $transactionBuilder = Transaction::query();
                $settlementBuilder = Settlement::query();
                if ($consolidated) {
                    $transactionBuilder->withoutGlobalScope('unit');
                    $settlementBuilder->withoutGlobalScope('unit');
                }

                $transactionQuery = $transactionBuilder
                    ->where('status', 'completed')
                    ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                    ->where(function ($query) {
                        $query->where('type', 'expense')
                            ->orWhere(function ($incomeQuery) {
                                $incomeQuery->where('type', 'income')
                                    ->whereNull('student_id');
                            });
                    })
                    ->get(['transaction_date', 'type', 'total_amount']);

                $settlementQuery = $settlementBuilder
                    ->where('status', 'completed')
                    ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
                    ->get(['payment_date', 'allocated_amount']);

                $incomeByMonth = [];
                $expenseByMonth = [];

                foreach ($transactionQuery as $transaction) {
                    $monthKey = Carbon::parse($transaction->transaction_date)->format('Y-m');
                    if ($transaction->type === 'expense') {
                        $expenseByMonth[$monthKey] = ($expenseByMonth[$monthKey] ?? 0) + (float) $transaction->total_amount;
                        continue;
                    }

                    $incomeByMonth[$monthKey] = ($incomeByMonth[$monthKey] ?? 0) + (float) $transaction->total_amount;
                }

                foreach ($settlementQuery as $settlement) {
                    $monthKey = Carbon::parse($settlement->payment_date)->format('Y-m');
                    $incomeByMonth[$monthKey] = ($incomeByMonth[$monthKey] ?? 0) + (float) $settlement->allocated_amount;
                }

                $labels = [];
                $incomeData = [];
                $expenseData = [];

                for ($i = $months - 1; $i >= 0; $i--) {
                    $date = $now->copy()->subMonths($i);
                    $monthKey = $date->format('Y-m');

                    $labels[] = $date->translatedFormat('M Y');
                    $incomeData[] = (float) ($incomeByMonth[$monthKey] ?? 0);
                    $expenseData[] = (float) ($expenseByMonth[$monthKey] ?? 0);
                }

                return compact('labels', 'incomeData', 'expenseData');
            }
        );
    }
}
