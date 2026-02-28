<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bank Reconciliation Session</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="font-semibold">{{ $bankReconciliation->bank_account_name }}</p>
                        <p class="text-sm text-gray-500">Period {{ sprintf('%02d/%04d', $bankReconciliation->period_month, $bankReconciliation->period_year) }}</p>
                    </div>
                    <div class="text-sm text-gray-700">Status: <span class="font-semibold">{{ strtoupper($bankReconciliation->status) }}</span></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-4">
                    <div class="rounded-md border p-3 bg-gray-50"><p class="text-xs text-gray-500 uppercase">Total Debit</p><p class="text-lg font-bold">Rp {{ number_format($summary['total_debit'], 0, ',', '.') }}</p></div>
                    <div class="rounded-md border p-3 bg-gray-50"><p class="text-xs text-gray-500 uppercase">Total Credit</p><p class="text-lg font-bold">Rp {{ number_format($summary['total_credit'], 0, ',', '.') }}</p></div>
                    <div class="rounded-md border p-3 bg-gray-50"><p class="text-xs text-gray-500 uppercase">Unmatched</p><p class="text-lg font-bold">{{ $summary['unmatched_count'] }}</p></div>
                    <div class="rounded-md border p-3 bg-gray-50"><p class="text-xs text-gray-500 uppercase">Unmatched Difference</p><p class="text-lg font-bold">Rp {{ number_format($summary['difference'], 0, ',', '.') }}</p></div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Import Bank Mutation CSV</h3>
                <form method="POST" action="{{ route('bank-reconciliation.import', $bankReconciliation) }}" enctype="multipart/form-data" class="flex items-end gap-3">
                    @csrf
                    <div>
                        <x-input-label for="file" value="CSV (date,description,reference,amount,type)" />
                        <input id="file" name="file" type="file" class="block mt-1" accept=".csv,.txt" required>
                    </div>
                    <x-primary-button>Import</x-primary-button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Lines</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Match</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($bankReconciliation->lines as $line)
                                <tr>
                                    <td class="px-4 py-3 text-sm">{{ $line->line_date?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $line->description ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $line->reference ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ strtoupper($line->type) }}</td>
                                    <td class="px-4 py-3 text-sm text-right">Rp {{ number_format((float) $line->amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm">{{ strtoupper($line->match_status) }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($line->match_status !== 'matched')
                                            <form method="POST" action="{{ route('bank-reconciliation.match', [$bankReconciliation, $line]) }}" class="flex items-center gap-2">
                                                @csrf
                                                <select name="transaction_id" class="border-gray-300 rounded-md text-sm" required>
                                                    <option value="">Tx</option>
                                                    @foreach($transactions as $tx)
                                                        <option value="{{ $tx->id }}">{{ $tx->transaction_number }} (Rp {{ number_format((float) $tx->total_amount, 0, ',', '.') }})</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="text-indigo-600 hover:text-indigo-900">Match</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('bank-reconciliation.unmatch', [$bankReconciliation, $line]) }}">
                                                @csrf
                                                <button type="submit" class="text-red-600 hover:text-red-900">Unmatch</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-4 text-sm text-gray-500 text-center">No lines.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @can('bank-reconciliation.close')
                    @if($bankReconciliation->status !== 'closed')
                        <form method="POST" action="{{ route('bank-reconciliation.close', $bankReconciliation) }}" class="mt-4" onsubmit="return confirm('Close this session?');">
                            @csrf
                            <x-primary-button>Close Session</x-primary-button>
                        </form>
                    @endif
                @endcan
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Audit Log</h3>
                <ul class="divide-y divide-gray-200">
                    @forelse($bankReconciliation->logs as $log)
                        <li class="py-2 text-sm text-gray-700">
                            <span class="font-semibold">{{ $log->created_at?->format('Y-m-d H:i:s') }}</span>
                            - {{ $log->action }}
                            @if($log->actor)
                                by {{ $log->actor->name }}
                            @endif
                        </li>
                    @empty
                        <li class="py-2 text-sm text-gray-500">No audit log yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
