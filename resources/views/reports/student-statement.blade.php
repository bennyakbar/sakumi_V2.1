<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Student Statement</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('reports.student-statement') }}" class="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                        <div class="lg:col-span-2">
                            <x-input-label for="student_id" :value="__('app.label.student')" />
                            <select id="student_id" name="student_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                <option value="">{{ __('app.placeholder.select_student') }}</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" {{ (string) $studentId === (string) $student->id ? 'selected' : '' }}>{{ $student->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="date_from" :value="__('app.label.date_from')" />
                            <x-text-input id="date_from" name="date_from" type="date" class="block mt-1 w-full" :value="$dateFrom->toDateString()" />
                        </div>
                        <div>
                            <x-input-label for="date_to" :value="__('app.label.date_to')" />
                            <x-text-input id="date_to" name="date_to" type="date" class="block mt-1 w-full" :value="$dateTo->toDateString()" />
                        </div>
                        <div class="flex gap-2">
                            @if($consolidated)
                                <input type="hidden" name="scope" value="all">
                            @endif
                            <x-primary-button>{{ __('app.button.filter') }}</x-primary-button>
                            <a href="{{ route('reports.student-statement') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-semibold uppercase">{{ __('app.button.reset') }}</a>
                        </div>
                    </form>

                    <div class="mb-4 flex flex-wrap gap-2">
                        @if($studentId)
                            <a href="{{ route('reports.student-statement.export', array_merge(request()->all(), ['format' => 'xlsx'])) }}" class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-sm font-semibold uppercase">{{ __('app.button.export_xlsx') }}</a>
                            <a href="{{ route('reports.student-statement.export', array_merge(request()->all(), ['format' => 'csv'])) }}" class="px-4 py-2 bg-emerald-100 text-emerald-800 rounded-md hover:bg-emerald-200 text-sm font-semibold uppercase">{{ __('app.button.export_csv') }}</a>
                        @endif
                        @if(auth()->user()->hasRole('super_admin'))
                            <a href="{{ route('reports.student-statement', array_merge(request()->except('scope'), ['scope' => ($scope ?? 'unit') === 'all' ? 'unit' : 'all'])) }}" class="px-4 py-2 rounded-md text-sm font-semibold uppercase {{ ($scope ?? 'unit') === 'all' ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">{{ ($scope ?? 'unit') === 'all' ? __('app.unit.current') : __('app.unit.all') }}</a>
                        @endif
                    </div>

                    @if($selectedStudent)
                        <div class="mb-4 rounded-md border p-3 bg-gray-50">
                            <p class="text-sm font-semibold">{{ $selectedStudent->name }}</p>
                            <p class="text-xs text-gray-500">{{ $dateFrom->format('d/m/Y') }} - {{ $dateTo->format('d/m/Y') }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                            <div class="rounded-md border p-3 bg-gray-50"><p class="text-xs text-gray-500 uppercase">Opening</p><p class="text-lg font-bold">{{ formatRupiah($summary['opening_balance']) }}</p></div>
                            <div class="rounded-md border p-3 bg-gray-50"><p class="text-xs text-gray-500 uppercase">Debit</p><p class="text-lg font-bold text-red-700">{{ formatRupiah($summary['total_debit']) }}</p></div>
                            <div class="rounded-md border p-3 bg-gray-50"><p class="text-xs text-gray-500 uppercase">Credit</p><p class="text-lg font-bold text-green-700">{{ formatRupiah($summary['total_credit']) }}</p></div>
                            <div class="rounded-md border p-3 bg-gray-50"><p class="text-xs text-gray-500 uppercase">Closing</p><p class="text-lg font-bold">{{ formatRupiah($summary['closing_balance']) }}</p></div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.date') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.description') }}</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr class="bg-yellow-50">
                                        <td class="px-4 py-3 text-sm" colspan="5">Opening Balance</td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold">{{ formatRupiah($summary['opening_balance']) }}</td>
                                    </tr>
                                    @foreach($statementRows as $row)
                                        <tr>
                                            <td class="px-4 py-3 text-sm">{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                                            <td class="px-4 py-3 text-sm">{{ $row['reference'] }}</td>
                                            <td class="px-4 py-3 text-sm">{{ $row['description'] }}</td>
                                            <td class="px-4 py-3 text-sm text-right">{{ formatRupiah((float) $row['debit']) }}</td>
                                            <td class="px-4 py-3 text-sm text-right">{{ formatRupiah((float) $row['credit']) }}</td>
                                            <td class="px-4 py-3 text-sm text-right font-semibold">{{ formatRupiah((float) $row['balance']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="rounded-md border p-4 bg-gray-50 text-gray-600 text-sm">Pilih siswa dan periode untuk melihat statement.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
