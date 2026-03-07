<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Transaction Detail') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-start mb-6 border-b pb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $transaction->code }}</h3>
                            <p class="text-sm text-gray-500">{{ $transaction->transaction_date->format('d F Y') }}</p>
                        </div>
                        <div class="text-right">
                            @if($transaction->status == 'completed')
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('app.status.completed') }}</span>
                            @elseif($transaction->status == 'cancelled')
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ __('app.status.cancelled') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('Student Info') }}
                            </h4>
                            <p class="text-gray-900 font-medium">{{ $transaction->student->name ?? '-' }}</p>
                            <p class="text-gray-600 text-sm">{{ __('app.label.nis') }}: {{ $transaction->student->nis ?? '-' }}</p>
                            <p class="text-gray-600 text-sm">{{ __('app.label.class') }}:
                                {{ $transaction->student->schoolClass->name ?? '-' }}</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('Payment Info') }}
                            </h4>
                            <p class="text-gray-600 text-sm">{{ __('app.label.method') }}: {{ ucfirst($transaction->payment_method) }}
                            </p>
                            <p class="text-gray-600 text-sm">{{ __('app.label.created_by') }}: {{ $transaction->creator->name ?? 'System' }}</p>
                            @if($transaction->notes)
                                <p class="text-gray-600 text-sm mt-2">{{ __('app.label.notes') }}: {{ $transaction->notes }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('Description / Fee Type') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($transaction->items as $item)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $item->feeType->name }}
                                            @if($item->description)
                                                <span class="text-gray-500 text-xs">- {{ $item->description }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">Rp
                                            {{ formatRupiah($item->amount) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">
                                        {{ __('app.label.total') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">
                                        {{ formatRupiah($transaction->total_amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="{{ route('transactions.index') }}"
                            class="px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                            {{ __('app.button.back') }}
                        </a>
                        @can('receipts.print')
                            <button type="button"
                                onclick="handleReceiptPrint('{{ route('receipts.print', $transaction) }}', {{ (int) ($transaction->receipt_print_count ?? 0) }})"
                                class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                {{ __('Print Receipt') }}
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="reprint-modal" class="fixed inset-0 bg-black/40 hidden z-50 items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-lg shadow-xl">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Reprint Reason</h3>
                <p class="text-sm text-gray-500 mt-1">Please select reason before printing a copy.</p>
            </div>
            <form id="reprint-form" method="GET" target="_blank" class="p-6">
                <div>
                    <x-input-label for="reprint_reason_type" value="Reason" />
                    <select id="reprint_reason_type" name="reason_type"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        onchange="toggleReprintOther()">
                        <option value="lost">Lost</option>
                        <option value="damaged">Damaged</option>
                        <option value="parent_request">Parent Request</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div id="reprint-other-wrap" class="mt-4 hidden">
                    <x-input-label for="reprint_reason_other" value="Other Reason" />
                    <x-text-input id="reprint_reason_other" name="reason_other" type="text" class="mt-1 block w-full" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-semibold uppercase"
                        onclick="closeReprintModal()">
                        {{ __('app.button.close') }}
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-semibold uppercase">
                        Continue Print
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const reprintModal = document.getElementById('reprint-modal');
        const reprintForm = document.getElementById('reprint-form');
        const reprintReasonType = document.getElementById('reprint_reason_type');
        const reprintReasonOther = document.getElementById('reprint_reason_other');
        const reprintOtherWrap = document.getElementById('reprint-other-wrap');

        function handleReceiptPrint(url, printCount) {
            if (printCount > 0) {
                reprintForm.action = url;
                reprintReasonType.value = 'lost';
                reprintReasonOther.value = '';
                toggleReprintOther();
                reprintModal.classList.remove('hidden');
                reprintModal.classList.add('flex');
                return;
            }

            window.open(url, '_blank');
        }

        function toggleReprintOther() {
            const isOther = reprintReasonType.value === 'other';
            reprintOtherWrap.classList.toggle('hidden', !isOther);
            reprintReasonOther.required = isOther;
            if (!isOther) {
                reprintReasonOther.value = '';
            }
        }

        function closeReprintModal() {
            reprintModal.classList.add('hidden');
            reprintModal.classList.remove('flex');
        }

        reprintModal.addEventListener('click', function (event) {
            if (event.target === reprintModal) {
                closeReprintModal();
            }
        });
    </script>
</x-app-layout>
