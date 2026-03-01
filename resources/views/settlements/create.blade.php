<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Settlement') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Step 1: Select Student --}}
                    <form method="GET" action="{{ route('settlements.create') }}" class="mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <x-input-label for="student_select" :value="__('Select Student')" />
                                <select id="student_select" name="student_id"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    onchange="this.form.submit()">
                                    <option value="">{{ __('app.placeholder.select_student') }}</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}" {{ $selectedStudentId == $student->id ? 'selected' : '' }}>
                                            {{ $student->name }} ({{ $student->schoolClass->name ?? '-' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>

                    @if($selectedStudentId && $outstandingInvoices->isNotEmpty())
                        {{-- Step 2: Settlement + Allocation --}}
                        <form method="POST" action="{{ route('settlements.store') }}" id="settlementForm">
                            @csrf
                            <input type="hidden" name="student_id" value="{{ $selectedStudentId }}">
                            <input type="hidden" name="invoice_id" value="{{ (int) ($selectedInvoiceId ?: ($outstandingInvoices->first()->id ?? 0)) }}">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 border-t border-gray-200 pt-4">
                                <div>
                                    <x-input-label for="payment_date" :value="__('Payment Date')" />
                                    <x-text-input id="payment_date" class="block mt-1 w-full" type="date" name="payment_date"
                                        :value="old('payment_date', date('Y-m-d'))" required />
                                </div>
                                <div>
                                    <x-input-label for="payment_method" :value="__('Payment Method')" />
                                    <select id="payment_method" name="payment_method"
                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                        <option value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'selected' : '' }}>{{ __('app.payment.cash') }}</option>
                                        <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>{{ __('app.payment.transfer') }}</option>
                                        <option value="qris" {{ old('payment_method') === 'qris' ? 'selected' : '' }}>{{ __('app.payment.qris') }}</option>
                                    </select>
                                </div>
                                <div>
                                    @php $invoice = $outstandingInvoices->first(); @endphp
                                    <x-input-label for="amount" :value="__('Payment Amount')" />
                                    <x-text-input id="amount" class="block mt-1 w-full" type="number" name="amount"
                                        :value="old('amount', (int) ($invoice->outstanding_amount ?? 0))"
                                        required min="1" max="{{ (int) ($invoice->outstanding_amount ?? 0) }}" step="1" />
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ __('app.form.min_max', ['max' => formatRupiah((float) ($invoice->outstanding_amount ?? 0))]) }}
                                    </p>
                                </div>
                                <div>
                                    <x-input-label for="reference_number" :value="__('Reference Number (Optional)')" />
                                    <x-text-input id="reference_number" class="block mt-1 w-full" type="text" name="reference_number"
                                        :value="old('reference_number')" :placeholder="__('app.placeholder.transfer_ref')" />
                                </div>
                                <div class="md:col-span-2">
                                    <x-input-label for="notes" :value="__('Notes (Optional)')" />
                                    <textarea id="notes" name="notes" rows="2"
                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">{{ old('notes') }}</textarea>
                                </div>
                            </div>

                            {{-- Invoice Snapshot --}}
                            <div class="border-t border-gray-200 pt-4 mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Invoice Summary') }}</h3>
                                <p class="text-sm text-gray-500 mb-4">{{ __('app.form.payment_applied') }}</p>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.code') }}</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.period') }}</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.due_date') }}</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('app.label.total') }}</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('app.status.paid') }}</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('app.label.outstanding') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($outstandingInvoices as $inv)
                                                <tr>
                                                    <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                                        {{ $inv->invoice_number }}
                                                    </td>
                                                    <td class="px-4 py-4 text-sm text-gray-500">
                                                        <span class="text-xs uppercase text-gray-400">{{ $inv->period_type }}</span>
                                                        {{ $inv->period_identifier }}
                                                    </td>
                                                    <td class="px-4 py-4 text-sm text-gray-500">{{ $inv->due_date->format('d/m/Y') }}</td>
                                                    <td class="px-4 py-4 text-sm text-gray-900 text-right">{{ formatRupiah($inv->total_amount) }}</td>
                                                    <td class="px-4 py-4 text-sm text-gray-500 text-right">{{ formatRupiah((float) ($inv->settled_amount ?? 0)) }}</td>
                                                    <td class="px-4 py-4 text-sm font-medium text-red-600 text-right">{{ formatRupiah((float) ($inv->outstanding_amount ?? 0)) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-4">
                                <a href="{{ route('settlements.index') }}"
                                    class="px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                                    {{ __('app.button.cancel') }}
                                </a>
                                <x-primary-button>{{ __('Create Settlement') }}</x-primary-button>
                            </div>
                        </form>
                    @elseif($selectedStudentId && $outstandingInvoices->isEmpty())
                        <div class="border-t border-gray-200 pt-4">
                            <p class="text-gray-500 text-center py-8">{{ __('app.empty.no_invoices_student') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
