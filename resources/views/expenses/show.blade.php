<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Expense Detail #{{ $expense->id }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Entry Info --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-semibold">Entry Information</h3>
                    @php
                        $badgeClass = match($expense->status) {
                            'posted', 'approved' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                            'reversed' => 'bg-orange-100 text-orange-800',
                            default => 'bg-yellow-100 text-yellow-800',
                        };
                    @endphp
                    <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $badgeClass }}">{{ strtoupper($expense->status) }}</span>
                </div>

                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="font-medium text-gray-500">Date</dt>
                        <dd>{{ $expense->entry_date?->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Amount</dt>
                        <dd class="text-lg font-semibold">{{ formatRupiah((float) $expense->amount) }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Category</dt>
                        <dd>{{ $expense->subcategory?->category?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Subcategory</dt>
                        <dd>{{ $expense->subcategory?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Fee Type</dt>
                        <dd>{{ $expense->feeType?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Payment Method</dt>
                        <dd>{{ strtoupper($expense->payment_method) }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Vendor</dt>
                        <dd>{{ $expense->vendor_name ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Created By</dt>
                        <dd>{{ $expense->creator?->name ?? '-' }}</dd>
                    </div>
                    @if($expense->description)
                    <div class="md:col-span-2">
                        <dt class="font-medium text-gray-500">Description</dt>
                        <dd>{{ $expense->description }}</dd>
                    </div>
                    @endif
                    @if($expense->internal_notes)
                    <div class="md:col-span-2">
                        <dt class="font-medium text-gray-500">Internal Notes</dt>
                        <dd class="italic text-gray-600">{{ $expense->internal_notes }}</dd>
                    </div>
                    @endif
                    @if($expense->approver)
                    <div>
                        <dt class="font-medium text-gray-500">Approved By</dt>
                        <dd>{{ $expense->approver->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Approved At</dt>
                        <dd>{{ $expense->approved_at?->format('d M Y H:i') }}</dd>
                    </div>
                    @endif
                    @if($expense->postedTransaction)
                    <div class="md:col-span-2">
                        <dt class="font-medium text-gray-500">Transaction</dt>
                        <dd>
                            <a href="{{ route('transactions.show', $expense->postedTransaction) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $expense->postedTransaction->transaction_number }}
                            </a>
                        </dd>
                    </div>
                    @endif
                </dl>

                {{-- Actions --}}
                <div class="mt-6 flex gap-2">
                    <a href="{{ route('expenses.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-xs font-semibold uppercase">Back</a>

                    @if($expense->status === 'draft' && auth()->user()->can('expenses.approve'))
                        <form method="POST" action="{{ route('expenses.approve', $expense) }}" onsubmit="return confirm('Approve and post this expense?');">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md text-xs font-semibold uppercase">Approve & Post</button>
                        </form>
                        <form method="POST" action="{{ route('expenses.cancel', $expense) }}" onsubmit="return confirm('Cancel this draft?');">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-xs font-semibold uppercase">Cancel Draft</button>
                        </form>
                    @endif

                    @if($expense->status === 'posted' && auth()->user()->can('expenses.approve'))
                        <form method="POST" action="{{ route('expenses.cancel-posted', $expense) }}" class="flex gap-2 items-end" onsubmit="return confirm('This will reverse the transaction. Continue?');">
                            @csrf
                            <div>
                                <input type="text" name="reason" placeholder="Reason for reversal..." required class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
                            </div>
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-xs font-semibold uppercase">Reverse</button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Budget Reference --}}
            @if($budgetInfo)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Budget Reference ({{ $budgetInfo['month'] }}/{{ $budgetInfo['year'] }})</h3>
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div class="text-center p-3 bg-blue-50 rounded-md">
                        <div class="text-xs text-gray-500 uppercase">Planned</div>
                        <div class="text-lg font-semibold text-blue-700">{{ formatRupiah($budgetInfo['planned']) }}</div>
                    </div>
                    <div class="text-center p-3 bg-amber-50 rounded-md">
                        <div class="text-xs text-gray-500 uppercase">Realized</div>
                        <div class="text-lg font-semibold text-amber-700">{{ formatRupiah($budgetInfo['realized']) }}</div>
                    </div>
                    <div class="text-center p-3 {{ $budgetInfo['remaining'] < 0 ? 'bg-red-50' : 'bg-green-50' }} rounded-md">
                        <div class="text-xs text-gray-500 uppercase">Remaining</div>
                        <div class="text-lg font-semibold {{ $budgetInfo['remaining'] < 0 ? 'text-red-700' : 'text-green-700' }}">{{ formatRupiah($budgetInfo['remaining']) }}</div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Journal Entries --}}
            @if($journalEntries->isNotEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Journal Entries</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($journalEntries as $je)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $je->line_no }}</td>
                                <td class="px-4 py-2 text-sm font-mono">{{ $je->account_code }}</td>
                                <td class="px-4 py-2 text-sm">{{ $je->description ?? '-' }}</td>
                                <td class="px-4 py-2 text-sm text-right">{{ (float) $je->debit > 0 ? formatRupiah((float) $je->debit) : '-' }}</td>
                                <td class="px-4 py-2 text-sm text-right">{{ (float) $je->credit > 0 ? formatRupiah((float) $je->credit) : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Attachments --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Attachments</h3>

                @if($expense->attachments->count() > 0)
                    <div class="space-y-2 mb-4">
                        @foreach($expense->attachments as $att)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium">{{ $att->original_name }}</span>
                                    <span class="text-xs text-gray-400">{{ number_format($att->file_size / 1024, 1) }} KB</span>
                                    <span class="text-xs text-gray-400">by {{ $att->uploader?->name ?? '-' }}</span>
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('expenses.attachments.download', $att) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">Download</a>
                                    @if(!$expense->isLocked())
                                        @can('expenses.create')
                                            <form method="POST" action="{{ route('expenses.attachments.destroy', $att) }}" onsubmit="return confirm('Delete this attachment?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 mb-4">No attachments.</p>
                @endif

                @if(!$expense->isLocked())
                    @can('expenses.create')
                    <form method="POST" action="{{ route('expenses.attachments.store', $expense) }}" enctype="multipart/form-data" class="flex gap-2 items-end">
                        @csrf
                        <div class="flex-1">
                            <input name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.pdf"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        </div>
                        <x-primary-button>Upload</x-primary-button>
                    </form>
                    @endcan
                @else
                    <p class="text-xs text-gray-400 italic">Attachments are locked for {{ $expense->status }} entries.</p>
                @endif
            </div>

            {{-- Audit Log Timeline --}}
            @if($expense->auditLogs->count() > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Audit Trail</h3>
                <div class="space-y-3">
                    @foreach($expense->auditLogs->sortByDesc('created_at') as $log)
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-md">
                            <div class="flex-shrink-0 mt-0.5">
                                @php
                                    $dotColor = match($log->event_type) {
                                        'expense_created' => 'bg-blue-500',
                                        'expense_approved', 'expense_posted' => 'bg-green-500',
                                        'expense_cancelled' => 'bg-red-500',
                                        'expense_reversed' => 'bg-orange-500',
                                        'attachment_uploaded' => 'bg-indigo-500',
                                        'attachment_deleted' => 'bg-gray-500',
                                        default => 'bg-gray-400',
                                    };
                                @endphp
                                <span class="block w-2.5 h-2.5 rounded-full {{ $dotColor }}"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-baseline">
                                    <span class="text-sm font-medium">{{ str_replace('_', ' ', strtoupper($log->event_type)) }}</span>
                                    <span class="text-xs text-gray-400">{{ $log->created_at?->format('d M Y H:i:s') }}</span>
                                </div>
                                <div class="text-xs text-gray-500">by {{ $log->user?->name ?? '-' }}</div>
                                @if($log->metadata)
                                    <div class="mt-1 text-xs text-gray-400">
                                        @foreach($log->metadata as $key => $value)
                                            @if(!is_null($value) && $value !== '')
                                                <span class="inline-block mr-2">{{ $key }}: {{ is_array($value) ? json_encode($value) : $value }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
