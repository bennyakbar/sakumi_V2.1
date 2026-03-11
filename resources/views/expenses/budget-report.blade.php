<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Budget vs Realization</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('expenses.budget-report') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <x-input-label for="month" :value="__('app.label.month')" />
                        <x-text-input id="month" name="month" type="number" min="1" max="12" class="block mt-1 w-full" :value="$month" />
                    </div>
                    <div>
                        <x-input-label for="year" :value="__('app.label.year')" />
                        <x-text-input id="year" name="year" type="number" min="2020" max="2100" class="block mt-1 w-full" :value="$year" />
                    </div>
                    <x-primary-button>{{ __('app.button.filter') }}</x-primary-button>
                    <a href="{{ route('expenses.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-xs font-semibold uppercase">Back to Expenses</a>
                </form>
            </div>

            @can('expenses.budget.manage')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Set Budget</h3>
                    <form method="POST" action="{{ route('expenses.budgets.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        @csrf
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <div class="md:col-span-2">
                            <x-input-label for="expense_fee_subcategory_id" value="Subcategory" />
                            <select id="expense_fee_subcategory_id" name="expense_fee_subcategory_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                <option value="">-- Select --</option>
                                @foreach($subcategories as $sub)
                                    <option value="{{ $sub->id }}">{{ $sub->category?->name ?? '-' }} / {{ $sub->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="budget_amount" value="Budget Amount" />
                            <x-text-input id="budget_amount" name="budget_amount" type="number" min="1" step="1" class="block mt-1 w-full" required />
                        </div>
                        <div>
                            <x-input-label for="notes" :value="__('app.label.notes')" />
                            <x-text-input id="notes" name="notes" type="text" class="block mt-1 w-full" />
                        </div>
                        <div class="md:col-span-4">
                            <x-primary-button>{{ __('app.button.save') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            @endcan

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subcategory</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Planned</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Estimated</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Realized</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Variance</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($rows as $row)
                                <tr>
                                    <td class="px-4 py-3 text-sm">{{ $row['category'] }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $row['subcategory'] }}</td>
                                    <td class="px-4 py-3 text-sm text-right">{{ formatRupiah($row['planned']) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-slate-500">{{ formatRupiah($row['estimated']) }}</td>
                                    <td class="px-4 py-3 text-sm text-right">{{ formatRupiah($row['realized']) }}</td>
                                    <td class="px-4 py-3 text-sm text-right {{ $row['variance'] < 0 ? 'text-red-700' : 'text-green-700' }}">{{ formatRupiah($row['variance']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-4 text-sm text-gray-500 text-center">No budget data for selected period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
