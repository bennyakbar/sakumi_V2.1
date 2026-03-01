<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Transactions') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">
                            {{ __('Transaction History') }}
                        </h2>
                        @can('transactions.create')
                            <a href="{{ route('transactions.create') }}"
                                class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('New Transaction') }}
                            </a>
                        @endcan
                    </div>

                    <form method="GET" action="{{ route('transactions.index') }}"
                        class="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                        <div class="lg:col-span-2">
                            <label for="search" class="block text-xs font-semibold text-gray-600 mb-1">{{ __('app.button.search') }}</label>
                            <input id="search" name="search" type="text" value="{{ request('search') }}"
                                placeholder="{{ __('app.placeholder.search_transaction') }}"
                                class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>

                        <div>
                            <label for="status" class="block text-xs font-semibold text-gray-600 mb-1">{{ __('app.label.status') }}</label>
                            <select id="status" name="status"
                                class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">{{ __('app.label.all') }}</option>
                                <option value="completed" @selected(request('status') === 'completed')>{{ __('app.status.completed') }}</option>
                                <option value="cancelled" @selected(request('status') === 'cancelled')>{{ __('app.status.cancelled') }}</option>
                            </select>
                        </div>

                        <div>
                            <label for="payment_method" class="block text-xs font-semibold text-gray-600 mb-1">{{ __('app.label.method') }}</label>
                            <select id="payment_method" name="payment_method"
                                class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">{{ __('app.label.all') }}</option>
                                <option value="cash" @selected(request('payment_method') === 'cash')>{{ __('app.payment.cash') }}</option>
                                <option value="transfer" @selected(request('payment_method') === 'transfer')>{{ __('app.payment.transfer') }}</option>
                                <option value="qris" @selected(request('payment_method') === 'qris')>{{ __('app.payment.qris') }}</option>
                            </select>
                        </div>

                        <div>
                            <label for="class_id" class="block text-xs font-semibold text-gray-600 mb-1">{{ __('app.label.class') }}</label>
                            <select id="class_id" name="class_id"
                                class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">{{ __('app.label.all') }}</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="date_from" class="block text-xs font-semibold text-gray-600 mb-1">{{ __('app.label.date_from') }}</label>
                            <input id="date_from" name="date_from" type="date" value="{{ request('date_from') }}"
                                class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>

                        <div>
                            <label for="date_to" class="block text-xs font-semibold text-gray-600 mb-1">{{ __('app.label.date_to') }}</label>
                            <input id="date_to" name="date_to" type="date" value="{{ request('date_to') }}"
                                class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>

                        <div class="lg:col-span-6 flex gap-2">
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                {{ __('app.button.filter') }}
                            </button>
                            <a href="{{ route('transactions.index') }}"
                                class="px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                                {{ __('app.button.reset') }}
                            </a>
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.date') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.code') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.student') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.class') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.total_amount') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.status') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($transactions as $transaction)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $transaction->code }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $transaction->student->name ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $transaction->student->schoolClass->name ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Rp
                                            {{ formatRupiah($transaction->total_amount) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($transaction->status == 'completed')
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('app.status.completed') }}</span>
                                            @elseif($transaction->status == 'cancelled')
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ __('app.status.cancelled') }}</span>
                                            @else
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ ucfirst($transaction->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('transactions.show', $transaction) }}"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3">{{ __('app.button.detail') }}</a>
                                            @can('receipts.print')
                                                <button type="button"
                                                    class="text-gray-600 hover:text-gray-900 mr-3"
                                                    onclick="handleReceiptPrint('{{ route('receipts.print', $transaction) }}', {{ (int) ($transaction->receipt_print_count ?? 0) }})">
                                                    {{ __('app.button.print') }}
                                                </button>
                                            @endcan
                                            @can('transactions.cancel')
                                                @if($transaction->status != 'cancelled')
                                                    <button type="button" class="text-red-600 hover:text-red-900"
                                                        onclick="openCancellationModal('{{ route('transactions.destroy', $transaction) }}', '{{ $transaction->code }}')">
                                                        {{ __('app.button.cancel') }}
                                                    </button>
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7"
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ __('app.empty.transactions') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $transactions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="cancellation-modal" class="fixed inset-0 bg-black/40 hidden z-50 items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-lg shadow-xl">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('Cancel Transaction') }}</h3>
                <p id="cancellation-modal-subtitle" class="text-sm text-gray-500 mt-1"></p>
            </div>

            <form id="cancellation-form" method="POST" class="p-6">
                @csrf
                @method('DELETE')
                <div>
                    <x-input-label for="cancellation_reason" :value="__('Cancellation Reason')" />
                    <textarea id="cancellation_reason" name="cancellation_reason" rows="4" required
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        placeholder="{{ __('app.placeholder.cancellation_reason') }}"></textarea>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-semibold uppercase"
                        onclick="closeCancellationModal()">
                        {{ __('app.button.close') }}
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm font-semibold uppercase">
                        {{ __('app.button.confirm_cancel') }}
                    </button>
                </div>
            </form>
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
        const cancellationModal = document.getElementById('cancellation-modal');
        const cancellationForm = document.getElementById('cancellation-form');
        const cancellationSubtitle = document.getElementById('cancellation-modal-subtitle');
        const cancellationReasonInput = document.getElementById('cancellation_reason');
        const reprintModal = document.getElementById('reprint-modal');
        const reprintForm = document.getElementById('reprint-form');
        const reprintReasonType = document.getElementById('reprint_reason_type');
        const reprintReasonOther = document.getElementById('reprint_reason_other');
        const reprintOtherWrap = document.getElementById('reprint-other-wrap');

        function openCancellationModal(actionUrl, transactionCode) {
            cancellationForm.action = actionUrl;
            cancellationSubtitle.textContent = `Transaction: ${transactionCode}`;
            cancellationReasonInput.value = '';
            cancellationModal.classList.remove('hidden');
            cancellationModal.classList.add('flex');
            cancellationReasonInput.focus();
        }

        function closeCancellationModal() {
            cancellationModal.classList.add('hidden');
            cancellationModal.classList.remove('flex');
        }

        cancellationModal.addEventListener('click', function (event) {
            if (event.target === cancellationModal) {
                closeCancellationModal();
            }
        });

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
