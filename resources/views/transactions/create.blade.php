<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Transaction') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('transactions.store') }}" id="transactionForm">
                        @csrf

                        @if($errors->any())
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"
                                role="alert">
                                <ul class="list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Transaction Type -->
                            <div>
                                <x-input-label for="type" :value="__('Transaction Type')" />
                                <select id="type" name="type"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    required onchange="toggleTransactionType()">
                                    <option value="income" {{ old('type', 'income') === 'income' ? 'selected' : '' }}>{{ __('app.payment.income') }}</option>
                                    @if($canCreateExpense)
                                        <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>{{ __('app.payment.expense') }}</option>
                                    @endif
                                </select>
                            </div>

                            <!-- Date -->
                            <div>
                                <x-input-label for="transaction_date" :value="__('Transaction Date')" />
                                <x-text-input id="transaction_date" class="block mt-1 w-full" type="date"
                                    name="transaction_date" :value="old('transaction_date', date('Y-m-d'))" required />
                            </div>

                            <!-- Payment Method -->
                            <div>
                                <x-input-label for="payment_method" :value="__('Payment Method')" />
                                <select id="payment_method" name="payment_method"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    required>
                                    <option value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'selected' : '' }}>{{ __('app.payment.cash') }}</option>
                                    <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>{{ __('app.payment.transfer') }}</option>
                                    <option value="qris" {{ old('payment_method') === 'qris' ? 'selected' : '' }}>{{ __('app.payment.qris') }}</option>
                                </select>
                            </div>

                            <!-- Student (Income only) -->
                            <div>
                                <x-input-label for="student_id" :value="__('Student (optional)')" />
                                <select id="student_id" name="student_id"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                    <option value="">{{ __('app.label.all') }}</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}" {{ (string) old('student_id') === (string) $student->id ? 'selected' : '' }}>
                                            {{ $student->name }} ({{ $student->schoolClass->name ?? '-' }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Jika siswa punya invoice aktif, pembayaran akan diarahkan ke Settlement.</p>
                            </div>

                            <div id="student-invoice-hint" class="md:col-span-2 rounded-md bg-amber-50 border border-amber-200 p-3 text-sm text-amber-900 hidden">
                                <p id="student-invoice-hint-text"></p>
                                <a id="student-invoice-hint-link" href="#"
                                    class="inline-block mt-2 px-3 py-1.5 bg-amber-600 text-white rounded text-xs font-semibold uppercase">
                                    Go to Settlement
                                </a>
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-2">
                                <x-input-label for="description" :value="__('Description')" />
                                <textarea id="description" name="description"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    rows="2">{{ old('description') }}</textarea>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4 mb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Transaction Items') }}</h3>

                            <div id="items-container">
                                <!-- Items will be added here via JS -->
                            </div>

                            <button type="button" onclick="addItem()"
                                class="mt-2 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('+ Add Item') }}
                            </button>
                        </div>

                        <div class="flex justify-end mt-6 border-t border-gray-200 pt-4">
                            <div class="mr-4 flex items-center">
                                <span class="text-lg font-bold mr-2">{{ __('app.label.total') }}:</span>
                                <span class="text-xl font-bold text-indigo-600" id="grand-total">Rp 0</span>
                            </div>
                        </div>

                        <div class="flex justify-end mt-6">
                            <a href="{{ route('transactions.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-2">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button id="submit-label">
                                {{ __('Process Transaction') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const incomeFeeTypes = @json($incomeFeeTypes);
        const expenseFeeTypes = @json($expenseFeeTypes);
        const activeInvoiceHints = @json($activeInvoiceHints);
        const settlementCreateUrl = @json(route('settlements.create'));
        let itemCount = 0;

        const jsTranslations = {
            selectFeeType: @json(__('app.placeholder.select_fee_type')),
            feeType: @json(__('app.label.fee_type')),
            amount: @json(__('app.label.amount')),
            notes: @json(__('app.label.notes')),
            remove: @json(__('app.button.remove')),
            processExpense: @json(__('app.form.process_expense')),
            processIncome: @json(__('app.form.process_income')),
        };

        function getFeeTypesByType() {
            const typeSelect = document.getElementById('type');
            const isExpense = typeSelect && typeSelect.value === 'expense';
            return isExpense ? expenseFeeTypes : incomeFeeTypes;
        }

        function buildFeeTypeOptions(selectedValue = '') {
            const feeTypes = getFeeTypesByType();
            const typeSelect = document.getElementById('type');
            const isExpense = typeSelect && typeSelect.value === 'expense';

            if (!isExpense) {
                let incomeOptions = `<option value="">${jsTranslations.selectFeeType}</option>`;
                feeTypes.forEach(ft => {
                    const selectedAttr = String(ft.id) === String(selectedValue) ? 'selected' : '';
                    incomeOptions += `<option value="${ft.id}" ${selectedAttr}>${ft.name}</option>`;
                });
                return incomeOptions;
            }

            const grouped = {};
            feeTypes.forEach(ft => {
                const key = `${ft.category} | ${ft.subcategory}`;
                if (!grouped[key]) {
                    grouped[key] = [];
                }
                grouped[key].push(ft);
            });

            let expenseOptions = `<option value="">${jsTranslations.selectFeeType}</option>`;
            Object.entries(grouped).forEach(([groupLabel, groupItems]) => {
                expenseOptions += `<optgroup label="${groupLabel.replace('|', ' /')}">`;
                groupItems.forEach(ft => {
                    const selectedAttr = String(ft.id) === String(selectedValue) ? 'selected' : '';
                    expenseOptions += `<option value="${ft.id}" ${selectedAttr}>${ft.name}</option>`;
                });
                expenseOptions += '</optgroup>';
            });

            return expenseOptions;
        }

        function addItem() {
            const container = document.getElementById('items-container');
            const rowId = itemCount++;
            const optionsHtml = buildFeeTypeOptions();

            const html = `
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-4 bg-gray-50 rounded-lg item-row" id="row-${rowId}">
                    <div class="md:col-span-1">
                        <label class="block font-medium text-sm text-gray-700">${jsTranslations.feeType}</label>
                        <select name="items[${rowId}][fee_type_id]" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                            ${optionsHtml}
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-sm text-gray-700">${jsTranslations.amount}</label>
                        <input type="number" name="items[${rowId}][amount]" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full amount-input" required min="0" oninput="calculateTotal()">
                    </div>
                     <div>
                        <label class="block font-medium text-sm text-gray-700">${jsTranslations.notes}</label>
                        <input type="text" name="items[${rowId}][description]" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                    </div>
                    <div class="flex items-end">
                        <button type="button" onclick="removeRow(${rowId})" class="text-red-600 hover:text-red-900 text-sm font-semibold">${jsTranslations.remove}</button>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', html);
        }

        function removeRow(id) {
            const row = document.getElementById(`row-${id}`);
            row.remove();
            calculateTotal();
        }

        function refreshAllFeeTypeOptions() {
            document.querySelectorAll('select[name$="[fee_type_id]"]').forEach(select => {
                const currentValue = select.value;
                select.innerHTML = buildFeeTypeOptions(currentValue);

                if (!Array.from(select.options).some(option => option.value === currentValue)) {
                    select.value = '';
                }
            });
        }

        function refreshStudentInvoiceHint() {
            const typeSelect = document.getElementById('type');
            const studentSelect = document.getElementById('student_id');
            const hintWrap = document.getElementById('student-invoice-hint');
            const hintText = document.getElementById('student-invoice-hint-text');
            const hintLink = document.getElementById('student-invoice-hint-link');

            if (!typeSelect || !studentSelect || !hintWrap || !hintText || !hintLink) {
                return;
            }

            const isExpense = typeSelect.value === 'expense';
            const selectedStudentId = studentSelect.value;

            if (isExpense || !selectedStudentId || !activeInvoiceHints[selectedStudentId]) {
                hintWrap.classList.add('hidden');
                return;
            }

            const hint = activeInvoiceHints[selectedStudentId];
            hintText.textContent = `Siswa memiliki ${hint.count} invoice aktif (contoh: ${hint.first_invoice_number}). Gunakan Settlement agar status invoice dan tunggakan terupdate.`;
            const url = new URL(settlementCreateUrl, window.location.origin);
            url.searchParams.set('student_id', selectedStudentId);
            url.searchParams.set('invoice_id', hint.first_invoice_id);
            hintLink.href = url.toString();

            hintWrap.classList.remove('hidden');
        }

        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('.amount-input').forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            document.getElementById('grand-total').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        }

        // Add first row by default
        addItem();

        function toggleTransactionType() {
            const typeSelect = document.getElementById('type');
            const submitLabel = document.getElementById('submit-label');
            const studentSelect = document.getElementById('student_id');
            const isExpense = typeSelect && typeSelect.value === 'expense';

            if (submitLabel) {
                submitLabel.textContent = isExpense ? jsTranslations.processExpense : jsTranslations.processIncome;
            }

            if (studentSelect) {
                studentSelect.disabled = isExpense;
                if (isExpense) {
                    studentSelect.value = '';
                }
            }

            refreshAllFeeTypeOptions();
            refreshStudentInvoiceHint();
        }

        document.getElementById('student_id')?.addEventListener('change', refreshStudentInvoiceHint);

        toggleTransactionType();
    </script>
</x-app-layout>
