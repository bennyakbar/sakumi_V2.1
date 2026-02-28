<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Monthly Report') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <form method="GET" action="{{ route('reports.monthly') }}"
                        class="mb-6 flex gap-4 items-end flex-wrap">
                        <div>
                            <x-input-label for="month" :value="__('Month')" />
                            <select id="month" name="month"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>
                                        {{ DateTime::createFromFormat('!m', $i)->format('F') }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <x-input-label for="year" :value="__('Year')" />
                            <select id="year" name="year"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                                    <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        @if($consolidated ?? false)
                            <input type="hidden" name="scope" value="all">
                        @endif
                        <x-primary-button>
                            {{ __('Filter') }}
                        </x-primary-button>

                        @if(auth()->user()->hasRole('super_admin'))
                            <a href="{{ route('reports.monthly', array_merge(request()->except('scope'), ['scope' => ($scope ?? 'unit') === 'all' ? 'unit' : 'all'])) }}"
                                class="px-4 py-2 rounded-md text-sm font-semibold uppercase {{ ($scope ?? 'unit') === 'all' ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                {{ ($scope ?? 'unit') === 'all' ? __('app.unit.current') : __('app.unit.all') }}
                            </a>
                        @endif
                        <a href="{{ route('reports.monthly.export', array_merge(request()->all(), ['format' => 'xlsx'])) }}"
                            class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-sm font-semibold uppercase">
                            {{ __('app.button.export_xlsx') }}
                        </a>
                        <a href="{{ route('reports.monthly.export', array_merge(request()->all(), ['format' => 'csv'])) }}"
                            class="px-4 py-2 bg-emerald-100 text-emerald-800 rounded-md hover:bg-emerald-200 text-sm font-semibold uppercase">
                            {{ __('app.button.export_csv') }}
                        </a>
                    </form>

                    <div class="flex justify-between items-center mb-4 bg-gray-50 p-4 rounded-lg">
                        <div>
                            <span class="text-gray-600">{{ __('report.period') }}</span>
                            <span
                                class="font-bold text-gray-900">{{ DateTime::createFromFormat('!m', $month)->format('F') }}
                                {{ $year }}</span>
                            @if($consolidated ?? false)
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">{{ __('app.unit.all') }}</span>
                            @endif
                        </div>
                        <div>
                            <span class="text-gray-600">{{ __('report.net_cash') }}</span>
                            <span class="font-bold text-xl text-green-600">Rp
                                {{ number_format($totalAmount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Daily Summary Table -->
                        <div class="lg:col-span-1">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('report.daily_summary') }}</h3>
                            <div class="bg-white border rounded-lg overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('app.label.date') }}</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('app.label.total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @forelse($dailySummary as $date => $total)
                                            <tr>
                                                <td class="px-6 py-3 text-sm text-gray-900">
                                                    {{ \Carbon\Carbon::parse($date)->format('d M') }}</td>
                                                <td class="px-6 py-3 text-sm text-gray-900 text-right">Rp
                                                    {{ number_format($total, 0, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="px-6 py-3 text-sm text-gray-500 text-center">{{ __('app.empty.transactions_short') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Unified Entry List -->
                        <div class="lg:col-span-2">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('report.all_entries') }}</h3>
                            <div class="bg-white border rounded-lg overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            @if($consolidated ?? false)
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {{ __('app.unit.unit') }}</th>
                                            @endif
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('app.label.date') }}</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('app.label.source') }}</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('app.label.code') }}</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('app.label.student') }}</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('app.label.amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @forelse($entries as $entry)
                                            <tr class="{{ $entry->type === 'expense' ? 'bg-red-50' : '' }}">
                                                @if($consolidated ?? false)
                                                    <td class="px-6 py-3 text-sm text-gray-500">
                                                        {{ $entry->unit_code ?? '-' }}</td>
                                                @endif
                                                <td class="px-6 py-3 text-sm text-gray-500">
                                                    {{ $entry->date->format('d/m/Y') }}</td>
                                                <td class="px-6 py-3 text-sm text-gray-500">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $entry->model_type === 'settlement' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                                        {{ $entry->source }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-3 text-sm font-medium">
                                                    @if($entry->model_type === 'settlement')
                                                        @can('settlements.view')
                                                            <a href="{{ route('settlements.show', $entry->model) }}" class="text-indigo-600 hover:underline">{{ $entry->code }}</a>
                                                        @else
                                                            {{ $entry->code }}
                                                        @endcan
                                                    @else
                                                        @can('transactions.view')
                                                            <a href="{{ route('transactions.show', $entry->model) }}" class="text-indigo-600 hover:underline">{{ $entry->code }}</a>
                                                        @else
                                                            {{ $entry->code }}
                                                        @endcan
                                                    @endif
                                                </td>
                                                <td class="px-6 py-3 text-sm text-gray-900">
                                                    {{ $entry->student_name }}</td>
                                                <td class="px-6 py-3 text-sm text-right {{ $entry->amount < 0 ? 'text-red-600' : 'text-gray-900' }}">Rp
                                                    {{ number_format($entry->amount, 0, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ ($consolidated ?? false) ? 6 : 5 }}" class="px-6 py-3 text-sm text-gray-500 text-center">{{ __('app.empty.entries') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
