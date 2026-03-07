<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Promotion Batch</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('master.promotions.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        <div>
                            <x-input-label for="from_academic_year_id" value="From Academic Year" />
                            <select id="from_academic_year_id" name="from_academic_year_id" class="mt-1 block w-full border-gray-300 rounded-md">
                                <option value="">-- Select --</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" @selected(old('from_academic_year_id') == $year->id)>{{ $year->code }} ({{ $year->status }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="to_academic_year_id" value="To Academic Year" />
                            <select id="to_academic_year_id" name="to_academic_year_id" class="mt-1 block w-full border-gray-300 rounded-md">
                                <option value="">-- Select --</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}" @selected(old('to_academic_year_id') == $year->id)>{{ $year->code }} ({{ $year->status }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="effective_date" value="Effective Date" />
                            <x-text-input id="effective_date" type="date" class="mt-1 block w-full" name="effective_date"
                                :value="old('effective_date', now()->toDateString())" />
                        </div>

                        <div>
                            <x-input-label for="items_json" value="Batch Items JSON" />
                            <p class="text-xs text-gray-500 mt-1">
                                Format: [{"student_id":1,"from_enrollment_id":1,"action":"promote","to_class_id":10,"reason":"Split A"}]
                            </p>
                            <textarea id="items_json" name="items_json" rows="8"
                                class="mt-1 block w-full border-gray-300 rounded-md">{{ old('items_json') }}</textarea>
                            @error('items')
                                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-input-label for="items_csv" value="Or Upload CSV" />
                            <p class="text-xs text-gray-500 mt-1">
                                Header: student_id,from_enrollment_id,action,to_class_id,reason
                            </p>
                            <input id="items_csv" name="items_csv" type="file" accept=".csv,.txt"
                                class="mt-1 block w-full border-gray-300 rounded-md" />
                            @error('items_csv')
                                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('master.promotions.index') }}" class="text-sm text-gray-600">Cancel</a>
                            <x-primary-button>Create Batch</x-primary-button>
                        </div>
                    </form>

                    <div class="mt-10 border-t pt-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Current Enrollments (Sample)</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs text-gray-500 uppercase">Enrollment ID</th>
                                        <th class="px-3 py-2 text-left text-xs text-gray-500 uppercase">Student</th>
                                        <th class="px-3 py-2 text-left text-xs text-gray-500 uppercase">Class</th>
                                        <th class="px-3 py-2 text-left text-xs text-gray-500 uppercase">Academic Year</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($currentEnrollments as $enrollment)
                                        <tr>
                                            <td class="px-3 py-2 text-sm">{{ $enrollment->id }}</td>
                                            <td class="px-3 py-2 text-sm">{{ $enrollment->student?->nis }} - {{ $enrollment->student?->name }}</td>
                                            <td class="px-3 py-2 text-sm">{{ $enrollment->schoolClass?->name }}</td>
                                            <td class="px-3 py-2 text-sm">{{ $enrollment->academicYear?->code }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
