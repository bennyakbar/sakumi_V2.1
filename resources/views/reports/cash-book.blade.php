<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cash Book</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('reports.cash-book') }}" class="mb-6 flex flex-wrap gap-4 items-end">
                        <div>
                            <x-input-label for="date" :value="__('Select Date')" />
                            <x-text-input id="date" name="date" type="date" class="block mt-1 w-full" :value="$date->toDateString()" required />
                        </div>
                        @if($consolidated)
                            <input type="hidden" name="scope" value="all">
                        @endif
                        <x-primary-button>{{ __('app.button.filter') }}</x-primary-button>
                        <a href="{{ route('reports.cash-book') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-semibold uppercase">{{ __('app.button.reset') }}</a>
                    </form>

                    <div class="mb-4 flex flex-wrap gap-2">
                        <a href="{{ route('reports.cash-book.export', array_merge(request()->all(), ['format' => 'xlsx'])) }}" class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-sm font-semibold uppercase">{{ __('app.button.export_xlsx') }}</a>
                        <a href="{{ route('reports.cash-book.export', array_merge(request()->all(), ['format' => 'csv'])) }}" class="px-4 py-2 bg-emerald-100 text-emerald-800 rounded-md hover:bg-emerald-200 text-sm font-semibold uppercase">{{ __('app.button.export_csv') }}</a>
                        @if(auth()->user()->hasRole('super_admin'))
                            <a href="{{ route('reports.cash-book', array_merge(request()->except('scope'), ['scope' => ($scope ?? 'unit') === 'all' ? 'unit' : 'all'])) }}" class="px-4 py-2 rounded-md text-sm font-semibold uppercase {{ ($scope ?? 'unit') === 'all' ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">{{ ($scope ?? 'unit') === 'all' ? __('app.unit.current') : __('app.unit.all') }}</a>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                        <div class="rounded-md border p-3 bg-gray-50"><p class="text-xs text-gray-500 uppercase">Opening Balance</p><p class="text-lg font-bold">Rp {{ number_format($summary['opening_balance'], 0, ',', '.') }}</p></div>
                        <div class="rounded-md border p-3 bg-gray-50"><p class="text-xs text-gray-500 uppercase">Total Receipt</p><p class="text-lg font-bold text-green-700">Rp {{ number_format($summary['total_debit'], 0, ',', '.') }}</p></div>
                        <div class="rounded-md border p-3 bg-gray-50"><p class="text-xs text-gray-500 uppercase">Total Expense</p><p class="text-lg font-bold text-red-700">Rp {{ number_format($summary['total_credit'], 0, ',', '.') }}</p></div>
                        <div class="rounded-md border p-3 bg-gray-50"><p class="text-xs text-gray-500 uppercase">Closing Balance</p><p class="text-lg font-bold">Rp {{ number_format($summary['closing_balance'], 0, ',', '.') }}</p></div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    @if($consolidated)
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.unit.unit') }}</th>
                                    @endif
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.time') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.source') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.code') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.description') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr class="bg-yellow-50">
                                    <td class="px-4 py-3 text-sm" colspan="{{ $consolidated ? 7 : 6 }}">Opening Balance</td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold">Rp {{ number_format($summary['opening_balance'], 0, ',', '.') }}</td>
                                </tr>
                                @forelse($entries as $entry)
                                    <tr>
                                        @if($consolidated)
                                            <td class="px-4 py-3 text-sm">{{ $entry['unit_code'] ?? '-' }}</td>
                                        @endif
                                        <td class="px-4 py-3 text-sm">{{ $entry['time'] }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $entry['source'] }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $entry['code'] }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $entry['description'] }}</td>
                                        <td class="px-4 py-3 text-sm text-right">Rp {{ number_format((float) $entry['debit'], 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-sm text-right">Rp {{ number_format((float) $entry['credit'], 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold">Rp {{ number_format((float) $entry['balance'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $consolidated ? 8 : 7 }}" class="px-4 py-4 text-sm text-gray-500 text-center">{{ __('app.empty.entries') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
