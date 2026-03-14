<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Students') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Header --}}
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">
                            {{ __('Student Data') }}
                            <span class="ml-2 text-sm font-normal text-gray-500">({{ $students->total() }} {{ __('students') }})</span>
                        </h2>

                        <div class="flex space-x-2">
                            @can('master.students.export')
                                <a href="{{ route('master.students.export') }}"
                                    class="px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700 transition ease-in-out duration-150">
                                    {{ __('app.button.export') }}
                                </a>
                            @endcan
                            @can('master.students.import')
                                <a href="{{ route('master.students.import') }}"
                                    class="px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition ease-in-out duration-150">
                                    {{ __('app.button.import') }}
                                </a>
                            @endcan
                            @can('master.students.create')
                                <a href="{{ route('master.students.create') }}"
                                    class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
                                    {{ __('Add New Student') }}
                                </a>
                            @endcan
                        </div>
                    </div>

                    {{-- Import error list --}}
                    @if (session('error_list') && count(session('error_list')) > 0)
                        <div class="mb-4 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-md" role="alert">
                            <strong class="font-bold">Import Warning!</strong>
                            <span class="block sm:inline text-sm">{{ count(session('error_list')) }} baris gagal diimport:</span>
                            <div class="mt-2 space-y-1 text-sm max-h-60 overflow-y-auto">
                                @foreach (session('error_list') as $error)
                                    @if (is_array($error))
                                        <div class="bg-red-100 rounded px-3 py-1.5">
                                            <span class="font-semibold">Baris {{ $error['row'] }}:</span>
                                            {{ implode(' | ', $error['messages']) }}
                                        </div>
                                    @else
                                        <div class="bg-red-100 rounded px-3 py-1.5">{{ $error }}</div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Filter Bar --}}
                    <form method="GET" action="{{ route('master.students.index') }}" class="mb-4">
                        <div class="flex flex-wrap gap-2 items-end">
                            {{-- Search --}}
                            <div class="flex-1 min-w-[180px]">
                                <label for="search" class="block text-xs font-medium text-gray-500 mb-1">{{ __('Search') }}</label>
                                <input type="text" id="search" name="search" value="{{ request('search') }}"
                                    placeholder="{{ __('Name / NIS / NISN') }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            {{-- Class --}}
                            <div class="min-w-[150px]">
                                <label for="class_id" class="block text-xs font-medium text-gray-500 mb-1">{{ __('app.label.class') }}</label>
                                <select id="class_id" name="class_id"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">— {{ __('All Classes') }} —</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Category --}}
                            <div class="min-w-[150px]">
                                <label for="category_id" class="block text-xs font-medium text-gray-500 mb-1">{{ __('app.label.category') }}</label>
                                <select id="category_id" name="category_id"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">— {{ __('All Categories') }} —</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Status --}}
                            <div class="min-w-[130px]">
                                <label for="status" class="block text-xs font-medium text-gray-500 mb-1">{{ __('app.label.status') }}</label>
                                <select id="status" name="status"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">— {{ __('All Status') }} —</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('app.status.active') }}</option>
                                    <option value="graduated" {{ request('status') === 'graduated' ? 'selected' : '' }}>{{ __('app.status.graduated') }}</option>
                                    <option value="transferred" {{ request('status') === 'transferred' ? 'selected' : '' }}>{{ __('app.status.transferred') }}</option>
                                    <option value="dropped_out" {{ request('status') === 'dropped_out' ? 'selected' : '' }}>{{ __('app.status.dropped_out') }}</option>
                                </select>
                            </div>

                            {{-- Sort --}}
                            <div class="min-w-[150px]">
                                <label for="sort" class="block text-xs font-medium text-gray-500 mb-1">{{ __('Sort') }}</label>
                                <select id="sort" name="sort"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="nis_asc" {{ ($sort ?? 'nis_asc') === 'nis_asc' ? 'selected' : '' }}>NIS ↑</option>
                                    <option value="nis_desc" {{ ($sort ?? '') === 'nis_desc' ? 'selected' : '' }}>NIS ↓</option>
                                    <option value="name_asc" {{ ($sort ?? '') === 'name_asc' ? 'selected' : '' }}>{{ __('app.label.name') }} A–Z</option>
                                    <option value="name_desc" {{ ($sort ?? '') === 'name_desc' ? 'selected' : '' }}>{{ __('app.label.name') }} Z–A</option>
                                    <option value="status_asc" {{ ($sort ?? '') === 'status_asc' ? 'selected' : '' }}>{{ __('app.label.status') }}</option>
                                    <option value="latest" {{ ($sort ?? '') === 'latest' ? 'selected' : '' }}>{{ __('Latest') }}</option>
                                </select>
                            </div>

                            {{-- Buttons --}}
                            <div class="flex gap-2">
                                <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
                                    {{ __('Filter') }}
                                </button>
                                <a href="{{ route('master.students.index') }}"
                                    class="px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition ease-in-out duration-150">
                                    {{ __('Reset') }}
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- Active filter summary --}}
                    @if(request('search') || request('class_id') || request('category_id') || request('status'))
                        <div class="mb-3 text-xs text-gray-500">
                            {{ __('Filtered results') }}:
                            @if(request('search'))
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded bg-indigo-100 text-indigo-700">"{{ request('search') }}"</span>
                            @endif
                            @if(request('class_id'))
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded bg-indigo-100 text-indigo-700">
                                    {{ $classes->firstWhere('id', request('class_id'))?->name ?? '' }}
                                </span>
                            @endif
                            @if(request('category_id'))
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded bg-indigo-100 text-indigo-700">
                                    {{ $categories->firstWhere('id', request('category_id'))?->name ?? '' }}
                                </span>
                            @endif
                            @if(request('status'))
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded bg-indigo-100 text-indigo-700">{{ __('app.status.' . request('status')) }}</span>
                            @endif
                        </div>
                    @endif

                    {{-- Table --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">No</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.nis_nisn') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.name') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.class') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.category') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.status') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('app.label.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($students as $i => $student)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 text-sm text-gray-400">
                                            {{ $students->firstItem() + $i }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $student->nis }}<br>
                                            <span class="text-xs text-gray-400">{{ $student->nisn }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $student->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $student->schoolClass->name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $student->category->name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @php $statusColor = match($student->status) {
                                                'active'      => 'bg-green-100 text-green-800',
                                                'graduated'   => 'bg-blue-100 text-blue-800',
                                                'transferred' => 'bg-yellow-100 text-yellow-800',
                                                default       => 'bg-red-100 text-red-800',
                                            }; @endphp
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                                {{ __('app.status.' . $student->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @can('master.students.edit')
                                                <a href="{{ route('master.students.show', $student) }}"
                                                    class="text-slate-600 hover:text-slate-900 mr-3">{{ __('app.button.view') }}</a>
                                                <a href="{{ route('master.students.edit', $student) }}"
                                                    class="text-indigo-600 hover:text-indigo-900 mr-3">{{ __('app.button.edit') }}</a>
                                            @endcan
                                            @can('master.students.delete')
                                                <form action="{{ route('master.students.destroy', $student) }}" method="POST"
                                                    class="inline-block" onsubmit="return confirm('Hapus siswa ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="text-red-600 hover:text-red-900">{{ __('app.button.delete') }}</button>
                                                </form>
                                                @if(auth()->user()->hasRole('super_admin') && getSetting('dangerous_permanent_delete_enabled', false))
                                                    <form action="{{ route('master.students.destroy', $student) }}" method="POST"
                                                        class="inline-block js-permanent-delete-form"
                                                        data-entity="student" data-entity-id="{{ $student->id }}" data-preview-url="{{ route('settings.permanent-delete.preview') }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="permanent_delete" value="1">
                                                        <input type="hidden" name="confirm_text" value="">
                                                        <button type="submit" class="text-red-900 hover:text-red-700 ml-2">Delete Permanent</button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-sm text-gray-500 text-center">
                                            {{ __('app.empty.students') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $students->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
