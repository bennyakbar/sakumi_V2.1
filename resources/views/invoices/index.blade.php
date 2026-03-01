<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Invoices') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">
                            {{ __('Invoice List') }}
                        </h2>
                        <div class="flex gap-2">
                            @can('invoices.generate')
                                <a href="{{ route('invoices.generate') }}"
                                    class="px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition ease-in-out duration-150">
                                    {{ __('Generate Invoices') }}
                                </a>
                            @endcan
                            @can('invoices.create')
                                <a href="{{ route('invoices.create') }}"
                                    class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
                                    {{ __('Create Invoice') }}
                                </a>
                            @endcan
                        </div>
                    </div>

                    {{-- Filters --}}
                    <form method="GET" action="{{ route('invoices.index') }}" class="mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <x-text-input name="search" class="block w-full" :placeholder="__('app.placeholder.search_invoice')" :value="request('search')" />
                            </div>
                            <div>
                                <select name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full">
                                    <option value="">{{ __('app.filter.all_status') }}</option>
                                    <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>{{ __('app.status.unpaid') }}</option>
                                    <option value="partially_paid" {{ request('status') === 'partially_paid' ? 'selected' : '' }}>{{ __('app.status.partial') }}</option>
                                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>{{ __('app.status.paid') }}</option>
                                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('app.status.cancelled') }}</option>
                                </select>
                            </div>
                            <div>
                                <select name="period_type" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full">
                                    <option value="">{{ __('app.filter.all_periods') }}</option>
                                    <option value="monthly" {{ request('period_type') === 'monthly' ? 'selected' : '' }}>{{ __('app.status.monthly') }}</option>
                                    <option value="annual" {{ request('period_type') === 'annual' ? 'selected' : '' }}>{{ __('app.status.annual') }}</option>
                                </select>
                            </div>
                            <div>
                                <x-primary-button class="w-full justify-center">{{ __('app.button.filter') }}</x-primary-button>
                            </div>
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Invoice #') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.student') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.period') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.due_date') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.total') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.outstanding') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.status') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($invoices as $invoice)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $invoice->invoice_number }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $invoice->student->name ?? '-' }}
                                            <span class="text-xs text-gray-400">({{ $invoice->student->schoolClass->name ?? '-' }})</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="text-xs uppercase text-gray-400">{{ $invoice->period_type }}</span>
                                            {{ $invoice->period_identifier }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $invoice->due_date->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">{{ formatRupiah($invoice->total_amount) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right {{ $invoice->outstanding > 0 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ formatRupiah($invoice->outstanding) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($invoice->status === 'unpaid')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ __('app.status.unpaid') }}</span>
                                            @elseif($invoice->status === 'partially_paid')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">{{ __('app.status.partial') }}</span>
                                            @elseif($invoice->status === 'paid')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('app.status.paid') }}</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ __('app.status.cancelled') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900 mr-2">{{ __('app.button.detail') }}</a>
                                            @can('invoices.print')
                                                <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="text-gray-600 hover:text-gray-900 mr-2">{{ __('app.button.print') }}</a>
                                            @endcan
                                            @can('settlements.create')
                                                @if(in_array($invoice->status, ['unpaid', 'partially_paid']))
                                                    <a href="{{ route('settlements.create', ['student_id' => $invoice->student_id, 'invoice_id' => $invoice->id]) }}" class="text-green-600 hover:text-green-900">{{ __('app.button.pay') }}</a>
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 text-sm text-gray-500 text-center">{{ __('app.empty.invoices') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $invoices->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
