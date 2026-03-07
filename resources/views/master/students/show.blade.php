<x-master-page :title="__('Student Detail')">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <div class="text-gray-500">{{ __('app.label.name') }}</div>
            <div class="font-medium">{{ $student->name }}</div>
        </div>
        <div>
            <div class="text-gray-500">{{ __('app.label.nis_nisn') }}</div>
            <div class="font-medium">{{ $student->nis }} / {{ $student->nisn ?? '-' }}</div>
        </div>
        <div>
            <div class="text-gray-500">{{ __('app.label.class') }}</div>
            <div class="font-medium">{{ $student->schoolClass?->name ?? '-' }}</div>
        </div>
        <div>
            <div class="text-gray-500">{{ __('app.label.category') }}</div>
            <div class="font-medium">{{ $student->category?->name ?? '-' }}</div>
        </div>
        <div>
            <div class="text-gray-500">{{ __('app.label.status') }}</div>
            <div class="font-medium">{{ $student->status }}</div>
        </div>
        <div>
            <div class="text-gray-500">{{ __('app.label.enrollment_date') }}</div>
            <div class="font-medium">{{ optional($student->enrollment_date)->format('Y-m-d') }}</div>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('master.students.index') }}"
            class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
            {{ __('app.button.back') }}
        </a>
    </div>

    @if(auth()->user()->canAny(['master.student-fee-mappings.view', 'master.student-fee-mappings.create', 'master.student-fee-mappings.edit', 'master.student-fee-mappings.delete']))
        <div class="mt-8 border-t pt-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Student Fee Mapping</h3>

            @can('master.student-fee-mappings.create')
                <form method="POST" action="{{ route('master.students.fee-mappings.store', $student) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    @csrf
                    <div class="md:col-span-2">
                        <x-input-label for="fee_matrix_id" value="Fee Matrix" />
                        <select id="fee_matrix_id" name="fee_matrix_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                            <option value="">-- Select Fee Matrix --</option>
                            @foreach($feeMatrices as $matrix)
                                <option value="{{ $matrix->id }}">
                                    {{ $matrix->feeType?->name ?? '-' }} | {{ $matrix->schoolClass?->name ?? 'ALL CLASS' }} | {{ $matrix->category?->name ?? 'ALL CATEGORY' }} | {{ formatRupiah((float) $matrix->amount) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="effective_from" value="Effective From" />
                        <x-text-input id="effective_from" name="effective_from" type="date" class="block mt-1 w-full" :value="now()->toDateString()" required />
                    </div>
                    <div>
                        <x-input-label for="effective_to" value="Effective To (Optional)" />
                        <x-text-input id="effective_to" name="effective_to" type="date" class="block mt-1 w-full" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="notes" :value="__('app.label.notes')" />
                        <textarea id="notes" name="notes" rows="2" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_active" value="1" checked>
                            Active
                        </label>
                    </div>
                    <div class="md:col-span-2">
                        <x-primary-button>{{ __('app.button.save') }}</x-primary-button>
                    </div>
                </form>
            @endcan

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fee Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matrix</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('app.label.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($student->feeMappings->sortByDesc('effective_from') as $mapping)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $mapping->feeMatrix?->feeType?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    {{ $mapping->feeMatrix?->schoolClass?->name ?? 'ALL CLASS' }} /
                                    {{ $mapping->feeMatrix?->category?->name ?? 'ALL CATEGORY' }} -
                                    {{ formatRupiah((float) ($mapping->feeMatrix?->amount ?? 0)) }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ optional($mapping->effective_from)->format('Y-m-d') }}
                                    -
                                    {{ optional($mapping->effective_to)->format('Y-m-d') ?? 'OPEN' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $mapping->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $mapping->is_active ? __('app.status.active') : __('app.status.inactive') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @can('master.student-fee-mappings.edit')
                                        <form method="POST" action="{{ route('master.students.fee-mappings.update', [$student, $mapping]) }}" class="inline-flex items-center gap-2 mr-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="effective_from" value="{{ optional($mapping->effective_from)->format('Y-m-d') }}">
                                            <input type="hidden" name="effective_to" value="{{ optional($mapping->effective_to)->format('Y-m-d') }}">
                                            <input type="hidden" name="notes" value="{{ $mapping->notes }}">
                                            <input type="hidden" name="is_active" value="{{ $mapping->is_active ? 0 : 1 }}">
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $mapping->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    @endcan
                                    @can('master.student-fee-mappings.delete')
                                        <form method="POST" action="{{ route('master.students.fee-mappings.destroy', [$student, $mapping]) }}" class="inline" onsubmit="return confirm('Deactivate this mapping?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">{{ __('app.button.delete') }}</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-sm text-gray-500 text-center">{{ __('app.empty.entries') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-master-page>
