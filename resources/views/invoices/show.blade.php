<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Invoice Detail') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- Header --}}
                    <div class="flex justify-between items-start mb-6 border-b pb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $invoice->invoice_number }}</h3>
                            <p class="text-sm text-gray-500">{{ $invoice->invoice_date->format('d F Y') }}</p>
                            <p class="text-xs text-gray-400 mt-1">
                                <span class="uppercase">{{ $invoice->period_type }}</span> &mdash; {{ $invoice->period_identifier }}
                            </p>
                        </div>
                        <div class="text-right">
                            @if($invoice->status === 'unpaid')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ __('app.status.unpaid') }}</span>
                            @elseif($invoice->status === 'partially_paid')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">{{ __('app.status.partial') }}</span>
                            @elseif($invoice->status === 'paid')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('app.status.paid') }}</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ __('app.status.cancelled') }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Student & Invoice Info --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('Student Info') }}</h4>
                            <p class="text-gray-900 font-medium">{{ $invoice->student->name ?? '-' }}</p>
                            <p class="text-gray-600 text-sm">{{ __('app.label.nis') }}: {{ $invoice->student->nis ?? '-' }}</p>
                            <p class="text-gray-600 text-sm">{{ __('app.label.class') }}: {{ $invoice->student->schoolClass->name ?? '-' }}</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('Invoice Info') }}</h4>
                            <p class="text-gray-600 text-sm">{{ __('app.label.due_date') }}: {{ $invoice->due_date->format('d F Y') }}</p>
                            <p class="text-gray-600 text-sm">{{ __('app.label.created_by') }}: {{ $invoice->creator->name ?? 'System' }}</p>
                            @if($invoice->notes)
                                <p class="text-gray-600 text-sm mt-2">{{ __('app.label.notes') }}: {{ $invoice->notes }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Line Items --}}
                    <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('Line Items') }}</h4>
                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.fee_type') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.period') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Obligation Status') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($invoice->items as $item)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->feeType->name ?? $item->description }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($item->month && $item->year)
                                                {{ sprintf('%02d/%d', $item->month, $item->year) }}
                                            @elseif($item->year)
                                                {{ $item->year }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($item->studentObligation && $item->studentObligation->is_paid)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('app.status.paid') }}</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ __('app.status.unpaid') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatRupiah($item->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50">
                                    <td colspan="3" class="px-6 py-4 text-sm font-bold text-gray-900 text-right">{{ __('app.label.total') }}</td>
                                    <td class="px-6 py-4 text-sm font-bold text-gray-900 text-right">{{ formatRupiah($invoice->total_amount) }}</td>
                                </tr>
                                <tr class="bg-gray-50">
                                    <td colspan="3" class="px-6 py-4 text-sm font-bold text-green-700 text-right">{{ __('app.status.paid') }}</td>
                                    <td class="px-6 py-4 text-sm font-bold text-green-700 text-right">{{ formatRupiah($invoice->paid_amount) }}</td>
                                </tr>
                                <tr class="bg-gray-50">
                                    <td colspan="3" class="px-6 py-4 text-sm font-bold text-red-700 text-right">{{ __('app.label.outstanding') }}</td>
                                    <td class="px-6 py-4 text-sm font-bold text-red-700 text-right">{{ formatRupiah($invoice->outstanding) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Settlement Allocations --}}
                    @if($invoice->allocations->isNotEmpty())
                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('Settlement History') }}</h4>
                        <div class="overflow-x-auto mb-6">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Settlement #') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.date') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.method') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.status') }}</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.allocated') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($invoice->allocations as $allocation)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                @can('settlements.view')
                                                    <a href="{{ route('settlements.show', $allocation->settlement) }}" class="text-indigo-600 hover:text-indigo-900">{{ $allocation->settlement->settlement_number }}</a>
                                                @else
                                                    {{ $allocation->settlement->settlement_number }}
                                                @endcan
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $allocation->settlement->payment_date->format('d/m/Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($allocation->settlement->payment_method) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @if($allocation->settlement->status === 'completed')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('app.status.completed') }}</span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ __('app.status.cancelled') }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">{{ formatRupiah($allocation->amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="flex justify-end space-x-4">
                        <a href="{{ route('invoices.index') }}"
                            class="px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                            {{ __('app.button.back') }}
                        </a>
                        @can('invoices.print')
                            <a href="{{ route('invoices.print', $invoice) }}" target="_blank"
                                class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                {{ __('Print Invoice') }}
                            </a>
                        @endcan
                        @can('settlements.create')
                            @if(in_array($invoice->status, ['unpaid', 'partially_paid']))
                                <a href="{{ route('settlements.create', ['student_id' => $invoice->student_id, 'invoice_id' => $invoice->id]) }}"
                                    class="px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                    {{ __('Create Settlement') }}
                                </a>
                            @endif
                        @endcan
                        @can('invoices.cancel')
                            @if(in_array($invoice->status, ['unpaid', 'partially_paid']))
                                <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('{{ __('Are you sure you want to cancel this invoice?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="cancellation_reason" value="{{ $invoice->status === 'partially_paid' ? 'Void partially paid invoice' : 'Cancel unpaid invoice' }}">
                                    <button type="submit"
                                        class="px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                        {{ $invoice->status === 'partially_paid' ? __('Void Invoice') : __('Cancel Invoice') }}
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
