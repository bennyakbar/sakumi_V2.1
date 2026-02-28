<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="academic_year_current" :value="__('app.label.academic_year')" />
                            <x-text-input
                                id="academic_year_current"
                                name="academic_year_current"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('academic_year_current', $academicYearCurrent)"
                                placeholder="2025/2026"
                                required
                            />
                            <p class="mt-2 text-xs text-gray-500">Format: YYYY/YYYY (contoh: 2025/2026)</p>
                            <x-input-error class="mt-2" :messages="$errors->get('academic_year_current')" />
                        </div>

                        <div class="rounded-md border border-amber-200 bg-amber-50 p-4">
                            <label for="dangerous_permanent_delete_enabled" class="flex items-start gap-3">
                                <input
                                    id="dangerous_permanent_delete_enabled"
                                    name="dangerous_permanent_delete_enabled"
                                    type="checkbox"
                                    value="1"
                                    class="mt-1 rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500"
                                    @checked(old('dangerous_permanent_delete_enabled', $dangerousPermanentDeleteEnabled))
                                />
                                <span class="text-sm text-amber-900">
                                    <span class="font-semibold">Aktifkan Permanent Delete (Superadmin saja)</span><br>
                                    Saat aktif, superadmin bisa menghapus data secara permanen dengan konfirmasi
                                    <code>HAPUS PERMANEN</code> dan pre-check dependensi.
                                </span>
                            </label>
                            <x-input-error class="mt-2" :messages="$errors->get('dangerous_permanent_delete_enabled')" />
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>{{ __('app.button.save') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
