<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold tracking-tight text-slate-800">{{ __('Invoices') }}</h2>
    </x-slot>

    @php
        $allInvoices   = $invoices->getCollection();
        $totalAmount   = $allInvoices->sum('total_amount');
        $totalPaid     = $allInvoices->sum('paid_amount');
        $totalBalance  = $allInvoices->sum('outstanding');
        $countUnpaid   = $allInvoices->whereIn('status', ['unpaid', 'partially_paid'])->count();
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Action Bar --}}
            <x-finance.action-bar :title="__('Invoice List')" :subtitle="__('app.label.showing') . ' ' . $invoices->firstItem() . '-' . $invoices->lastItem() . ' / ' . $invoices->total()">
                @can('invoices.generate')
                    <a href="{{ route('invoices.generate') }}"
                       class="inline-flex items-center gap-x-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-150">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                        </svg>
                        {{ __('Generate Invoices') }}
                    </a>
                @endcan
                @can('invoices.create')
                    <a href="{{ route('invoices.create') }}"
                       class="inline-flex items-center gap-x-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-150">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ __('Create Invoice') }}
                    </a>
                @endcan
            </x-finance.action-bar>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-finance.summary :title="__('app.label.total')" :value="formatRupiah($totalAmount)" color="indigo">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </x-slot>
                </x-finance.summary>

                <x-finance.summary :title="__('app.status.paid')" :value="formatRupiah($totalPaid)" color="emerald">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot>
                </x-finance.summary>

                <x-finance.summary :title="__('app.label.outstanding')" :value="formatRupiah($totalBalance)" color="red">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot>
                </x-finance.summary>

                <x-finance.summary :title="__('app.status.unpaid')" :value="$countUnpaid" color="amber">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </x-slot>
                </x-finance.summary>
            </div>

            {{-- Filters --}}
            <x-finance.card :padding="true" noDivider>
                <form method="GET" action="{{ route('invoices.index') }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                        <div>
                            <label for="search" class="block text-xs font-medium text-slate-500 mb-1">{{ __('app.placeholder.search_invoice') }}</label>
                            <input type="text" name="search" id="search"
                                   value="{{ request('search') }}"
                                   placeholder="{{ __('app.placeholder.search_invoice') }}"
                                   class="block w-full rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label for="status" class="block text-xs font-medium text-slate-500 mb-1">{{ __('app.label.status') }}</label>
                            <select name="status" id="status"
                                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('app.filter.all_status') }}</option>
                                <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>{{ __('app.status.unpaid') }}</option>
                                <option value="partially_paid" {{ request('status') === 'partially_paid' ? 'selected' : '' }}>{{ __('app.status.partial') }}</option>
                                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>{{ __('app.status.paid') }}</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('app.status.cancelled') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="period_type" class="block text-xs font-medium text-slate-500 mb-1">{{ __('app.label.period') }}</label>
                            <select name="period_type" id="period_type"
                                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('app.filter.all_periods') }}</option>
                                <option value="monthly" {{ request('period_type') === 'monthly' ? 'selected' : '' }}>{{ __('app.status.monthly') }}</option>
                                <option value="annual" {{ request('period_type') === 'annual' ? 'selected' : '' }}>{{ __('app.status.annual') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="period_identifier" class="block text-xs font-medium text-slate-500 mb-1">{{ __('Month') }}</label>
                            <input type="month" name="period_identifier" id="period_identifier"
                                   value="{{ request('period_identifier') }}"
                                   class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                    class="flex-1 inline-flex items-center justify-center gap-x-1.5 rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 transition-all duration-150">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                                </svg>
                                {{ __('app.button.filter') }}
                            </button>
                            @if(request()->hasAny(['search', 'status', 'period_type', 'period_identifier']))
                                <a href="{{ route('invoices.index') }}"
                                   class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-600 shadow-sm hover:bg-slate-50 transition-all duration-150"
                                   title="{{ __('app.button.reset') }}">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </x-finance.card>

            {{-- Invoice Table --}}
            <x-finance.table>
                <x-slot name="head">
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Invoice #') }}</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('app.label.student') }}</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Month') }}</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('app.label.amount') }}</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('app.status.paid') }}</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('app.label.outstanding') }}</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('app.label.status') }}</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('app.label.actions') }}</th>
                </x-slot>

                @forelse ($invoices as $invoice)
                    <tr>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <a href="{{ route('invoices.show', $invoice) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors duration-150">
                                {{ $invoice->invoice_number }}
                            </a>
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <div class="text-sm font-medium text-slate-800">{{ $invoice->student->name ?? '-' }}</div>
                            <div class="text-xs text-slate-400">{{ $invoice->student->schoolClass->name ?? '-' }}</div>
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <div class="text-sm text-slate-700">{{ $invoice->period_identifier }}</div>
                            <div class="text-xs text-slate-400">{{ __('app.label.due_date') }}: {{ $invoice->due_date->format('d/m/Y') }}</div>
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-sm font-semibold text-slate-800 text-right">
                            {{ formatRupiah($invoice->total_amount) }}
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-sm font-medium text-emerald-600 text-right">
                            {{ formatRupiah($invoice->paid_amount) }}
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-sm font-semibold text-right {{ $invoice->outstanding > 0 ? 'text-red-600' : 'text-slate-400' }}">
                            {{ formatRupiah($invoice->outstanding) }}
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            @if($invoice->status === 'unpaid')
                                <x-finance.badge color="yellow" dot>{{ __('app.status.unpaid') }}</x-finance.badge>
                            @elseif($invoice->status === 'partially_paid')
                                <x-finance.badge color="blue" dot>{{ __('app.status.partial') }}</x-finance.badge>
                            @elseif($invoice->status === 'paid')
                                <x-finance.badge color="green" dot>{{ __('app.status.paid') }}</x-finance.badge>
                            @else
                                <x-finance.badge color="red" dot>{{ __('app.status.cancelled') }}</x-finance.badge>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('invoices.show', $invoice) }}"
                                   class="inline-flex items-center rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors duration-150"
                                   title="{{ __('app.button.detail') }}">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </a>
                                @can('invoices.print')
                                    <a href="{{ route('invoices.print', $invoice) }}" target="_blank"
                                       class="inline-flex items-center rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors duration-150"
                                       title="{{ __('app.button.print') }}">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M9.75 21h4.5" />
                                        </svg>
                                    </a>
                                @endcan
                                @can('settlements.create')
                                    @if(in_array($invoice->status, ['unpaid', 'partially_paid']))
                                        <a href="{{ route('settlements.create', ['student_id' => $invoice->student_id, 'invoice_id' => $invoice->id]) }}"
                                           class="inline-flex items-center rounded-md p-1.5 text-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 transition-colors duration-150"
                                           title="{{ __('app.button.pay') }}">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                            </svg>
                                        </a>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="h-10 w-10 text-slate-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9.75m3 0h3m-1.5-18H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                <p class="text-sm text-slate-400">{{ __('app.empty.invoices') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse

                <x-slot name="pagination">
                    {{ $invoices->links() }}
                </x-slot>
            </x-finance.table>

        </div>
    </div>
</x-app-layout>
