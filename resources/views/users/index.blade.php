<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('app.nav.users') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">
                            {{ __('app.nav.users') }}
                        </h2>
                        <div class="flex gap-2">
                            @can('users.view')
                                <a href="{{ route('users.export', request()->query()) }}"
                                    class="px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700">
                                    {{ __('app.button.export_csv') }}
                                </a>
                            @endcan
                            @can('users.create')
                                <a href="{{ route('users.create') }}"
                                    class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ __('app.button.create') }}
                                </a>
                            @endcan
                        </div>
                    </div>

                    <form method="GET" action="{{ route('users.index') }}" class="mb-4 grid grid-cols-1 md:grid-cols-6 gap-3">
                        <div class="md:col-span-2">
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="{{ __('app.button.search') }} name / email"
                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        </div>
                        <div>
                            <select name="status"
                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">{{ __('app.filter.all_status') }}</option>
                                <option value="active" @selected(request('status') === 'active')>{{ __('app.status.active') }}</option>
                                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('app.status.inactive') }}</option>
                            </select>
                        </div>
                        <div>
                            <select name="role"
                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">All Roles</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($isSuperAdmin)
                            <div>
                                <select name="unit_id"
                                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">{{ __('app.label.all') }} {{ __('app.unit.unit') }}</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}" @selected((string) request('unit_id') === (string) $unit->id)>
                                            {{ $unit->code }} - {{ $unit->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div>
                            <select name="sort"
                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="latest" @selected(request('sort', 'latest') === 'latest')>Latest</option>
                                <option value="name_asc" @selected(request('sort') === 'name_asc')>Name A-Z</option>
                                <option value="name_desc" @selected(request('sort') === 'name_desc')>Name Z-A</option>
                                <option value="email_asc" @selected(request('sort') === 'email_asc')>Email A-Z</option>
                                <option value="email_desc" @selected(request('sort') === 'email_desc')>Email Z-A</option>
                                <option value="role_asc" @selected(request('sort') === 'role_asc')>Role A-Z</option>
                                <option value="role_desc" @selected(request('sort') === 'role_desc')>Role Z-A</option>
                                <option value="status_asc" @selected(request('sort') === 'status_asc')>Active First</option>
                                <option value="status_desc" @selected(request('sort') === 'status_desc')>Inactive First</option>
                            </select>
                        </div>
                        <div>
                            <select name="per_page"
                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach([15, 25, 50, 100] as $size)
                                    <option value="{{ $size }}" @selected((int) request('per_page', $perPage) === $size)>{{ $size }} / page</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                class="px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                {{ __('app.button.filter') }}
                            </button>
                            <a href="{{ route('users.index') }}"
                                class="px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                                {{ __('app.button.reset') }}
                            </a>
                        </div>
                    </form>

                    @can('users.edit')
                        <div class="mb-4 flex items-center gap-2">
                            <button type="button" onclick="submitBulkStatus('activate')"
                                class="px-3 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700">
                                Activate Selected
                            </button>
                            <button type="button" onclick="submitBulkStatus('deactivate')"
                                class="px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                Deactivate Selected
                            </button>
                        </div>
                    @endcan

                    @php($colspan = auth()->user()->can('users.edit') ? 7 : 6)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    @can('users.edit')
                                        <th class="px-4 py-3 text-left">
                                            <input type="checkbox" id="select-all-users"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        </th>
                                    @endcan
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.unit.unit') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.status') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('app.label.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($users as $user)
                                    <tr>
                                        @can('users.edit')
                                            <td class="px-4 py-4 text-sm">
                                                @if($user->id !== auth()->id())
                                                    <input type="checkbox" class="bulk-user-id rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                        value="{{ $user->id }}">
                                                @endif
                                            </td>
                                        @endcan
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $user->name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ $user->email }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ $user->unit?->code ?? '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            @forelse($user->roles as $role)
                                                <span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800">{{ $role->name }}</span>
                                            @empty
                                                -
                                            @endforelse
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            @if($user->is_active)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('app.status.active') }}</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ __('app.status.inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                            <a href="{{ route('users.show', $user) }}" class="text-slate-600 hover:text-slate-900 mr-3">{{ __('app.button.view') }}</a>

                                            @can('users.edit')
                                                <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">{{ __('app.button.edit') }}</a>
                                            @endcan

                                            @can('users.delete')
                                                @if($user->id !== auth()->id() && $user->is_active)
                                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline-block"
                                                        onsubmit="return confirm('Deactivate this user?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">{{ __('app.button.delete') }}</button>
                                                    </form>
                                                @endif
                                                @if($user->id !== auth()->id() && auth()->user()->hasRole('super_admin') && getSetting('dangerous_permanent_delete_enabled', false))
                                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline-block"
                                                        onsubmit="const t=prompt('Ketik HAPUS PERMANEN untuk menghapus user ini secara permanen'); if(t===null){return false;} this.querySelector('input[name=confirm_text]').value=t; return confirm('Data akan dihapus permanen jika tidak memiliki dependensi. Lanjutkan?');">
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
                                        <td colspan="{{ $colspan }}" class="px-6 py-4 text-sm text-gray-500 text-center">{{ __('app.empty.users') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>

                    <form id="bulk-status-form" action="{{ route('users.bulk-status') }}" method="POST" class="hidden">
                        @csrf
                        <input type="hidden" name="action" id="bulk-status-action">
                        <div id="bulk-status-ids"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @can('users.edit')
        <script>
            (function() {
                const selectAll = document.getElementById('select-all-users');
                const checkboxes = Array.from(document.querySelectorAll('.bulk-user-id'));

                if (selectAll) {
                    selectAll.addEventListener('change', function() {
                        checkboxes.forEach((cb) => {
                            cb.checked = selectAll.checked;
                        });
                    });
                }
            })();

            function submitBulkStatus(action) {
                const selected = Array.from(document.querySelectorAll('.bulk-user-id:checked')).map((el) => el.value);
                if (selected.length === 0) {
                    alert('Select at least one user.');
                    return;
                }

                const actionLabel = action === 'activate' ? 'activate' : 'deactivate';
                if (!confirm(`Are you sure you want to ${actionLabel} ${selected.length} user(s)?`)) {
                    return;
                }

                const idsContainer = document.getElementById('bulk-status-ids');
                idsContainer.innerHTML = '';

                selected.forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    idsContainer.appendChild(input);
                });

                document.getElementById('bulk-status-action').value = action;
                document.getElementById('bulk-status-form').submit();
            }
        </script>
    @endcan
</x-app-layout>
