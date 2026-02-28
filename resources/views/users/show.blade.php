<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('app.button.view') }} {{ __('app.nav.users') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $user->name }}</h3>
                            <p class="text-sm text-gray-600">{{ $user->email }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @can('users.edit')
                                <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">{{ __('app.button.edit') }}</a>
                            @endcan
                            <a href="{{ route('users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('app.button.back') }}</a>
                        </div>
                    </div>

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">{{ __('app.unit.unit') }}</dt>
                            <dd class="text-gray-900 font-medium">{{ $user->unit?->code ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('app.label.status') }}</dt>
                            <dd>
                                @if($user->is_active)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('app.status.active') }}</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ __('app.status.inactive') }}</span>
                                @endif
                            </dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-gray-500">Role</dt>
                            <dd class="mt-1">
                                @forelse($user->roles as $role)
                                    <span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800">{{ $role->name }}</span>
                                @empty
                                    <span class="text-gray-500">-</span>
                                @endforelse
                            </dd>
                        </div>
                    </dl>

                    @can('users.edit')
                        <div class="mt-6">
                            <form method="POST" action="{{ route('users.reset-password', $user) }}"
                                onsubmit="return confirm('Reset password for this user and generate temporary password?');">
                                @csrf
                                <button type="submit"
                                    class="px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700">
                                    Admin Reset Password
                                </button>
                            </form>
                        </div>
                    @endcan
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent User Activities</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">When</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($activities as $activity)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $activity->created_at?->format('Y-m-d H:i:s') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $activity->description }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $activity->properties['ip'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-sm text-gray-500 text-center">No activity.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
