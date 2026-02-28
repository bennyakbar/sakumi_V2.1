<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Settlement Detail') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- Header --}}
                    <div class="flex justify-between items-start mb-6 border-b pb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $settlement->settlement_number }}</h3>
                            <p class="text-sm text-gray-500">{{ $settlement->payment_date->format('d F Y') }}</p>
                        </div>
                        <div class="text-right">
                            @if($settlement->status === 'completed')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('app.status.completed') }}</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ __('app.status.cancelled') }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('Student Info') }}</h4>
                            <p class="text-gray-900 font-medium">{{ $settlement->student->name ?? '-' }}</p>
                            <p class="text-gray-600 text-sm">{{ __('app.label.nis') }}: {{ $settlement->student->nis ?? '-' }}</p>
                            <p class="text-gray-600 text-sm">{{ __('app.label.class') }}: {{ $settlement->student->schoolClass->name ?? '-' }}</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('Payment Info') }}</h4>
                            <p class="text-gray-600 text-sm">{{ __('app.label.method') }}: {{ ucfirst($settlement->payment_method) }}</p>
                            <p class="text-gray-600 text-sm">{{ __('app.label.created_by') }}: {{ $settlement->creator->name ?? 'System' }}</p>
                            @if($settlement->reference_number)
                                <p class="text-gray-600 text-sm">{{ __('app.label.reference') }}: {{ $settlement->reference_number }}</p>
                            @endif
                            @if($settlement->notes)
                                <p class="text-gray-600 text-sm mt-2">{{ __('app.label.notes') }}: {{ $settlement->notes }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Amounts --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-500">{{ __('Total Payment') }}</p>
                            <p class="text-xl font-bold text-gray-900">Rp {{ number_format($settlement->total_amount, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-500">{{ __('app.label.allocated') }}</p>
                            <p class="text-xl font-bold text-green-600">Rp {{ number_format($settlement->allocated_amount, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-500">{{ __('app.label.unallocated') }}</p>
                            <p class="text-xl font-bold {{ $settlement->unallocated > 0 ? 'text-yellow-600' : 'text-gray-600' }}">Rp {{ number_format($settlement->unallocated, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    {{-- Allocations --}}
                    <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('Invoice Allocations') }}</h4>
                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Invoice #') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.period') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Invoice Status') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Invoice Total') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.allocated') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($settlement->allocations as $allocation)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            @can('invoices.view')
                                                <a href="{{ route('invoices.show', $allocation->invoice) }}" class="text-indigo-600 hover:text-indigo-900">{{ $allocation->invoice->invoice_number }}</a>
                                            @else
                                                {{ $allocation->invoice->invoice_number }}
                                            @endcan
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="text-xs uppercase text-gray-400">{{ $allocation->invoice->period_type }}</span>
                                            {{ $allocation->invoice->period_identifier }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($allocation->invoice->status === 'unpaid')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ __('app.status.unpaid') }}</span>
                                            @elseif($allocation->invoice->status === 'partially_paid')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">{{ __('app.status.partial') }}</span>
                                            @elseif($allocation->invoice->status === 'paid')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('app.status.paid') }}</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ __('app.status.cancelled') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">Rp {{ number_format($allocation->invoice->total_amount, 0, ',', '.') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-700 text-right">Rp {{ number_format($allocation->amount, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-sm text-gray-500 text-center">{{ __('app.empty.allocations') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Cancellation Info --}}
                    @if($settlement->status === 'cancelled')
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                            <h4 class="text-sm font-semibold text-red-700 mb-1">{{ __('Cancellation Details') }}</h4>
                            <p class="text-sm text-red-600">{{ __('Reason') }}: {{ $settlement->cancellation_reason }}</p>
                            <p class="text-sm text-red-600">{{ __('Cancelled by') }}: {{ $settlement->canceller->name ?? '-' }}</p>
                            <p class="text-sm text-red-600">{{ __('Cancelled at') }}: {{ $settlement->cancelled_at?->format('d/m/Y H:i') }}</p>
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="flex justify-end space-x-4">
                        <a href="{{ route('settlements.index') }}"
                            class="px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                            {{ __('app.button.back') }}
                        </a>
                        @if($settlement->status === 'completed')
                            <a href="{{ route('settlements.print', $settlement) }}" target="_blank"
                                class="px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                {{ __('app.button.print') }}
                            </a>
                        @endif
                        @can('settlements.cancel')
                            @if($settlement->status === 'completed')
                                <button type="button" class="px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700"
                                    onclick="document.getElementById('cancel-modal').classList.remove('hidden'); document.getElementById('cancel-modal').classList.add('flex');">
                                    {{ __('Cancel Settlement') }}
                                </button>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cancel Modal --}}
    @can('settlements.cancel')
        @if($settlement->status === 'completed')
            <div id="cancel-modal" class="fixed inset-0 bg-black/40 hidden z-50 items-center justify-center p-4">
                <div class="bg-white w-full max-w-lg rounded-lg shadow-xl">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">{{ __('Cancel Settlement') }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $settlement->settlement_number }}</p>
                    </div>
                    <form method="POST" action="{{ route('settlements.destroy', $settlement) }}" class="p-6">
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
                                onclick="document.getElementById('cancel-modal').classList.add('hidden'); document.getElementById('cancel-modal').classList.remove('flex');">
                                {{ __('app.button.close') }}
                            </button>
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm font-semibold uppercase">
                                {{ __('app.button.confirm_cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endcan
</x-app-layout>
