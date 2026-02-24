<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    @php
        $masterDataCoreRoles = ['super_admin', 'admin_tu_mi', 'admin_tu_ra', 'admin_tu_dta', 'operator_tu'];
        $masterDataFinanceRoles = ['super_admin', 'admin_tu_mi', 'admin_tu_ra', 'admin_tu_dta', 'bendahara'];
    @endphp

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-20 w-auto" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @can('transactions.view')
                        <x-nav-link :href="route('transactions.index')" :active="request()->routeIs('transactions.*')">
                            {{ __('Transactions') }}
                        </x-nav-link>
                    @endcan

                    @can('invoices.view')
                        <x-nav-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')">
                            {{ __('Invoices') }}
                        </x-nav-link>
                    @endcan

                    @can('settlements.view')
                        <x-nav-link :href="route('settlements.index')" :active="request()->routeIs('settlements.*')">
                            {{ __('Settlements') }}
                        </x-nav-link>
                    @endcan

                    @can('expenses.view')
                        <x-nav-link :href="route('expenses.index')" :active="request()->routeIs('expenses.*')">
                            {{ __('Expenses') }}
                        </x-nav-link>
                    @endcan

                    @can('bank-reconciliation.view')
                        <x-nav-link :href="route('bank-reconciliation.index')" :active="request()->routeIs('bank-reconciliation.*')">
                            {{ __('Bank Recon') }}
                        </x-nav-link>
                    @endcan

                    {{-- Master Data Dropdown --}}
                    @if(
                            (auth()->user()->hasAnyRole($masterDataCoreRoles) && auth()->user()->canAny(['master.students.view', 'master.classes.view', 'master.categories.view']))
                            || (auth()->user()->hasAnyRole($masterDataFinanceRoles) && auth()->user()->canAny(['master.fee-types.view', 'master.fee-matrix.view']))
                        )
                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                        <div>{{ __('app.nav.master_data') }}</div>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @if(auth()->user()->hasAnyRole($masterDataCoreRoles) && auth()->user()->can('master.students.view'))
                                        <x-dropdown-link :href="route('master.students.index')">
                                            {{ __('Students') }}
                                        </x-dropdown-link>
                                    @endif
                                    @if(auth()->user()->hasAnyRole($masterDataCoreRoles) && auth()->user()->can('master.classes.view'))
                                        <x-dropdown-link :href="route('master.classes.index')">
                                            {{ __('Classes') }}
                                        </x-dropdown-link>
                                    @endif
                                    @if(auth()->user()->hasAnyRole($masterDataCoreRoles) && auth()->user()->can('master.categories.view'))
                                        <x-dropdown-link :href="route('master.categories.index')">
                                            {{ __('Categories') }}
                                        </x-dropdown-link>
                                    @endif
                                    @if(auth()->user()->hasAnyRole($masterDataFinanceRoles) && auth()->user()->can('master.fee-types.view'))
                                        <x-dropdown-link :href="route('master.fee-types.index')">
                                            {{ __('Fee Types') }}
                                        </x-dropdown-link>
                                    @endif
                                    @if(auth()->user()->hasAnyRole($masterDataFinanceRoles) && auth()->user()->can('master.fee-matrix.view'))
                                        <x-dropdown-link :href="route('master.fee-matrix.index')">
                                            {{ __('Fee Matrix') }}
                                        </x-dropdown-link>
                                    @endif
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endif

                    {{-- Reports Dropdown --}}
                    @if(auth()->user()->canAny(['reports.daily', 'reports.monthly', 'reports.arrears', 'reports.ar-outstanding', 'reports.collection', 'reports.student-statement', 'reports.cash-book']))
                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                        <div>{{ __('app.nav.reports') }}</div>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @can('reports.daily')
                                        <x-dropdown-link :href="route('reports.daily')">
                                            {{ __('Daily Report') }}
                                        </x-dropdown-link>
                                    @endcan
                                    @can('reports.monthly')
                                        <x-dropdown-link :href="route('reports.monthly')">
                                            {{ __('Monthly Report') }}
                                        </x-dropdown-link>
                                    @endcan
                                    @can('reports.arrears')
                                        <x-dropdown-link :href="route('reports.arrears')">
                                            {{ __('Arrears Report') }}
                                        </x-dropdown-link>
                                    @endcan
                                    @can('reports.ar-outstanding')
                                        <x-dropdown-link :href="route('reports.ar-outstanding')">
                                            {{ __('AR Outstanding') }}
                                        </x-dropdown-link>
                                    @endcan
                                    @can('reports.collection')
                                        <x-dropdown-link :href="route('reports.collection')">
                                            {{ __('Collection Report') }}
                                        </x-dropdown-link>
                                    @endcan
                                    @can('reports.student-statement')
                                        <x-dropdown-link :href="route('reports.student-statement')">
                                            {{ __('Student Statement') }}
                                        </x-dropdown-link>
                                    @endcan
                                    @can('reports.cash-book')
                                        <x-dropdown-link :href="route('reports.cash-book')">
                                            {{ __('Cash Book') }}
                                        </x-dropdown-link>
                                    @endcan
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endif

                    @can('users.view')
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                            {{ __('app.nav.users') }}
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <!-- Locale + Unit Switcher + Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:gap-3">
                {{-- Language Toggle --}}
                <form method="POST" action="{{ route('locale.switch') }}">
                    @csrf
                    <input type="hidden" name="locale" value="{{ app()->getLocale() === 'id' ? 'en' : 'id' }}">
                    <button type="submit"
                        class="inline-flex items-center px-2 py-1 border border-gray-200 text-xs font-semibold rounded-full text-gray-600 bg-gray-50 hover:bg-gray-100 focus:outline-none transition ease-in-out duration-150"
                        title="{{ app()->getLocale() === 'id' ? 'Switch to English' : 'Ganti ke Bahasa Indonesia' }}">
                        {{ app()->getLocale() === 'id' ? 'EN' : 'ID' }}
                    </button>
                </form>

                {{-- Unit Indicator / Switcher --}}
                @if(isset($currentUnit))
                    @if(isset($switchableUnits) && $switchableUnits->count() > 1)
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button
                                    class="inline-flex items-center px-3 py-1.5 border border-indigo-200 text-xs font-semibold rounded-full text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none transition ease-in-out duration-150">
                                    <div>{{ $currentUnit->code }}</div>
                                    <div class="ms-1">
                                        <svg class="fill-current h-3 w-3" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                @foreach($switchableUnits as $unit)
                                    <form method="POST" action="{{ route('unit.switch') }}">
                                        @csrf
                                        <input type="hidden" name="unit_id" value="{{ $unit->id }}">
                                        <button type="submit"
                                            class="block w-full px-4 py-2 text-start text-sm leading-5 {{ $unit->id === $currentUnit->id ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-700 hover:bg-gray-100' }} focus:outline-none transition duration-150 ease-in-out">
                                            {{ $unit->code }} &mdash; {{ $unit->name }}
                                        </button>
                                    </form>
                                @endforeach
                            </x-slot>
                        </x-dropdown>
                    @else
                        <span
                            class="inline-flex items-center px-3 py-1.5 border border-gray-200 text-xs font-semibold rounded-full text-gray-600 bg-gray-50">
                            {{ $currentUnit->code }}
                        </span>
                    @endif
                @endif

                {{-- User Profile Dropdown --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        @can('settings.view')
                            <x-dropdown-link :href="route('settings.edit')">
                                {{ __('Settings') }}
                            </x-dropdown-link>
                        @endcan

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @can('transactions.view')
                <x-responsive-nav-link :href="route('transactions.index')" :active="request()->routeIs('transactions.*')">
                    {{ __('Transactions') }}
                </x-responsive-nav-link>
            @endcan

            @can('invoices.view')
                <x-responsive-nav-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')">
                    {{ __('Invoices') }}
                </x-responsive-nav-link>
            @endcan

            @can('settlements.view')
                <x-responsive-nav-link :href="route('settlements.index')" :active="request()->routeIs('settlements.*')">
                    {{ __('Settlements') }}
                </x-responsive-nav-link>
            @endcan

            @can('expenses.view')
                <x-responsive-nav-link :href="route('expenses.index')" :active="request()->routeIs('expenses.*')">
                    {{ __('Expenses') }}
                </x-responsive-nav-link>
            @endcan

            @can('bank-reconciliation.view')
                <x-responsive-nav-link :href="route('bank-reconciliation.index')" :active="request()->routeIs('bank-reconciliation.*')">
                    {{ __('Bank Recon') }}
                </x-responsive-nav-link>
            @endcan

            {{-- Master Data Group --}}
            @if(
                    (auth()->user()->hasAnyRole($masterDataCoreRoles) && auth()->user()->canAny(['master.students.view', 'master.classes.view', 'master.categories.view']))
                    || (auth()->user()->hasAnyRole($masterDataFinanceRoles) && auth()->user()->canAny(['master.fee-types.view', 'master.fee-matrix.view']))
                )
                <div class="pt-2 pb-1 border-t border-gray-200">
                    <div class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        {{ __('app.nav.master_data') }}
                    </div>
                </div>
                @if(auth()->user()->hasAnyRole($masterDataCoreRoles) && auth()->user()->can('master.students.view'))
                    <x-responsive-nav-link :href="route('master.students.index')"
                        :active="request()->routeIs('master.students.*')">
                        {{ __('Students') }}
                    </x-responsive-nav-link>
                @endif
                @if(auth()->user()->hasAnyRole($masterDataCoreRoles) && auth()->user()->can('master.classes.view'))
                    <x-responsive-nav-link :href="route('master.classes.index')"
                        :active="request()->routeIs('master.classes.*')">
                        {{ __('Classes') }}
                    </x-responsive-nav-link>
                @endif
                @if(auth()->user()->hasAnyRole($masterDataCoreRoles) && auth()->user()->can('master.categories.view'))
                    <x-responsive-nav-link :href="route('master.categories.index')"
                        :active="request()->routeIs('master.categories.*')">
                        {{ __('Categories') }}
                    </x-responsive-nav-link>
                @endif
                @if(auth()->user()->hasAnyRole($masterDataFinanceRoles) && auth()->user()->can('master.fee-types.view'))
                    <x-responsive-nav-link :href="route('master.fee-types.index')"
                        :active="request()->routeIs('master.fee-types.*')">
                        {{ __('Fee Types') }}
                    </x-responsive-nav-link>
                @endif
                @if(auth()->user()->hasAnyRole($masterDataFinanceRoles) && auth()->user()->can('master.fee-matrix.view'))
                    <x-responsive-nav-link :href="route('master.fee-matrix.index')"
                        :active="request()->routeIs('master.fee-matrix.*')">
                        {{ __('Fee Matrix') }}
                    </x-responsive-nav-link>
                @endif
            @endif

            {{-- Reports Group --}}
            @if(auth()->user()->canAny(['reports.daily', 'reports.monthly', 'reports.arrears', 'reports.ar-outstanding', 'reports.collection', 'reports.student-statement', 'reports.cash-book']))
                <div class="pt-2 pb-1 border-t border-gray-200">
                    <div class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        {{ __('app.nav.reports') }}
                    </div>
                </div>
                @can('reports.daily')
                    <x-responsive-nav-link :href="route('reports.daily')" :active="request()->routeIs('reports.daily')">
                        {{ __('Daily Report') }}
                    </x-responsive-nav-link>
                @endcan
                @can('reports.monthly')
                    <x-responsive-nav-link :href="route('reports.monthly')" :active="request()->routeIs('reports.monthly')">
                        {{ __('Monthly Report') }}
                    </x-responsive-nav-link>
                @endcan
                @can('reports.arrears')
                    <x-responsive-nav-link :href="route('reports.arrears')" :active="request()->routeIs('reports.arrears')">
                        {{ __('Arrears Report') }}
                    </x-responsive-nav-link>
                @endcan
                @can('reports.ar-outstanding')
                    <x-responsive-nav-link :href="route('reports.ar-outstanding')" :active="request()->routeIs('reports.ar-outstanding')">
                        {{ __('AR Outstanding') }}
                    </x-responsive-nav-link>
                @endcan
                @can('reports.collection')
                    <x-responsive-nav-link :href="route('reports.collection')" :active="request()->routeIs('reports.collection')">
                        {{ __('Collection Report') }}
                    </x-responsive-nav-link>
                @endcan
                @can('reports.student-statement')
                    <x-responsive-nav-link :href="route('reports.student-statement')" :active="request()->routeIs('reports.student-statement')">
                        {{ __('Student Statement') }}
                    </x-responsive-nav-link>
                @endcan
                @can('reports.cash-book')
                    <x-responsive-nav-link :href="route('reports.cash-book')" :active="request()->routeIs('reports.cash-book')">
                        {{ __('Cash Book') }}
                    </x-responsive-nav-link>
                @endcan
            @endif

            @can('users.view')
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    {{ __('app.nav.users') }}
                </x-responsive-nav-link>
            @endcan
        </div>

        {{-- Responsive Language Toggle --}}
        <div class="pt-2 pb-1 border-t border-gray-200">
            <div class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('app.nav.language') }}
            </div>
        </div>
        <form method="POST" action="{{ route('locale.switch') }}" class="px-4 py-2">
            @csrf
            <input type="hidden" name="locale" value="{{ app()->getLocale() === 'id' ? 'en' : 'id' }}">
            <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                {{ app()->getLocale() === 'id' ? 'Switch to English' : 'Ganti ke Bahasa Indonesia' }}
            </button>
        </form>

        {{-- Responsive Unit Switcher --}}
        @if(isset($currentUnit))
            <div class="pt-2 pb-1 border-t border-gray-200">
                <div class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit:
                    {{ $currentUnit->code }}
                </div>
            </div>
            @if(isset($switchableUnits) && $switchableUnits->count() > 1)
                @foreach($switchableUnits as $unit)
                    <form method="POST" action="{{ route('unit.switch') }}">
                        @csrf
                        <input type="hidden" name="unit_id" value="{{ $unit->id }}">
                        <button type="submit"
                            class="block w-full ps-4 pe-4 py-2 text-start text-base font-medium {{ $unit->id === $currentUnit->id ? 'text-indigo-700 bg-indigo-50 border-l-4 border-indigo-400' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-50' }} focus:outline-none transition duration-150 ease-in-out">
                            {{ $unit->code }} &mdash; {{ $unit->name }}
                        </button>
                    </form>
                @endforeach
            @endif
        @endif

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>
                @can('settings.view')
                    <x-responsive-nav-link :href="route('settings.edit')">
                        {{ __('Settings') }}
                    </x-responsive-nav-link>
                @endcan

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
