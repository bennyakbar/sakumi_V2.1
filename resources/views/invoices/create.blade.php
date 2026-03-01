<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Invoice') }}
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

                    {{-- Student Selector --}}
                    <form method="GET" action="{{ route('invoices.create') }}" class="mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <x-input-label for="student_id" :value="__('Select Student')" />
                                <select id="student_id" name="student_id"
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

                    @if($selectedStudentId && $obligations->isNotEmpty())
                        <form method="POST" action="{{ route('invoices.store') }}">
                            @csrf
                            <input type="hidden" name="student_id" value="{{ $selectedStudentId }}">

                            <div class="border-t border-gray-200 pt-4 mb-4">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Unpaid Obligations') }}</h3>
                                <p class="text-sm text-gray-500 mb-4">{{ __('app.form.select_obligations') }}</p>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                    <input type="checkbox" id="select-all" class="rounded border-gray-300">
                                                </th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.fee_type') }}</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.period') }}</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('app.label.amount') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($obligations as $obligation)
                                                <tr>
                                                    <td class="px-4 py-4">
                                                        <input type="checkbox" name="obligation_ids[]" value="{{ $obligation->id }}" class="obligation-checkbox rounded border-gray-300"
                                                            data-amount="{{ $obligation->amount }}" {{ is_array(old('obligation_ids')) && in_array($obligation->id, old('obligation_ids')) ? 'checked' : '' }}>
                                                    </td>
                                                    <td class="px-4 py-4 text-sm text-gray-900">{{ $obligation->feeType->name }}</td>
                                                    <td class="px-4 py-4 text-sm text-gray-500">{{ sprintf('%02d/%d', $obligation->month, $obligation->year) }}</td>
                                                    <td class="px-4 py-4 text-sm font-medium text-gray-900 text-right">{{ formatRupiah($obligation->amount) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-gray-50">
                                                <td colspan="3" class="px-4 py-4 text-sm font-bold text-gray-900 text-right">{{ __('app.form.selected_total') }}</td>
                                                <td class="px-4 py-4 text-sm font-bold text-indigo-600 text-right" id="selected-total">Rp 0</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <x-input-label for="due_date" :value="__('Due Date')" />
                                    <x-text-input id="due_date" class="block mt-1 w-full" type="date" name="due_date" :value="old('due_date', now()->addDays(30)->format('Y-m-d'))" required />
                                </div>
                                <div>
                                    <x-input-label for="notes" :value="__('Notes (Optional)')" />
                                    <textarea id="notes" name="notes" rows="2"
                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">{{ old('notes') }}</textarea>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-4">
                                <a href="{{ route('invoices.index') }}"
                                    class="px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                                    {{ __('app.button.cancel') }}
                                </a>
                                <x-primary-button>{{ __('Create Invoice') }}</x-primary-button>
                            </div>
                        </form>
                    @elseif($selectedStudentId && $obligations->isEmpty())
                        <div class="border-t border-gray-200 pt-4">
                            <p class="text-gray-500 text-center py-8">{{ __('app.form.no_obligations') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.obligation-checkbox');
        const totalDisplay = document.getElementById('selected-total');

        function updateTotal() {
            let total = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    total += parseFloat(cb.dataset.amount) || 0;
                }
            });
            totalDisplay.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
                updateTotal();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateTotal);
        });

        updateTotal();
    </script>
</x-app-layout>
