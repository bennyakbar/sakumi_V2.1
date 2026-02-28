<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('app.button.edit') }} {{ __('app.nav.users') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="name" :value="__('app.label.name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="email" value="Email" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>

                        @if($isSuperAdmin)
                            <div>
                                <x-input-label for="unit_id" :value="__('app.unit.unit')" />
                                <select id="unit_id" name="unit_id" required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">-- {{ __('app.unit.unit') }} --</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}" @selected((string) old('unit_id', $user->unit_id) === (string) $unit->id)>
                                            {{ $unit->code }} - {{ $unit->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('unit_id')" />
                            </div>
                        @endif

                        @if($roles->isNotEmpty())
                            <div>
                                <x-input-label for="role" value="Role" />
                                <select id="role" name="role"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">-- Select Role --</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" @selected(old('role', $selectedRole) === $role->name)>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('role')" />
                            </div>
                        @endif

                        <div>
                            <x-input-label for="password" value="New Password (optional)" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                            <x-input-error class="mt-2" :messages="$errors->get('password')" />
                        </div>

                        <div>
                            <x-input-label for="password_confirmation" value="Confirm New Password" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" />
                        </div>

                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                @checked(old('is_active', $user->is_active))>
                            <span class="ms-2 text-sm text-gray-600">{{ __('app.status.active') }}</span>
                        </label>
                        <x-input-error class="mt-2" :messages="$errors->get('is_active')" />

                        <div class="flex items-center gap-3">
                            <x-primary-button>{{ __('app.button.save') }}</x-primary-button>
                            <a href="{{ route('users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('app.button.cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
