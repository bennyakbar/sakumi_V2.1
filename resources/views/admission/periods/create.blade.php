<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('app.nav.admission_periods') }} - {{ __('app.button.create') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admission.periods.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('app.label.name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                    :value="old('name')" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="academic_year" :value="__('app.label.academic_year')" />
                                <x-text-input id="academic_year" class="block mt-1 w-full" type="text" name="academic_year"
                                    :value="old('academic_year')" placeholder="2026/2027" required />
                                <x-input-error :messages="$errors->get('academic_year')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="registration_open" value="Registration Open" />
                                <x-text-input id="registration_open" class="block mt-1 w-full" type="date" name="registration_open"
                                    :value="old('registration_open')" required />
                                <x-input-error :messages="$errors->get('registration_open')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="registration_close" value="Registration Close" />
                                <x-text-input id="registration_close" class="block mt-1 w-full" type="date" name="registration_close"
                                    :value="old('registration_close')" required />
                                <x-input-error :messages="$errors->get('registration_close')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="status" :value="__('app.label.status')" />
                                <select id="status" name="status"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    required>
                                    <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>{{ __('app.status.draft') }}</option>
                                    <option value="open" {{ old('status') === 'open' ? 'selected' : '' }}>{{ __('app.status.open') }}</option>
                                    <option value="closed" {{ old('status') === 'closed' ? 'selected' : '' }}>{{ __('app.status.closed') }}</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="notes" :value="__('app.label.notes')" />
                                <textarea id="notes" name="notes"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    rows="3">{{ old('notes') }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                        </div>

                        {{-- Quota Section --}}
                        <div class="mt-8 border-t pt-6" x-data="quotaManager()">
                            <h3 class="text-base font-semibold text-gray-900 mb-4">Kuota Per Kelas</h3>

                            <template x-for="(row, index) in rows" :key="index">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3 items-end">
                                    <div>
                                        <x-input-label :value="__('app.label.class')" />
                                        <select :name="'quotas[' + index + '][class_id]'" x-model="row.class_id"
                                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                            <option value="">-- {{ __('app.label.class') }} --</option>
                                            @foreach($classes as $class)
                                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label value="Kuota" />
                                        <x-text-input :type="'number'" :name="'quotas[' + index + '][quota]'" x-model="row.quota"
                                            class="block mt-1 w-full" min="1" />
                                    </div>
                                    <div>
                                        <button type="button" @click="removeRow(index)"
                                            class="px-3 py-2 bg-red-100 text-red-700 rounded-md text-sm hover:bg-red-200">
                                            {{ __('app.button.remove') }}
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <button type="button" @click="addRow()"
                                class="mt-2 px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                                + Tambah Kuota
                            </button>
                        </div>

                        <div class="flex justify-end mt-6">
                            <a href="{{ route('admission.periods.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 mr-2">
                                {{ __('app.button.cancel') }}
                            </a>
                            <x-primary-button>
                                {{ __('app.button.save') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function quotaManager() {
            return {
                rows: [],
                addRow() { this.rows.push({ class_id: '', quota: '' }); },
                removeRow(index) { this.rows.splice(index, 1); }
            }
        }
    </script>
    @endpush
</x-app-layout>
