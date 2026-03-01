{{-- Top bar --}}
<header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6">
    {{-- Left: mobile hamburger + logo --}}
    <div class="flex items-center gap-x-3">
        {{-- Mobile hamburger --}}
        <button @click="toggleMobile()" class="lg:hidden -ml-1 rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors duration-150">
            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        {{-- Logo (mobile only, hidden on desktop since sidebar has logo) --}}
        <a href="{{ route('dashboard') }}" class="lg:hidden flex items-center">
            <x-application-logo class="block h-8 w-auto" />
        </a>
    </div>

    {{-- Right: locale + unit + user --}}
    <div class="flex items-center gap-3">
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
                            class="inline-flex items-center px-3 py-1.5 border border-indigo-200 text-xs font-semibold rounded-full text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none transition ease-in-out duration-150 whitespace-nowrap">
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
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 whitespace-nowrap">
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
</header>
