<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('app.nav.applicants') }} - {{ __('app.button.create') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admission.applicants.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="admission_period_id" :value="__('app.label.period')" />
                                <select id="admission_period_id" name="admission_period_id"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    required>
                                    <option value="">-- Pilih Periode --</option>
                                    @foreach($periods as $period)
                                        <option value="{{ $period->id }}" {{ old('admission_period_id') == $period->id ? 'selected' : '' }}>{{ $period->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('admission_period_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="name" :value="__('app.label.name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                    :value="old('name')" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="target_class_id" :value="__('app.label.class')" />
                                <select id="target_class_id" name="target_class_id"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    required>
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ old('target_class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('target_class_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="category_id" :value="__('app.label.category')" />
                                <select id="category_id" name="category_id"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="gender" :value="__('app.label.gender')" />
                                <select id="gender" name="gender"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    required>
                                    <option value="L" {{ old('gender') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ old('gender') == 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="previous_school" value="Asal Sekolah" />
                                <x-text-input id="previous_school" class="block mt-1 w-full" type="text" name="previous_school"
                                    :value="old('previous_school')" />
                                <x-input-error :messages="$errors->get('previous_school')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="birth_place" :value="__('Birth Place')" />
                                <x-text-input id="birth_place" class="block mt-1 w-full" type="text" name="birth_place"
                                    :value="old('birth_place')" />
                                <x-input-error :messages="$errors->get('birth_place')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="birth_date" :value="__('Birth Date')" />
                                <x-text-input id="birth_date" class="block mt-1 w-full" type="date" name="birth_date"
                                    :value="old('birth_date')" />
                                <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="parent_name" :value="__('Parent Name')" />
                                <x-text-input id="parent_name" class="block mt-1 w-full" type="text" name="parent_name"
                                    :value="old('parent_name')" />
                                <x-input-error :messages="$errors->get('parent_name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="parent_phone" :value="__('Parent Phone')" />
                                <x-text-input id="parent_phone" class="block mt-1 w-full" type="text" name="parent_phone"
                                    :value="old('parent_phone')" />
                                <x-input-error :messages="$errors->get('parent_phone')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="parent_whatsapp" value="WhatsApp" />
                                <x-text-input id="parent_whatsapp" class="block mt-1 w-full" type="text" name="parent_whatsapp"
                                    :value="old('parent_whatsapp')" placeholder="628xxxxxxxxxx" />
                                <x-input-error :messages="$errors->get('parent_whatsapp')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="address" :value="__('Address')" />
                                <textarea id="address" name="address"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    rows="3">{{ old('address') }}</textarea>
                                <x-input-error :messages="$errors->get('address')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="notes" :value="__('app.label.notes')" />
                                <textarea id="notes" name="notes"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    rows="2">{{ old('notes') }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex justify-end mt-6">
                            <a href="{{ route('admission.applicants.index') }}"
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
</x-app-layout>
