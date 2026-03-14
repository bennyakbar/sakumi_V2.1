<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Expense Management</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @can('expenses.create')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Create Expense Draft</h3>
                <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div class="md:col-span-2">
                        <x-input-label for="fee_type_id" value="Expense Fee Type" />
                        <select id="fee_type_id" name="fee_type_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                            <option value="">-- Select --</option>
                            @foreach($expenseFeeTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }} | {{ $type->expenseFeeSubcategory?->category?->name ?? '-' }} / {{ $type->expenseFeeSubcategory?->name ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="entry_date" value="Date" />
                        <x-text-input id="entry_date" name="entry_date" type="date" class="block mt-1 w-full" :value="now()->toDateString()" required />
                    </div>
                    <div>
                        <x-input-label for="payment_method" value="Payment Method" />
                        <select id="payment_method" name="payment_method" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="vendor_name" value="Vendor" />
                        <x-text-input id="vendor_name" name="vendor_name" type="text" class="block mt-1 w-full" />
                    </div>
                    <div>
                        <x-input-label for="amount" :value="__('app.label.amount')" />
                        <x-text-input id="amount" name="amount" type="number" min="1" step="1" class="block mt-1 w-full" required />
                    </div>
                    <div class="md:col-span-3">
                        <x-input-label for="description" :value="__('app.label.description')" />
                        <textarea id="description" name="description" rows="2" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"></textarea>
                    </div>
                    <div class="md:col-span-3">
                        <x-input-label for="internal_notes" value="Internal Notes" />
                        <x-text-input id="internal_notes" name="internal_notes" type="text" class="block mt-1 w-full" placeholder="Catatan internal (tidak tercetak di voucher)" />
                    </div>
                    <div class="md:col-span-3">
                        <x-input-label for="attachments" value="Attachments" />
                        <input id="attachments" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.pdf"
                            class="block mt-1 w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        <p class="mt-1 text-xs text-gray-400">Max 5 files, 5MB each. JPG, PNG, PDF.</p>
                    </div>
                    <div class="md:col-span-3 flex gap-2">
                        <x-primary-button>{{ __('app.button.save') }}</x-primary-button>
                        <a href="{{ route('expenses.budget-report') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-xs font-semibold uppercase">Budget vs Realization</a>
                    </div>
                </form>
            </div>
            @endcan

            {{-- Filter --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('expenses.index') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <x-input-label for="filter_status" value="Status" />
                        <select id="filter_status" name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="">All</option>
                            @foreach(['draft', 'posted', 'reversed', 'cancelled'] as $s)
                                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ strtoupper($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="filter_date_from" value="From" />
                        <x-text-input id="filter_date_from" name="date_from" type="date" class="block mt-1 w-full" :value="request('date_from')" />
                    </div>
                    <div>
                        <x-input-label for="filter_date_to" value="To" />
                        <x-text-input id="filter_date_to" name="date_to" type="date" class="block mt-1 w-full" :value="request('date_to')" />
                    </div>
                    <div>
                        <x-input-label for="filter_vendor" value="Vendor" />
                        <x-text-input id="filter_vendor" name="vendor" type="text" class="block mt-1 w-full" :value="request('vendor')" placeholder="Search vendor..." />
                    </div>
                    <x-primary-button>{{ __('app.button.filter') }}</x-primary-button>
                    @if(request()->hasAny(['status', 'date_from', 'date_to', 'vendor']))
                        <a href="{{ route('expenses.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-xs font-semibold uppercase">Reset</a>
                    @endif
                    @can('expenses.report.view')
                        <a href="{{ route('expenses.export', request()->only(['status', 'date_from', 'date_to', 'vendor'])) }}" class="px-4 py-2 bg-green-600 text-white rounded-md text-xs font-semibold uppercase">Export Excel</a>
                    @endcan
                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Expense Entries</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subcategory</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Att.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($entries as $entry)
                                <tr>
                                    <td class="px-4 py-3 text-sm">{{ $entry->entry_date?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $entry->subcategory?->category?->name ?? '-' }} / {{ $entry->subcategory?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $entry->vendor_name ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @php
                                            $badgeClass = match($entry->status) {
                                                'posted', 'approved' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                                'reversed' => 'bg-orange-100 text-orange-800',
                                                default => 'bg-yellow-100 text-yellow-800',
                                            };
                                        @endphp
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">{{ strtoupper($entry->status) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right">{{ formatRupiah((float) $entry->amount) }}</td>
                                    <td class="px-4 py-3 text-sm text-center">
                                        @if($entry->attachments->count() > 0)
                                            <span class="text-indigo-600" title="{{ $entry->attachments->count() }} file(s)">{{ $entry->attachments->count() }}</span>
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm space-x-1">
                                        <a href="{{ route('expenses.show', $entry) }}" class="text-indigo-600 hover:text-indigo-900">Detail</a>
                                        @if($entry->status === 'draft' && auth()->user()->can('expenses.approve'))
                                            <form method="POST" action="{{ route('expenses.approve', $entry) }}" class="inline-block" onsubmit="return confirm('Approve and post this expense?');">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-900">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('expenses.cancel', $entry) }}" class="inline-block" onsubmit="return confirm('Cancel this draft?');">
                                                @csrf
                                                <button type="submit" class="text-red-600 hover:text-red-900">Cancel</button>
                                            </form>
                                        @elseif($entry->postedTransaction)
                                            <a href="{{ route('transactions.show', $entry->postedTransaction) }}" class="text-indigo-600 hover:text-indigo-900">Txn</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-4 text-sm text-gray-500 text-center">{{ __('app.empty.entries') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $entries->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
