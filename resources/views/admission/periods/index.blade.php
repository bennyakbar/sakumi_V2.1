<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('app.nav.admission_periods') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">
                            {{ __('app.nav.admission_periods') }}
                            <span class="ml-2 text-sm font-normal text-gray-500">({{ $periods->total() }})</span>
                        </h2>

                        @can('admission.periods.create')
                            <a href="{{ route('admission.periods.create') }}"
                                class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
                                {{ __('app.button.create') }}
                            </a>
                        @endcan
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">{{ __('app.label.no') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.academic_year') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.period') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.status') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.nav.applicants') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($periods as $i => $period)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 text-sm text-gray-400">{{ $periods->firstItem() + $i }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $period->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $period->academic_year }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $period->registration_open?->format('d/m/Y') }} - {{ $period->registration_close?->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @php $statusColor = match($period->status) {
                                                'open'   => 'bg-green-100 text-green-800',
                                                'closed' => 'bg-red-100 text-red-800',
                                                default  => 'bg-gray-100 text-gray-800',
                                            }; @endphp
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                                {{ __('app.status.' . $period->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <a href="{{ route('admission.applicants.index', ['admission_period_id' => $period->id]) }}"
                                                class="text-indigo-600 hover:text-indigo-900">
                                                {{ $period->applicants_count }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admission.periods.show', $period) }}"
                                                class="text-slate-600 hover:text-slate-900 mr-3">{{ __('app.button.view') }}</a>
                                            @can('admission.periods.edit')
                                                <a href="{{ route('admission.periods.edit', $period) }}"
                                                    class="text-indigo-600 hover:text-indigo-900 mr-3">{{ __('app.button.edit') }}</a>
                                            @endcan
                                            @can('admission.periods.delete')
                                                <form action="{{ route('admission.periods.destroy', $period) }}" method="POST"
                                                    class="inline-block" onsubmit="return confirm('{{ __('app.button.confirm') }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">{{ __('app.button.delete') }}</button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-sm text-gray-500 text-center">
                                            {{ __('app.empty.admission_periods') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $periods->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
