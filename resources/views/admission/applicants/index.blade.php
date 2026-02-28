<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('app.nav.applicants') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">
                            {{ __('app.nav.applicants') }}
                            <span class="ml-2 text-sm font-normal text-gray-500">({{ $applicants->total() }})</span>
                        </h2>

                        @can('admission.applicants.create')
                            <a href="{{ route('admission.applicants.create') }}"
                                class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
                                {{ __('app.button.create') }}
                            </a>
                        @endcan
                    </div>

                    {{-- Filter Bar --}}
                    <form method="GET" action="{{ route('admission.applicants.index') }}" class="mb-4">
                        <div class="flex flex-wrap gap-2 items-end">
                            <div class="flex-1 min-w-[180px]">
                                <label for="search" class="block text-xs font-medium text-gray-500 mb-1">{{ __('Search') }}</label>
                                <input type="text" id="search" name="search" value="{{ request('search') }}"
                                    placeholder="Nama / No. Registrasi"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div class="min-w-[150px]">
                                <label for="admission_period_id" class="block text-xs font-medium text-gray-500 mb-1">{{ __('app.label.period') }}</label>
                                <select id="admission_period_id" name="admission_period_id"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">-- Semua --</option>
                                    @foreach ($periods as $period)
                                        <option value="{{ $period->id }}" {{ request('admission_period_id') == $period->id ? 'selected' : '' }}>
                                            {{ $period->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="min-w-[130px]">
                                <label for="status" class="block text-xs font-medium text-gray-500 mb-1">{{ __('app.label.status') }}</label>
                                <select id="status" name="status"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">-- Semua --</option>
                                    <option value="registered" {{ request('status') === 'registered' ? 'selected' : '' }}>{{ __('app.status.registered') }}</option>
                                    <option value="under_review" {{ request('status') === 'under_review' ? 'selected' : '' }}>{{ __('app.status.under_review') }}</option>
                                    <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>{{ __('app.status.accepted') }}</option>
                                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('app.status.rejected') }}</option>
                                    <option value="enrolled" {{ request('status') === 'enrolled' ? 'selected' : '' }}>{{ __('app.status.enrolled') }}</option>
                                </select>
                            </div>

                            <div class="min-w-[130px]">
                                <label for="target_class_id" class="block text-xs font-medium text-gray-500 mb-1">{{ __('app.label.class') }}</label>
                                <select id="target_class_id" name="target_class_id"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">-- Semua --</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}" {{ request('target_class_id') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex gap-2">
                                <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                    {{ __('Filter') }}
                                </button>
                                <a href="{{ route('admission.applicants.index') }}"
                                    class="px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                                    {{ __('Reset') }}
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">{{ __('app.label.no') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Registrasi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.period') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.class') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.status') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($applicants as $i => $applicant)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 text-sm text-gray-400">{{ $applicants->firstItem() + $i }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $applicant->registration_number }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $applicant->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $applicant->admissionPeriod->name ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $applicant->targetClass->name ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @php $statusColor = match($applicant->status) {
                                                'registered'   => 'bg-gray-100 text-gray-800',
                                                'under_review' => 'bg-yellow-100 text-yellow-800',
                                                'accepted'     => 'bg-blue-100 text-blue-800',
                                                'rejected'     => 'bg-red-100 text-red-800',
                                                'enrolled'     => 'bg-green-100 text-green-800',
                                                default        => 'bg-gray-100 text-gray-800',
                                            }; @endphp
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                                {{ __('app.status.' . $applicant->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admission.applicants.show', $applicant) }}"
                                                class="text-slate-600 hover:text-slate-900 mr-3">{{ __('app.button.view') }}</a>
                                            @can('admission.applicants.edit')
                                                @if($applicant->status !== 'enrolled')
                                                    <a href="{{ route('admission.applicants.edit', $applicant) }}"
                                                        class="text-indigo-600 hover:text-indigo-900 mr-3">{{ __('app.button.edit') }}</a>
                                                @endif
                                            @endcan
                                            @can('admission.applicants.delete')
                                                @if($applicant->status !== 'enrolled')
                                                    <form action="{{ route('admission.applicants.destroy', $applicant) }}" method="POST"
                                                        class="inline-block" onsubmit="return confirm('{{ __('app.button.confirm') }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">{{ __('app.button.delete') }}</button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-sm text-gray-500 text-center">
                                            {{ __('app.empty.applicants') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $applicants->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
