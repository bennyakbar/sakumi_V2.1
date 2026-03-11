<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold tracking-tight text-slate-800">{{ __('Expense Management') }}</h2>
    </x-slot>

    @php
        $pageItems     = $entries->getCollection();
        $totalAmount   = $pageItems->sum('amount');
        $countPosted   = $pageItems->whereIn('status', ['posted', 'approved'])->count();
        $countDraft    = $pageItems->where('status', 'draft')->count();
        $postedAmount  = $pageItems->whereIn('status', ['posted', 'approved'])->sum('amount');
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Action Bar --}}
            <x-finance.action-bar :title="__('Expense Entries')" :subtitle="__('app.label.showing') . ' ' . $entries->firstItem() . '-' . $entries->lastItem() . ' / ' . $entries->total()">
                <a href="{{ route('expenses.budget-report') }}"
                   class="inline-flex items-center gap-x-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-300 hover:bg-slate-50 transition-all duration-150">
                    <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    {{ __('Budget vs Realization') }}
                </a>
            </x-finance.action-bar>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-finance.summary :title="__('Total Expenses')" :value="formatRupiah($totalAmount)" color="indigo">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </x-slot>
                </x-finance.summary>

                <x-finance.summary :title="__('Posted')" :value="formatRupiah($postedAmount)" color="emerald">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot>
                </x-finance.summary>

                <x-finance.summary :title="__('Approved')" :value="$countPosted" color="blue">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75" />
                        </svg>
                    </x-slot>
                </x-finance.summary>

                <x-finance.summary :title="__('Drafts')" :value="$countDraft" color="amber">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>
                    </x-slot>
                </x-finance.summary>
            </div>

            {{-- Budget Warning --}}
            @if(session('budget_warning'))
                @php $bw = session('budget_warning'); @endphp
                <div class="rounded-lg border border-amber-300 bg-amber-50 p-4">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-amber-500 mt-0.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-amber-800">{{ __('Budget Exceeded') }}</h3>
                            <p class="mt-1 text-sm text-amber-700">
                                {{ __('Budget for :subcategory will be exceeded by :amount (:pct% of budget).', [
                                    'subcategory' => $bw['subcategory'],
                                    'amount' => formatRupiah($bw['exceeds_by']),
                                    'pct' => $bw['percentage'],
                                ]) }}
                            </p>
                            <div class="mt-2 grid grid-cols-3 gap-3 text-xs text-amber-700">
                                <div>{{ __('Budget') }}: <span class="font-semibold">{{ formatRupiah($bw['budget']) }}</span></div>
                                <div>{{ __('Spent') }}: <span class="font-semibold">{{ formatRupiah($bw['spent']) }}</span></div>
                                <div>{{ __('Remaining') }}: <span class="font-semibold">{{ formatRupiah($bw['remaining']) }}</span></div>
                            </div>
                            <p class="mt-2 text-xs text-amber-600">{{ __('Check the box below and resubmit to confirm.') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Create Expense Form --}}
            <x-finance.card :title="__('Create Expense Draft')" :subtitle="__('Enter a new expense entry for approval')">
                <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    @csrf
                    <div class="md:col-span-2">
                        <label for="fee_type_id" class="block text-xs font-medium text-slate-500 mb-1">{{ __('Expense Fee Type') }}</label>
                        <select id="fee_type_id" name="fee_type_id"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach($expenseFeeTypes as $type)
                                <option value="{{ $type->id }}" {{ old('fee_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }} | {{ $type->expenseFeeSubcategory?->category?->name ?? '-' }} / {{ $type->expenseFeeSubcategory?->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('fee_type_id')" class="mt-1" />
                    </div>
                    <div>
                        <label for="entry_date" class="block text-xs font-medium text-slate-500 mb-1">{{ __('app.label.date') }}</label>
                        <input type="date" id="entry_date" name="entry_date"
                               value="{{ old('entry_date', now()->toDateString()) }}"
                               class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                        <x-input-error :messages="$errors->get('entry_date')" class="mt-1" />
                    </div>
                    <div>
                        <label for="payment_method" class="block text-xs font-medium text-slate-500 mb-1">{{ __('app.label.method') }}</label>
                        <select id="payment_method" name="payment_method"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                            <option value="qris" {{ old('payment_method') === 'qris' ? 'selected' : '' }}>QRIS</option>
                        </select>
                        <x-input-error :messages="$errors->get('payment_method')" class="mt-1" />
                    </div>
                    <div>
                        <label for="vendor_name" class="block text-xs font-medium text-slate-500 mb-1">{{ __('Vendor') }}</label>
                        <input type="text" id="vendor_name" name="vendor_name"
                               value="{{ old('vendor_name') }}"
                               class="block w-full rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500" />
                        <x-input-error :messages="$errors->get('vendor_name')" class="mt-1" />
                    </div>
                    <div>
                        <label for="amount" class="block text-xs font-medium text-slate-500 mb-1">{{ __('app.label.amount') }}</label>
                        <input type="number" id="amount" name="amount" min="1" step="1"
                               value="{{ old('amount') }}"
                               class="block w-full rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500" required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                    </div>
                    <div class="md:col-span-3">
                        <label for="description" class="block text-xs font-medium text-slate-500 mb-1">{{ __('app.label.description') }}</label>
                        <textarea id="description" name="description" rows="2"
                                  class="block w-full rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>
                    <div>
                        <label for="receipt" class="block text-xs font-medium text-slate-500 mb-1">{{ __('Receipt') }}</label>
                        <input type="file" id="receipt" name="receipt" accept=".jpg,.jpeg,.png,.pdf"
                               class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200" />
                        <x-input-error :messages="$errors->get('receipt')" class="mt-1" />
                    </div>
                    <div>
                        <label for="supporting_doc" class="block text-xs font-medium text-slate-500 mb-1">{{ __('Supporting Document') }}</label>
                        <input type="file" id="supporting_doc" name="supporting_doc" accept=".jpg,.jpeg,.png,.pdf"
                               class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200" />
                        <x-input-error :messages="$errors->get('supporting_doc')" class="mt-1" />
                    </div>
                    <div></div>
                    <div class="md:col-span-3 flex items-center gap-3">
                        @if(session('budget_warning'))
                            <label class="inline-flex items-center gap-2 text-sm text-amber-700">
                                <input type="checkbox" name="confirm_over_budget" value="1"
                                       class="rounded border-amber-400 text-amber-600 focus:ring-amber-500" required />
                                {{ __('I confirm this expense exceeds the budget') }}
                            </label>
                        @endif
                        <button type="submit"
                                class="inline-flex items-center gap-x-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-150">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            {{ __('app.button.save') }}
                        </button>
                    </div>
                </form>
            </x-finance.card>

            {{-- Filters --}}
            <x-finance.card :padding="true" noDivider>
                <form method="GET" action="{{ route('expenses.index') }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                        <div>
                            <label for="filter_status" class="block text-xs font-medium text-slate-500 mb-1">{{ __('app.label.status') }}</label>
                            <select name="status" id="filter_status"
                                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('app.filter.all_status') }}</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                            </select>
                        </div>
                        <div>
                            <label for="date_from" class="block text-xs font-medium text-slate-500 mb-1">{{ __('From') }}</label>
                            <input type="date" name="date_from" id="date_from"
                                   value="{{ request('date_from') }}"
                                   class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label for="date_to" class="block text-xs font-medium text-slate-500 mb-1">{{ __('To') }}</label>
                            <input type="date" name="date_to" id="date_to"
                                   value="{{ request('date_to') }}"
                                   class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div class="flex gap-2 lg:col-span-2">
                            <button type="submit"
                                    class="inline-flex items-center justify-center gap-x-1.5 rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 transition-all duration-150">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                                </svg>
                                {{ __('app.button.filter') }}
                            </button>
                            @if(request()->hasAny(['status', 'date_from', 'date_to']))
                                <a href="{{ route('expenses.index') }}"
                                   class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-600 shadow-sm hover:bg-slate-50 transition-all duration-150"
                                   title="{{ __('app.button.reset') }}">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </x-finance.card>

            {{-- Expense Table --}}
            <x-finance.table>
                <x-slot name="head">
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('app.label.date') }}</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Category') }}</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('app.label.description') }}</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('app.label.amount') }}</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('app.label.status') }}</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('app.label.actions') }}</th>
                </x-slot>

                @forelse($entries as $entry)
                    <tr>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <div class="text-sm text-slate-800">{{ $entry->entry_date?->format('d M Y') }}</div>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="text-sm font-medium text-slate-800">{{ $entry->subcategory?->category?->name ?? '-' }}</div>
                            <div class="text-xs text-slate-400">{{ $entry->subcategory?->name ?? '-' }}</div>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="text-sm text-slate-700 max-w-xs truncate" title="{{ $entry->description }}">
                                {{ $entry->description ?: '-' }}
                            </div>
                            @if($entry->vendor_name)
                                <div class="text-xs text-slate-400 mt-0.5">
                                    <span class="inline-flex items-center gap-x-1">
                                        <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                                        </svg>
                                        {{ $entry->vendor_name }}
                                    </span>
                                </div>
                            @endif
                            @if($entry->receipt_path || $entry->supporting_doc_path)
                                <div class="flex items-center gap-2 mt-1">
                                    @if($entry->receipt_path)
                                        <a href="{{ Storage::disk('public')->url($entry->receipt_path) }}" target="_blank"
                                           class="inline-flex items-center gap-x-1 text-xs text-indigo-600 hover:text-indigo-800">
                                            <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                                            </svg>
                                            {{ __('Receipt') }}
                                        </a>
                                    @endif
                                    @if($entry->supporting_doc_path)
                                        <a href="{{ Storage::disk('public')->url($entry->supporting_doc_path) }}" target="_blank"
                                           class="inline-flex items-center gap-x-1 text-xs text-indigo-600 hover:text-indigo-800">
                                            <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                                            </svg>
                                            {{ __('Doc') }}
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-sm font-semibold text-slate-800 text-right">
                            {{ formatRupiah((float) $entry->amount) }}
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            @if(in_array($entry->status, ['posted', 'approved']))
                                <x-finance.badge color="green" dot>{{ strtoupper($entry->status) }}</x-finance.badge>
                            @else
                                <x-finance.badge color="yellow" dot>{{ strtoupper($entry->status) }}</x-finance.badge>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if($entry->status === 'draft' && auth()->user()->can('expenses.approve'))
                                    <form method="POST" action="{{ route('expenses.approve', $entry) }}" onsubmit="return confirm('Approve and post this expense?');">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center gap-x-1.5 rounded-md px-2.5 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 ring-1 ring-inset ring-emerald-600/20 hover:bg-emerald-100 transition-colors duration-150">
                                            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                            {{ __('Approve') }}
                                        </button>
                                    </form>
                                @elseif($entry->postedTransaction)
                                    <a href="{{ route('transactions.show', $entry->postedTransaction) }}"
                                       class="inline-flex items-center rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors duration-150"
                                       title="{{ __('View Transaction') }}">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                    </a>
                                @endif
                                @can('expenses.create')
                                    <a href="{{ route('expenses.duplicate', $entry) }}"
                                       class="inline-flex items-center rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors duration-150"
                                       title="{{ __('Duplicate as new draft') }}">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                                        </svg>
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="h-10 w-10 text-slate-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                                <p class="text-sm text-slate-400">{{ __('app.empty.entries') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse

                <x-slot name="pagination">
                    {{ $entries->links() }}
                </x-slot>
            </x-finance.table>

        </div>
    </div>
</x-app-layout>
