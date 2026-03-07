<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Generate Invoices') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-500 mb-6">
                        {{ __('app.form.batch_generate_desc') }}
                    </p>

                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('generation_errors'))
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4">
                            <p class="font-bold mb-2">{{ __('app.form.generation_errors') }}</p>
                            <ul class="list-disc list-inside text-sm">
                                @foreach(session('generation_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('invoices.runGeneration') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <x-input-label for="period_type" :value="__('Period Type')" />
                                <select id="period_type" name="period_type"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"
                                    required onchange="updatePeriodField()">
                                    <option value="monthly" {{ old('period_type', 'monthly') === 'monthly' ? 'selected' : '' }}>{{ __('app.status.monthly') }}</option>
                                    <option value="annual" {{ old('period_type') === 'annual' ? 'selected' : '' }}>{{ __('app.status.annual') }}</option>
                                </select>
                            </div>

                            <div id="period-monthly">
                                <x-input-label for="period_month" :value="__('Period (Month)')" />
                                <x-text-input id="period_month" class="block mt-1 w-full" type="month"
                                    :value="old('period_identifier', now()->format('Y-m'))" />
                            </div>

                            <div id="period-annual" class="hidden">
                                <x-input-label for="period_year" :value="__('Academic Year')" />
                                <x-text-input id="period_year" class="block mt-1 w-full" type="text" placeholder="AY2026"
                                    :value="old('period_identifier', 'AY' . now()->year)" />
                            </div>

                            <input type="hidden" name="period_identifier" id="period_identifier_hidden" value="{{ old('period_identifier', now()->format('Y-m')) }}">

                            <div>
                                <x-input-label for="due_date" :value="__('Due Date')" />
                                <x-text-input id="due_date" class="block mt-1 w-full" type="date" name="due_date"
                                    :value="old('due_date', now()->addDays(30)->format('Y-m-d'))" required />
                            </div>

                            <div>
                                <x-input-label for="class_id" :value="__('Class (Optional)')" />
                                <select id="class_id" name="class_id"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                    <option value="">{{ __('app.filter.all_classes') }}</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="category_id" :value="__('Category (Optional)')" />
                                <select id="category_id" name="category_id"
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                    <option value="">{{ __('app.placeholder.all_categories') }}</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4">
                            <a href="{{ route('invoices.index') }}"
                                class="px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                                {{ __('app.button.back') }}
                            </a>
                            <x-primary-button onclick="return confirm(@json(__('app.form.confirm_generate')))">
                                {{ __('Run Generation') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updatePeriodField() {
            const type = document.getElementById('period_type').value;
            const monthDiv = document.getElementById('period-monthly');
            const annualDiv = document.getElementById('period-annual');
            const hidden = document.getElementById('period_identifier_hidden');

            if (type === 'monthly') {
                monthDiv.classList.remove('hidden');
                annualDiv.classList.add('hidden');
                hidden.value = document.getElementById('period_month').value;
            } else {
                monthDiv.classList.add('hidden');
                annualDiv.classList.remove('hidden');
                hidden.value = document.getElementById('period_year').value;
            }
        }

        document.getElementById('period_month').addEventListener('change', function () {
            document.getElementById('period_identifier_hidden').value = this.value;
        });

        document.getElementById('period_year').addEventListener('input', function () {
            document.getElementById('period_identifier_hidden').value = this.value;
        });

        // Initialize
        updatePeriodField();
    </script>
</x-app-layout>
