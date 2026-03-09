<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-slate-50"
             x-data="{
                 sidebarCollapsed: localStorage.getItem('finance_sidebar_collapsed') === 'true',
                 mobileOpen: false,
                 toggleCollapse() {
                     this.sidebarCollapsed = !this.sidebarCollapsed;
                     localStorage.setItem('finance_sidebar_collapsed', this.sidebarCollapsed);
                 },
                 toggleMobile() {
                     this.mobileOpen = !this.mobileOpen;
                 },
                 closeMobile() {
                     this.mobileOpen = false;
                 }
             }"
             x-on:keydown.escape.window="closeMobile()"
        >
            {{-- ===== FINANCE SIDEBAR ===== --}}

            {{-- Mobile backdrop --}}
            <div x-show="mobileOpen"
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="closeMobile()"
                 class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden"
                 x-cloak></div>

            {{-- Sidebar --}}
            <aside
                :class="{
                    'translate-x-0': mobileOpen,
                    '-translate-x-full lg:translate-x-0': !mobileOpen,
                    'lg:w-[4.5rem]': sidebarCollapsed,
                    'lg:w-[16.5rem]': !sidebarCollapsed
                }"
                class="fixed inset-y-0 left-0 z-50 lg:z-0 flex w-[16.5rem] flex-col bg-white border-r border-slate-200/80 transition-all duration-300 ease-in-out shadow-sm"
            >
                {{-- Logo area --}}
                <div class="flex h-16 shrink-0 items-center border-b border-slate-100 px-5">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-x-3">
                        <x-application-logo class="block h-9 w-auto shrink-0" />
                        <span class="text-base font-semibold text-slate-800 tracking-tight truncate"
                              x-show="!sidebarCollapsed" x-cloak>
                            {{ config('app.name', 'SAKUMI') }}
                        </span>
                    </a>
                </div>

                {{-- Navigation --}}
                <nav class="flex-1 overflow-y-auto px-3 py-5 space-y-1.5 scrollbar-hide">
                    {{-- Section label --}}
                    <div class="px-3 mb-3" x-show="!sidebarCollapsed" x-cloak>
                        <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-slate-400">{{ __('app.nav.finance') }}</p>
                    </div>
                    <div x-show="sidebarCollapsed" x-cloak class="mx-1 mb-3 border-t border-slate-200"></div>

                    {{-- Dashboard --}}
                    @php
                        $isDashboard = request()->routeIs('dashboard');
                    @endphp
                    <a href="{{ route('dashboard') }}"
                       :title="sidebarCollapsed ? '{{ __('app.nav.dashboard') }}' : ''"
                       class="group flex items-center gap-x-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150
                              {{ $isDashboard
                                  ? 'bg-indigo-50 text-indigo-700 shadow-sm shadow-indigo-100/50'
                                  : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span class="shrink-0 w-5 h-5 {{ $isDashboard ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                        </span>
                        <span class="truncate" x-show="!sidebarCollapsed" x-cloak>{{ __('app.nav.dashboard') }}</span>
                    </a>

                    {{-- Students --}}
                    @if(auth()->user()->hasAnyRole(['super_admin', 'admin_tu_mi', 'admin_tu_ra', 'admin_tu_dta', 'operator_tu']) && auth()->user()->can('master.students.view'))
                        @php $isStudents = request()->routeIs('master.students.*'); @endphp
                        <a href="{{ route('master.students.index') }}"
                           :title="sidebarCollapsed ? '{{ __('app.nav.students') }}' : ''"
                           class="group flex items-center gap-x-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150
                                  {{ $isStudents
                                      ? 'bg-indigo-50 text-indigo-700 shadow-sm shadow-indigo-100/50'
                                      : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <span class="shrink-0 w-5 h-5 {{ $isStudents ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                                </svg>
                            </span>
                            <span class="truncate" x-show="!sidebarCollapsed" x-cloak>{{ __('app.nav.students') }}</span>
                        </a>
                    @endif

                    {{-- Invoices --}}
                    @can('invoices.view')
                        @php $isInvoices = request()->routeIs('invoices.*'); @endphp
                        <a href="{{ route('invoices.index') }}"
                           :title="sidebarCollapsed ? '{{ __('app.nav.invoices') }}' : ''"
                           class="group flex items-center gap-x-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150
                                  {{ $isInvoices
                                      ? 'bg-indigo-50 text-indigo-700 shadow-sm shadow-indigo-100/50'
                                      : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <span class="shrink-0 w-5 h-5 {{ $isInvoices ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </span>
                            <span class="truncate" x-show="!sidebarCollapsed" x-cloak>{{ __('app.nav.invoices') }}</span>
                        </a>
                    @endcan

                    {{-- Payments (Settlements) --}}
                    @can('settlements.view')
                        @php $isSettlements = request()->routeIs('settlements.*'); @endphp
                        <a href="{{ route('settlements.index') }}"
                           :title="sidebarCollapsed ? '{{ __('app.nav.settlements') }}' : ''"
                           class="group flex items-center gap-x-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150
                                  {{ $isSettlements
                                      ? 'bg-indigo-50 text-indigo-700 shadow-sm shadow-indigo-100/50'
                                      : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <span class="shrink-0 w-5 h-5 {{ $isSettlements ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                </svg>
                            </span>
                            <span class="truncate" x-show="!sidebarCollapsed" x-cloak>{{ __('app.nav.settlements') }}</span>
                        </a>
                    @endcan

                    {{-- Expenses --}}
                    @can('expenses.view')
                        @php $isExpenses = request()->routeIs('expenses.*'); @endphp
                        <a href="{{ route('expenses.index') }}"
                           :title="sidebarCollapsed ? '{{ __('app.nav.expenses') }}' : ''"
                           class="group flex items-center gap-x-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150
                                  {{ $isExpenses
                                      ? 'bg-indigo-50 text-indigo-700 shadow-sm shadow-indigo-100/50'
                                      : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <span class="shrink-0 w-5 h-5 {{ $isExpenses ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                            </span>
                            <span class="truncate" x-show="!sidebarCollapsed" x-cloak>{{ __('app.nav.expenses') }}</span>
                        </a>
                    @endcan

                    {{-- Divider --}}
                    <div class="mx-1 my-3 border-t border-slate-100"></div>

                    {{-- Section label: Reports --}}
                    <div class="px-3 mb-2" x-show="!sidebarCollapsed" x-cloak>
                        <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-slate-400">{{ __('app.nav.reports') }}</p>
                    </div>
                    <div x-show="sidebarCollapsed" x-cloak class="mx-1 mb-2 border-t border-slate-200"></div>

                    {{-- Reports --}}
                    @if(auth()->user()->canAny(['reports.daily', 'reports.monthly', 'reports.arrears', 'reports.ar-outstanding', 'reports.collection', 'reports.student-statement', 'reports.cash-book']))
                        @can('reports.daily')
                            @php $isDaily = request()->routeIs('reports.daily'); @endphp
                            <a href="{{ route('reports.daily') }}"
                               :title="sidebarCollapsed ? '{{ __('app.nav.daily_report') }}' : ''"
                               class="group flex items-center gap-x-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150
                                      {{ $isDaily
                                          ? 'bg-indigo-50 text-indigo-700 shadow-sm shadow-indigo-100/50'
                                          : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                <span class="shrink-0 w-5 h-5 {{ $isDaily ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                                    </svg>
                                </span>
                                <span class="truncate" x-show="!sidebarCollapsed" x-cloak>{{ __('app.nav.daily_report') }}</span>
                            </a>
                        @endcan

                        @can('reports.monthly')
                            @php $isMonthly = request()->routeIs('reports.monthly'); @endphp
                            <a href="{{ route('reports.monthly') }}"
                               :title="sidebarCollapsed ? '{{ __('app.nav.monthly_report') }}' : ''"
                               class="group flex items-center gap-x-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150
                                      {{ $isMonthly
                                          ? 'bg-indigo-50 text-indigo-700 shadow-sm shadow-indigo-100/50'
                                          : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                <span class="shrink-0 w-5 h-5 {{ $isMonthly ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                    </svg>
                                </span>
                                <span class="truncate" x-show="!sidebarCollapsed" x-cloak>{{ __('app.nav.monthly_report') }}</span>
                            </a>
                        @endcan

                        @can('reports.arrears')
                            @php $isArrears = request()->routeIs('reports.arrears'); @endphp
                            <a href="{{ route('reports.arrears') }}"
                               :title="sidebarCollapsed ? '{{ __('app.nav.arrears_report') }}' : ''"
                               class="group flex items-center gap-x-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150
                                      {{ $isArrears
                                          ? 'bg-indigo-50 text-indigo-700 shadow-sm shadow-indigo-100/50'
                                          : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                <span class="shrink-0 w-5 h-5 {{ $isArrears ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </span>
                                <span class="truncate" x-show="!sidebarCollapsed" x-cloak>{{ __('app.nav.arrears_report') }}</span>
                            </a>
                        @endcan

                        @can('reports.collection')
                            @php $isCollection = request()->routeIs('reports.collection'); @endphp
                            <a href="{{ route('reports.collection') }}"
                               :title="sidebarCollapsed ? '{{ __('app.nav.collection_report') }}' : ''"
                               class="group flex items-center gap-x-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150
                                      {{ $isCollection
                                          ? 'bg-indigo-50 text-indigo-700 shadow-sm shadow-indigo-100/50'
                                          : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                <span class="shrink-0 w-5 h-5 {{ $isCollection ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                                    </svg>
                                </span>
                                <span class="truncate" x-show="!sidebarCollapsed" x-cloak>{{ __('app.nav.collection_report') }}</span>
                            </a>
                        @endcan
                    @endif
                </nav>

                {{-- Back to main app link + Collapse toggle --}}
                <div class="shrink-0 border-t border-slate-200 p-3 space-y-1">
                    {{-- Back to main app --}}
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors duration-150">
                        <span class="shrink-0 w-5 h-5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                            </svg>
                        </span>
                        <span class="truncate" x-show="!sidebarCollapsed" x-cloak>{{ __('Back to App') }}</span>
                    </a>

                    {{-- Collapse toggle (desktop only) --}}
                    <div class="hidden lg:block">
                        <button @click="toggleCollapse()"
                                class="flex w-full items-center justify-center rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors duration-150">
                            <svg x-show="!sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
                            </svg>
                            <svg x-show="sidebarCollapsed" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5m-6-15l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                    </div>
                </div>
            </aside>

            {{-- ===== MAIN CONTENT ===== --}}
            <div class="transition-all duration-300 ease-in-out lg:pl-[16.5rem]"
                 :class="{ 'lg:pl-[4.5rem]': sidebarCollapsed, 'lg:pl-[16.5rem]': !sidebarCollapsed }">

                {{-- Top bar --}}
                <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200/80 bg-white/80 backdrop-blur-md px-4 sm:px-6 lg:px-8">
                    {{-- Left: mobile hamburger + page context --}}
                    <div class="flex items-center gap-x-3">
                        {{-- Mobile hamburger --}}
                        <button @click="toggleMobile()" class="lg:hidden -ml-1 rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors duration-150">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>

                        {{-- Logo (mobile only) --}}
                        <a href="{{ route('dashboard') }}" class="lg:hidden flex items-center">
                            <x-application-logo class="block h-8 w-auto" />
                        </a>

                        {{-- Page title slot --}}
                        @isset($headerTitle)
                            <div class="hidden lg:block">
                                <h1 class="text-lg font-semibold text-slate-800 tracking-tight">{{ $headerTitle }}</h1>
                            </div>
                        @endisset
                    </div>

                    {{-- Right: actions --}}
                    <div class="flex items-center gap-3">
                        {{-- Language Toggle --}}
                        <form method="POST" action="{{ route('locale.switch') }}">
                            @csrf
                            <input type="hidden" name="locale" value="{{ app()->getLocale() === 'id' ? 'en' : 'id' }}">
                            <button type="submit"
                                class="inline-flex items-center px-2.5 py-1.5 border border-slate-200 text-xs font-semibold rounded-lg text-slate-600 bg-white hover:bg-slate-50 focus:outline-none transition ease-in-out duration-150"
                                title="{{ app()->getLocale() === 'id' ? 'Switch to English' : 'Ganti ke Bahasa Indonesia' }}">
                                {{ app()->getLocale() === 'id' ? 'EN' : 'ID' }}
                            </button>
                        </form>

                        {{-- Unit Indicator / Switcher --}}
                        @if(isset($currentUnit))
                            @if(isset($switchableUnits) && $switchableUnits->count() > 1)
                                <x-dropdown align="right" width="48">
                                    <x-slot name="trigger">
                                        <button class="inline-flex items-center px-3 py-1.5 border border-indigo-200 text-xs font-semibold rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none transition ease-in-out duration-150 whitespace-nowrap">
                                            <div>{{ $currentUnit->code }}</div>
                                            <div class="ms-1">
                                                <svg class="fill-current h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
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
                                <span class="inline-flex items-center px-3 py-1.5 border border-slate-200 text-xs font-semibold rounded-lg text-slate-600 bg-white">
                                    {{ $currentUnit->code }}
                                </span>
                            @endif
                        @endif

                        {{-- User Profile Dropdown --}}
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-x-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-800 focus:outline-none transition ease-in-out duration-150 whitespace-nowrap">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </span>
                                    <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                                    <svg class="fill-current h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
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
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </header>

                {{-- Page heading --}}
                @isset($header)
                    <header class="border-b border-slate-100 bg-white">
                        <div class="px-4 sm:px-6 lg:px-8 py-5">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                {{-- Flash messages --}}
                @if (session('success') || session('status') || $errors->any() || session('temporary_password'))
                    <div class="px-4 sm:px-6 lg:px-8 mt-4 space-y-2">
                        @if (session('success'))
                            <div class="flex items-center gap-x-3 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                                <svg class="h-5 w-5 shrink-0 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ session('success') }}</span>
                            </div>
                        @endif

                        @if (session('status'))
                            <div class="flex items-center gap-x-3 rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">
                                <svg class="h-5 w-5 shrink-0 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>
                                <span>{{ session('status') }}</span>
                            </div>
                        @endif

                        @if (session('temporary_password'))
                            <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
                                <p class="font-semibold">Temporary Password</p>
                                <p class="mt-1 font-mono">{{ session('temporary_password') }}</p>
                                <p class="mt-1 text-xs">Share securely and require immediate password change.</p>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="flex items-start gap-x-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                                <svg class="h-5 w-5 shrink-0 text-red-500 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                                <ul class="list-disc ms-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Main content --}}
                <main class="px-4 sm:px-6 lg:px-8 py-6">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
