<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-800">{{ __('Dashboard') }}</h2>
                @if($consolidated ?? false)
                    <x-finance.badge color="indigo" size="md" dot class="mt-1.5">
                        {{ __('dashboard.all_units') }}
                    </x-finance.badge>
                @endif
            </div>
            @if(auth()->user()->hasRole('super_admin'))
                <a href="{{ route('dashboard', ['scope' => ($scope ?? 'unit') === 'all' ? 'unit' : 'all']) }}"
                   class="inline-flex items-center gap-x-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-150
                          {{ ($scope ?? 'unit') === 'all'
                              ? 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2'
                              : 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50' }}">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                    </svg>
                    {{ ($scope ?? 'unit') === 'all' ? __('dashboard.current_unit') : __('dashboard.all_units') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Today's Net Cash --}}
                <x-finance.summary
                    :title="__('dashboard.net_cash_today')"
                    :value="formatRupiah($todayIncome)"
                    color="emerald"
                >
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot>
                </x-finance.summary>

                {{-- Monthly Net Cash --}}
                <x-finance.summary
                    :title="__('dashboard.net_cash_month')"
                    :value="formatRupiah($monthIncome)"
                    color="blue"
                >
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </x-slot>
                </x-finance.summary>

                {{-- Total Arrears --}}
                <x-finance.summary
                    :title="__('dashboard.total_arrears')"
                    :value="formatRupiah($totalArrears)"
                    color="red"
                >
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </x-slot>
                </x-finance.summary>

                {{-- Total Expenses (from chart data, current month) --}}
                <x-finance.summary
                    :title="__('dashboard.expense')"
                    :value="formatRupiah(end($chartData['expenseData']) ?: 0)"
                    color="amber"
                >
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </x-slot>
                </x-finance.summary>
            </div>

            {{-- Pending Expense Drafts --}}
            @if(($pendingExpenseDrafts ?? 0) > 0)
                <a href="{{ route('expenses.index', ['status' => 'draft']) }}"
                   class="block rounded-lg border border-amber-200 bg-amber-50 p-4 hover:bg-amber-100 transition-colors duration-150">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100">
                            <svg class="h-5 w-5 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-amber-800">
                                {{ $pendingExpenseDrafts }} {{ trans_choice('expense draft pending approval|expense drafts pending approval', $pendingExpenseDrafts) }}
                            </p>
                            <p class="text-xs text-amber-600">{{ __('Click to review and approve') }}</p>
                        </div>
                        <svg class="h-5 w-5 text-amber-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </div>
                </a>
            @endif

            {{-- Per-Unit Breakdown (consolidated only) --}}
            @if(($consolidated ?? false) && !empty($unitBreakdown))
                <x-finance.table compact>
                    <x-slot name="head">
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('dashboard.table_unit') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('dashboard.cash_today') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('dashboard.cash_month') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('dashboard.arrears') }}</th>
                    </x-slot>

                    @foreach($unitBreakdown as $ub)
                        <tr>
                            <td class="px-5 py-3.5 whitespace-nowrap text-sm font-medium text-slate-800">
                                {{ $ub['name'] }}
                                <x-finance.badge color="slate" size="xs" class="ml-1.5">{{ $ub['code'] }}</x-finance.badge>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-sm font-medium text-emerald-600 text-right">{{ formatRupiah($ub['today_income']) }}</td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-sm font-medium text-blue-600 text-right">{{ formatRupiah($ub['month_income']) }}</td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-sm font-medium text-red-600 text-right">{{ formatRupiah($ub['arrears']) }}</td>
                        </tr>
                    @endforeach
                </x-finance.table>
            @endif

            {{-- Chart --}}
            <x-finance.card :title="__('dashboard.chart_title')">
                <canvas id="financialChart" height="100"></canvas>
            </x-finance.card>

            {{-- Recent Transactions --}}
            <x-finance.card :title="__('dashboard.recent_transactions')" :padding="false">
                <x-slot name="action">
                    @can('transactions.view')
                        <a href="{{ route('transactions.index') }}"
                           class="inline-flex items-center gap-x-1 text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors duration-150">
                            {{ __('dashboard.view_all') }}
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    @endcan
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead>
                            <tr class="bg-slate-50/80">
                                @if($consolidated ?? false)
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('dashboard.table_unit') }}</th>
                                @endif
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('dashboard.table_date') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('dashboard.table_code') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('dashboard.table_student') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('dashboard.table_class') }}</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('dashboard.table_amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($recentTransactions as $transaction)
                                <tr class="hover:bg-slate-50/80 transition-colors duration-100">
                                    @if($consolidated ?? false)
                                        <td class="px-5 py-3.5 whitespace-nowrap text-sm">
                                            <x-finance.badge color="indigo" size="xs">{{ $transaction->unit->code ?? '-' }}</x-finance.badge>
                                        </td>
                                    @endif
                                    <td class="px-5 py-3.5 whitespace-nowrap text-sm text-slate-500">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-sm font-medium text-slate-800">{{ $transaction->transaction_number }}</td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-sm text-slate-600">{{ $transaction->student->name ?? '-' }}</td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-sm text-slate-500">{{ $transaction->student->schoolClass->name ?? '-' }}</td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-sm font-semibold text-slate-800 text-right">{{ formatRupiah($transaction->total_amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ ($consolidated ?? false) ? 6 : 5 }}" class="px-5 py-10 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="h-8 w-8 text-slate-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859M12 3v8.25m0 0l-3-3m3 3l3-3" />
                                            </svg>
                                            <p class="text-sm text-slate-400">{{ __('dashboard.no_transactions_yet') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-finance.card>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        const ctx = document.getElementById('financialChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chartData['labels']),
                datasets: [
                    {
                        label: @json(__('dashboard.income')),
                        data: @json($chartData['incomeData']),
                        backgroundColor: 'rgba(16, 185, 129, 0.15)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                    },
                    {
                        label: @json(__('dashboard.expense')),
                        data: @json($chartData['expenseData']),
                        backgroundColor: 'rgba(245, 158, 11, 0.15)',
                        borderColor: 'rgb(245, 158, 11)',
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11 } },
                        border: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        border: { display: false },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 11 },
                            callback: function(value) {
                                if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                                if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + 'K';
                                return 'Rp ' + value;
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'rectRounded',
                            padding: 20,
                            color: '#64748b',
                            font: { size: 12 },
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f8fafc',
                        bodyColor: '#e2e8f0',
                        cornerRadius: 8,
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(context.raw);
                            }
                        }
                    }
                }
            }
        });
    </script>
</x-app-layout>
