<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Import Students') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Instructions') }}</h3>
                        <ul class="list-disc list-inside text-sm text-gray-600 mb-4 space-y-1">
                            <li>{{ __('Download the template file to ensure correct formatting.') }}</li>
                            <li>{{ __('Fill in the student data. Required fields are: Name, Class Name, Category Name, Gender, Enrollment Date, Status.') }}
                            </li>
                            <li>{{ __('Class Name and Category Name must match exactly with existing data in the system.') }}
                            </li>
                            <li>{{ __('Dates should be in YYYY-MM-DD format.') }}</li>
                            <li>{{ __('Gender should be L (Male) or P (Female).') }}</li>
                        </ul>

                        <a href="{{ route('master.students.template') }}"
                            class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Download Template') }}
                        </a>
                    </div>

                    <form action="{{ route('master.students.processImport') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <x-input-label for="file" :value="__('Choose Import File')" />
                            <input id="file" name="file" type="file" required accept=".csv,.txt,.xlsx,.xls" class="mt-1 block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-indigo-50 file:text-indigo-700
                                hover:file:bg-indigo-100
                            " />
                            <x-input-error class="mt-2" :messages="$errors->get('file')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Import Students') }}</x-primary-button>
                            <a href="{{ route('master.students.index') }}"
                                class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
