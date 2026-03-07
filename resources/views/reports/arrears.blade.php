<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Arrears Report') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <form method="GET" action="{{ route('reports.arrears') }}"
                        class="mb-6 flex gap-4 items-end flex-wrap">
                        <div>
                            <x-input-label for="class_id" :value="__('Class (Optional)')" />
                            <select id="class_id" name="class_id"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                <option value="">{{ __('app.filter.all_classes') }}</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" {{ $classId == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($consolidated ?? false)
                            <input type="hidden" name="scope" value="all">
                        @endif
                        <x-primary-button>
                            {{ __('Filter') }}
                        </x-primary-button>
                        <a href="{{ route('reports.arrears.export', array_merge(request()->all(), ['format' => 'xlsx'])) }}"
                            class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-sm font-semibold uppercase">
                            {{ __('app.button.export_xlsx') }}
                        </a>
                        <a href="{{ route('reports.arrears.export', array_merge(request()->all(), ['format' => 'csv'])) }}"
                            class="px-4 py-2 bg-emerald-100 text-emerald-800 rounded-md hover:bg-emerald-200 text-sm font-semibold uppercase">
                            {{ __('app.button.export_csv') }}
                        </a>

                        @if(auth()->user()->hasRole('super_admin'))
                            <a href="{{ route('reports.arrears', array_merge(request()->except('scope'), ['scope' => ($scope ?? 'unit') === 'all' ? 'unit' : 'all'])) }}"
                                class="px-4 py-2 rounded-md text-sm font-semibold uppercase {{ ($scope ?? 'unit') === 'all' ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                {{ ($scope ?? 'unit') === 'all' ? __('app.unit.current') : __('app.unit.all') }}
                            </a>
                        @endif
                    </form>

                    <div class="flex justify-between items-center mb-4 bg-red-50 p-4 rounded-lg border border-red-100">
                        <div>
                            <span class="text-red-800">{{ __('report.overdue_title') }}</span>
                            @if($consolidated ?? false)
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">{{ __('app.unit.all') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-4 bg-white border rounded-lg p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">{{ __('report.aging_analysis') }}</h3>
                            <p class="text-xs text-gray-500">{{ __('report.as_of', ['date' => $asOfDate->format('d/m/Y')]) }}</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            @foreach($agingSummary as $summary)
                                <div class="rounded-md border border-gray-200 p-3 bg-gray-50">
                                    <p class="text-xs text-gray-500 uppercase">{{ $summary['label'] }}</p>
                                    <p class="text-lg font-bold text-gray-900">{{ number_format($summary['count']) }}</p>
                                    <p class="text-sm text-red-700">{{ formatRupiah($summary['amount']) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    @if($consolidated ?? false)
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('app.unit.unit') }}</th>
                                    @endif
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('report.invoice') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.student') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.class') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.due_date') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('report.invoice_total') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('report.already_paid') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.outstanding') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('report.aging_days') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('report.aging_bucket') }}</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($arrears as $arrear)
                                    <tr>
                                        @if($consolidated ?? false)
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $arrear->unit->code ?? '-' }}</td>
                                        @endif
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $arrear->invoice_number }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $arrear->student?->name ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $arrear->student?->schoolClass?->name ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $arrear->due_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                            {{ formatRupiah((float) $arrear->total_amount) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                            {{ formatRupiah((float) ($arrear->settled_amount ?? 0)) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600 text-right">
                                            {{ formatRupiah((float) ($arrear->outstanding_amount ?? 0)) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                            {{ $arrear->aging_days ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @php
                                                $bucketClass = match($arrear->aging_bucket_key ?? 'current') {
                                                    'd90_plus' => 'bg-red-100 text-red-800',
                                                    'd61_90' => 'bg-orange-100 text-orange-800',
                                                    'd31_60' => 'bg-yellow-100 text-yellow-800',
                                                    default => 'bg-green-100 text-green-800',
                                                };
                                            @endphp
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $bucketClass }}">
                                                {{ $arrear->aging_bucket ?? '0-30' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @can('settlements.create')
                                                <a href="{{ route('settlements.create', ['student_id' => $arrear->student_id, 'invoice_id' => $arrear->id]) }}"
                                                    class="text-indigo-600 hover:text-indigo-900">{{ __('app.button.pay_now') }}</a>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ ($consolidated ?? false) ? 12 : 11 }}"
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ __('app.empty.arrears') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $arrears->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
