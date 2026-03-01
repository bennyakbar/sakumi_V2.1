<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">AR Outstanding</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('reports.ar-outstanding') }}" class="mb-6 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 items-end">
                        <div>
                            <x-input-label for="date_from" :value="__('app.label.date_from')" />
                            <x-text-input id="date_from" name="date_from" type="date" class="block mt-1 w-full" :value="$dateFrom->toDateString()" />
                        </div>
                        <div>
                            <x-input-label for="date_to" :value="__('app.label.date_to')" />
                            <x-text-input id="date_to" name="date_to" type="date" class="block mt-1 w-full" :value="$dateTo->toDateString()" />
                        </div>
                        <div>
                            <x-input-label for="class_id" :value="__('app.label.class')" />
                            <select id="class_id" name="class_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                <option value="">{{ __('app.filter.all_classes') }}</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" {{ (string) $classId === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="category_id" :value="__('app.label.category')" />
                            <select id="category_id" name="category_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                <option value="">{{ __('app.placeholder.all_categories') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ (string) $categoryId === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="student_id" :value="__('app.label.student')" />
                            <select id="student_id" name="student_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                <option value="">{{ __('app.label.all') }}</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" {{ (string) $studentId === (string) $student->id ? 'selected' : '' }}>{{ $student->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            @if($consolidated)
                                <input type="hidden" name="scope" value="all">
                            @endif
                            <x-primary-button>{{ __('app.button.filter') }}</x-primary-button>
                            <a href="{{ route('reports.ar-outstanding') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-semibold uppercase">{{ __('app.button.reset') }}</a>
                        </div>
                    </form>

                    <div class="mb-4 flex flex-wrap gap-2">
                        <a href="{{ route('reports.ar-outstanding.export', array_merge(request()->all(), ['format' => 'xlsx'])) }}" class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-sm font-semibold uppercase">{{ __('app.button.export_xlsx') }}</a>
                        <a href="{{ route('reports.ar-outstanding.export', array_merge(request()->all(), ['format' => 'csv'])) }}" class="px-4 py-2 bg-emerald-100 text-emerald-800 rounded-md hover:bg-emerald-200 text-sm font-semibold uppercase">{{ __('app.button.export_csv') }}</a>
                        @if(auth()->user()->hasRole('super_admin'))
                            <a href="{{ route('reports.ar-outstanding', array_merge(request()->except('scope'), ['scope' => ($scope ?? 'unit') === 'all' ? 'unit' : 'all'])) }}" class="px-4 py-2 rounded-md text-sm font-semibold uppercase {{ ($scope ?? 'unit') === 'all' ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">{{ ($scope ?? 'unit') === 'all' ? __('app.unit.current') : __('app.unit.all') }}</a>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                        <div class="rounded-md border p-3 bg-gray-50">
                            <p class="text-xs text-gray-500 uppercase">Total Invoice</p>
                            <p class="text-lg font-bold">{{ formatRupiah((float) ($summary->total_invoice ?? 0)) }}</p>
                        </div>
                        <div class="rounded-md border p-3 bg-gray-50">
                            <p class="text-xs text-gray-500 uppercase">Total Settled</p>
                            <p class="text-lg font-bold text-green-700">{{ formatRupiah((float) ($summary->total_settled ?? 0)) }}</p>
                        </div>
                        <div class="rounded-md border p-3 bg-gray-50">
                            <p class="text-xs text-gray-500 uppercase">Total Outstanding</p>
                            <p class="text-lg font-bold text-red-700">{{ formatRupiah((float) ($summary->total_outstanding ?? 0)) }}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    @if($consolidated)
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.unit.unit') }}</th>
                                    @endif
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('report.invoice') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.student') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.class') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.category') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.due_date') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('report.invoice_total') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('report.already_paid') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('app.label.outstanding') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($rows as $row)
                                    <tr>
                                        @if($consolidated)
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $row->unit->code ?? '-' }}</td>
                                        @endif
                                        <td class="px-4 py-3 text-sm font-medium">{{ $row->invoice_number }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $row->student?->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $row->student?->schoolClass?->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $row->student?->category?->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $row->due_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-right">{{ formatRupiah((float) $row->total_amount) }}</td>
                                        <td class="px-4 py-3 text-sm text-right">{{ formatRupiah((float) ($row->settled_amount ?? 0)) }}</td>
                                        <td class="px-4 py-3 text-sm text-right text-red-700 font-semibold">{{ formatRupiah((float) ($row->outstanding_amount ?? 0)) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $consolidated ? 10 : 9 }}" class="px-4 py-4 text-sm text-gray-500 text-center">{{ __('app.empty.entries') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $rows->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
