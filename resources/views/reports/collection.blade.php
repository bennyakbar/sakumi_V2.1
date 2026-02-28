<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Collection Report</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('reports.collection') }}" class="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                        <div>
                            <x-input-label for="date_from" :value="__('app.label.date_from')" />
                            <x-text-input id="date_from" name="date_from" type="date" class="block mt-1 w-full" :value="$dateFrom->toDateString()" />
                        </div>
                        <div>
                            <x-input-label for="date_to" :value="__('app.label.date_to')" />
                            <x-text-input id="date_to" name="date_to" type="date" class="block mt-1 w-full" :value="$dateTo->toDateString()" />
                        </div>
                        <div>
                            <x-input-label for="payment_method" :value="__('app.label.method')" />
                            <select id="payment_method" name="payment_method" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                <option value="">{{ __('app.label.all') }}</option>
                                <option value="cash" {{ $paymentMethod === 'cash' ? 'selected' : '' }}>{{ __('app.payment.cash') }}</option>
                                <option value="transfer" {{ $paymentMethod === 'transfer' ? 'selected' : '' }}>{{ __('app.payment.transfer') }}</option>
                                <option value="qris" {{ $paymentMethod === 'qris' ? 'selected' : '' }}>{{ __('app.payment.qris') }}</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="cashier_id" :value="__('Cashier')" />
                            <select id="cashier_id" name="cashier_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                <option value="">{{ __('app.label.all') }}</option>
                                @foreach($cashiers as $cashier)
                                    <option value="{{ $cashier->id }}" {{ (string) $cashierId === (string) $cashier->id ? 'selected' : '' }}>{{ $cashier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            @if($consolidated)
                                <input type="hidden" name="scope" value="all">
                            @endif
                            <x-primary-button>{{ __('app.button.filter') }}</x-primary-button>
                            <a href="{{ route('reports.collection') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-semibold uppercase">{{ __('app.button.reset') }}</a>
                        </div>
                    </form>

                    <div class="mb-4 flex flex-wrap gap-2">
                        <a href="{{ route('reports.collection.export', array_merge(request()->all(), ['format' => 'xlsx'])) }}" class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-sm font-semibold uppercase">{{ __('app.button.export_xlsx') }}</a>
                        <a href="{{ route('reports.collection.export', array_merge(request()->all(), ['format' => 'csv'])) }}" class="px-4 py-2 bg-emerald-100 text-emerald-800 rounded-md hover:bg-emerald-200 text-sm font-semibold uppercase">{{ __('app.button.export_csv') }}</a>
                        @if(auth()->user()->hasRole('super_admin'))
                            <a href="{{ route('reports.collection', array_merge(request()->except('scope'), ['scope' => ($scope ?? 'unit') === 'all' ? 'unit' : 'all'])) }}" class="px-4 py-2 rounded-md text-sm font-semibold uppercase {{ ($scope ?? 'unit') === 'all' ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">{{ ($scope ?? 'unit') === 'all' ? __('app.unit.current') : __('app.unit.all') }}</a>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                        <div class="rounded-md border p-3 bg-gray-50"><p class="text-xs text-gray-500 uppercase">Total Income</p><p class="text-lg font-bold text-green-700">Rp {{ number_format($totalIncome, 0, ',', '.') }}</p></div>
                        <div class="rounded-md border p-3 bg-gray-50"><p class="text-xs text-gray-500 uppercase">Total Expense</p><p class="text-lg font-bold text-red-700">Rp {{ number_format($totalExpense, 0, ',', '.') }}</p></div>
                        <div class="rounded-md border p-3 bg-gray-50"><p class="text-xs text-gray-500 uppercase">Net</p><p class="text-lg font-bold">Rp {{ number_format($net, 0, ',', '.') }}</p></div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    @if($consolidated)
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.unit.unit') }}</th>
                                    @endif
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.date') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.source') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.code') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.student') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.method') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cashier</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('app.label.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($paginated as $entry)
                                    <tr class="{{ $entry['amount'] < 0 ? 'bg-red-50' : '' }}">
                                        @if($consolidated)
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $entry['unit_code'] ?? '-' }}</td>
                                        @endif
                                        <td class="px-4 py-3 text-sm">{{ \Carbon\Carbon::parse($entry['date'])->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $entry['source'] }}</td>
                                        <td class="px-4 py-3 text-sm font-medium">{{ $entry['code'] }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $entry['student'] }}</td>
                                        <td class="px-4 py-3 text-sm">{{ strtoupper($entry['payment_method']) }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $entry['cashier'] }}</td>
                                        <td class="px-4 py-3 text-sm text-right {{ $entry['amount'] < 0 ? 'text-red-700' : 'text-green-700' }}">Rp {{ number_format(abs((float) $entry['amount']), 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $consolidated ? 9 : 8 }}" class="px-4 py-4 text-sm text-gray-500 text-center">{{ __('app.empty.entries') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $paginated->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
