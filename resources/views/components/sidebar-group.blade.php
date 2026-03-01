@props(['label', 'active' => false])

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }">
    {{-- Group header --}}
    <button
        x-show="!sidebarCollapsed"
        x-cloak
        @click="open = !open"
        class="flex w-full items-center justify-between px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 hover:text-gray-700 transition-colors duration-150"
    >
        <span>{{ $label }}</span>
        <svg class="h-4 w-4 shrink-0 transition-transform duration-200" :class="{ 'rotate-90': open }"
             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
        </svg>
    </button>

    {{-- Collapsed divider (icon-only mode) --}}
    <div x-show="sidebarCollapsed" x-cloak class="mx-2 my-2 border-t border-gray-200"></div>

    {{-- Children --}}
    <div x-show="open || sidebarCollapsed"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         x-cloak>
        {{ $slot }}
    </div>
</div>
