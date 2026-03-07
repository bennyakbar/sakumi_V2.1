<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add New Fee Matrix') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('master.fee-matrix.store') }}">
                        @csrf

                        @if($errors->has('error'))
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"
                                role="alert">
                                <span class="block sm:inline">{{ $errors->first('error') }}</span>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Class -->
                            <div>
                                <x-input-label for="class_id" :value="__('Class')" />
                                <select id="class_id" name="class_id"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                    <option value="">-- All Classes --</option>
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
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                    <option value="">-- All Categories --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                            </div>

                            <!-- Fee Type -->
                            <div>
                                <x-input-label for="fee_type_id" :value="__('Fee Type')" />
                                <select id="fee_type_id" name="fee_type_id"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    required>
                                    <option value="">-- Select Fee Type --</option>
                                    @foreach($feeTypes as $feeType)
                                        <option value="{{ $feeType->id }}" {{ old('fee_type_id') == $feeType->id ? 'selected' : '' }}>{{ $feeType->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('fee_type_id')" class="mt-2" />
                            </div>

                            <!-- Amount -->
                            <div>
                                <x-input-label for="amount" :value="__('Amount (Rp)')" />
                                <x-text-input id="amount" class="block mt-1 w-full" type="number" name="amount"
                                    :value="old('amount')" min="0" required />
                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            </div>

                            <!-- Effective Dates -->
                            <div>
                                <x-input-label for="effective_from" :value="__('Effective From')" />
                                <x-text-input id="effective_from" class="block mt-1 w-full" type="date"
                                    name="effective_from" :value="old('effective_from', date('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('effective_from')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="effective_to" :value="__('Effective To (Optional)')" />
                                <x-text-input id="effective_to" class="block mt-1 w-full" type="date"
                                    name="effective_to" :value="old('effective_to')" />
                                <x-input-error :messages="$errors->get('effective_to')" class="mt-2" />
                            </div>

                            <!-- Status -->
                            <div class="flex items-center mt-4">
                                <label for="is_active" class="inline-flex items-center">
                                    <input id="is_active" type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                                    <span class="ms-2 text-sm text-gray-600">{{ __('Active') }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end mt-6">
                            <a href="{{ route('master.fee-matrix.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-2">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>
                                {{ __('Save Matrix') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
