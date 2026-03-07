<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add New Student') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('master.students.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <x-input-label for="name" :value="__('Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                    :value="old('name')" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <!-- NIS -->
                            <div>
                                <x-input-label for="nis" :value="__('NIS')" />
                                <x-text-input id="nis" class="block mt-1 w-full" type="text" name="nis"
                                    :value="old('nis')" />
                                <x-input-error :messages="$errors->get('nis')" class="mt-2" />
                            </div>

                            <!-- NISN -->
                            <div>
                                <x-input-label for="nisn" :value="__('NISN')" />
                                <x-text-input id="nisn" class="block mt-1 w-full" type="text" name="nisn"
                                    :value="old('nisn')" />
                                <x-input-error :messages="$errors->get('nisn')" class="mt-2" />
                            </div>

                            <!-- Enrollment Date -->
                            <div>
                                <x-input-label for="enrollment_date" :value="__('Enrollment Date')" />
                                <x-text-input id="enrollment_date" class="block mt-1 w-full" type="date"
                                    name="enrollment_date" :value="old('enrollment_date', date('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('enrollment_date')" class="mt-2" />
                            </div>

                            <!-- Class -->
                            <div>
                                <x-input-label for="class_id" :value="__('Class')" />
                                <select id="class_id" name="class_id"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    required>
                                    <option value="">-- Select Class --</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('class_id')" class="mt-2" />
                            </div>

                            <!-- Category -->
                            <div>
                                <x-input-label for="category_id" :value="__('Category')" />
                                <select id="category_id" name="category_id"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    required>
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                            </div>

                            <!-- Gender -->
                            <div>
                                <x-input-label for="gender" :value="__('Gender')" />
                                <select id="gender" name="gender"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    required>
                                    <option value="L" {{ old('gender') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ old('gender') == 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                            </div>

                            <!-- Status -->
                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                <select id="status" name="status"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    required>
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>
                                        Active</option>
                                    <option value="graduated" {{ old('status') == 'graduated' ? 'selected' : '' }}>
                                        Graduated</option>
                                    <option value="transferred" {{ old('status') == 'transferred' ? 'selected' : '' }}>
                                        Transferred</option>
                                    <option value="dropout" {{ old('status') == 'dropout' ? 'selected' : '' }}>Dropout
                                    </option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>

                            <!-- Birth Place -->
                            <div>
                                <x-input-label for="birth_place" :value="__('Birth Place')" />
                                <x-text-input id="birth_place" class="block mt-1 w-full" type="text" name="birth_place"
                                    :value="old('birth_place')" />
                                <x-input-error :messages="$errors->get('birth_place')" class="mt-2" />
                            </div>

                            <!-- Birth Date -->
                            <div>
                                <x-input-label for="birth_date" :value="__('Birth Date')" />
                                <x-text-input id="birth_date" class="block mt-1 w-full" type="date" name="birth_date"
                                    :value="old('birth_date')" />
                                <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
                            </div>

                            <!-- Parent Name -->
                            <div>
                                <x-input-label for="parent_name" :value="__('Parent Name')" />
                                <x-text-input id="parent_name" class="block mt-1 w-full" type="text" name="parent_name"
                                    :value="old('parent_name')" />
                                <x-input-error :messages="$errors->get('parent_name')" class="mt-2" />
                            </div>

                            <!-- Parent Phone -->
                            <div>
                                <x-input-label for="parent_phone" :value="__('Parent Phone')" />
                                <x-text-input id="parent_phone" class="block mt-1 w-full" type="text"
                                    name="parent_phone" :value="old('parent_phone')" />
                                <x-input-error :messages="$errors->get('parent_phone')" class="mt-2" />
                            </div>

                            <!-- Address -->
                            <div class="md:col-span-2">
                                <x-input-label for="address" :value="__('Address')" />
                                <textarea id="address" name="address"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    rows="3">{{ old('address') }}</textarea>
                                <x-input-error :messages="$errors->get('address')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex justify-end mt-6">
                            <a href="{{ route('master.students.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-2">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>
                                {{ __('Save Student') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
