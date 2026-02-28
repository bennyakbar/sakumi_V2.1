<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('app.nav.admission_periods') }} - {{ $period->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <div class="text-gray-500">{{ __('app.label.name') }}</div>
                            <div class="font-medium">{{ $period->name }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">{{ __('app.label.academic_year') }}</div>
                            <div class="font-medium">{{ $period->academic_year }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Registration Open</div>
                            <div class="font-medium">{{ $period->registration_open?->format('d/m/Y') }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Registration Close</div>
                            <div class="font-medium">{{ $period->registration_close?->format('d/m/Y') }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">{{ __('app.label.status') }}</div>
                            <div class="font-medium">
                                @php $statusColor = match($period->status) {
                                    'open'   => 'bg-green-100 text-green-800',
                                    'closed' => 'bg-red-100 text-red-800',
                                    default  => 'bg-gray-100 text-gray-800',
                                }; @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                    {{ __('app.status.' . $period->status) }}
                                </span>
                            </div>
                        </div>
                        @if($period->notes)
                            <div class="md:col-span-2">
                                <div class="text-gray-500">{{ __('app.label.notes') }}</div>
                                <div class="font-medium">{{ $period->notes }}</div>
                            </div>
                        @endif
                    </div>

                    {{-- Quota Table --}}
                    @if($period->quotas->isNotEmpty())
                        <div class="mt-8 border-t pt-6">
                            <h3 class="text-base font-semibold text-gray-900 mb-4">Kuota Per Kelas</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.class') }}</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kuota</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terisi</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sisa</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($period->quotas as $quota)
                                            @php
                                                $stats = $quotaStats[$quota->class_id] ?? ['filled' => 0, 'quota' => $quota->quota];
                                                $remaining = $stats['quota'] - $stats['filled'];
                                            @endphp
                                            <tr>
                                                <td class="px-4 py-3 text-sm">{{ $quota->schoolClass->name ?? '-' }}</td>
                                                <td class="px-4 py-3 text-sm">{{ $quota->quota }}</td>
                                                <td class="px-4 py-3 text-sm">{{ $stats['filled'] }}</td>
                                                <td class="px-4 py-3 text-sm">
                                                    <span class="{{ $remaining <= 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                                                        {{ $remaining }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 flex gap-2">
                        <a href="{{ route('admission.periods.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                            {{ __('app.button.back') }}
                        </a>
                        @can('admission.periods.edit')
                            <a href="{{ route('admission.periods.edit', $period) }}"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                {{ __('app.button.edit') }}
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
