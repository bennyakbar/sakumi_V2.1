<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bank Reconciliation</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Create Session</h3>
                <form method="POST" action="{{ route('bank-reconciliation.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    @csrf
                    <div class="md:col-span-2">
                        <x-input-label for="bank_account_name" value="Bank Account Name" />
                        <x-text-input id="bank_account_name" name="bank_account_name" type="text" class="block mt-1 w-full" required />
                    </div>
                    <div>
                        <x-input-label for="period_month" :value="__('app.label.month')" />
                        <x-text-input id="period_month" name="period_month" type="number" min="1" max="12" class="block mt-1 w-full" :value="now()->month" required />
                    </div>
                    <div>
                        <x-input-label for="period_year" :value="__('app.label.year')" />
                        <x-text-input id="period_year" name="period_year" type="number" min="2020" max="2100" class="block mt-1 w-full" :value="now()->year" required />
                    </div>
                    <div>
                        <x-input-label for="opening_balance" value="Opening Balance" />
                        <x-text-input id="opening_balance" name="opening_balance" type="number" step="1" class="block mt-1 w-full" :value="0" />
                    </div>
                    <div class="md:col-span-5">
                        <x-primary-button>{{ __('app.button.save') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Sessions</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Matched</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unmatched</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($sessions as $session)
                                <tr>
                                    <td class="px-4 py-3 text-sm">{{ $session->bank_account_name }}</td>
                                    <td class="px-4 py-3 text-sm">{{ sprintf('%02d/%04d', $session->period_month, $session->period_year) }}</td>
                                    <td class="px-4 py-3 text-sm">{{ strtoupper($session->status) }}</td>
                                    <td class="px-4 py-3 text-sm text-right">{{ $session->matched_count }}</td>
                                    <td class="px-4 py-3 text-sm text-right">{{ $session->unmatched_count }}</td>
                                    <td class="px-4 py-3 text-sm"><a href="{{ route('bank-reconciliation.show', $session) }}" class="text-indigo-600 hover:text-indigo-900">Open</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-4 text-sm text-gray-500 text-center">No sessions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $sessions->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
