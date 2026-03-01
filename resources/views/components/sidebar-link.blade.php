@props(['href', 'active' => false])

@php
    $activeClasses = 'border-l-4 border-indigo-400 bg-indigo-50 text-indigo-700';
    $inactiveClasses = 'border-l-4 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-800';
    $classes = $active ? $activeClasses : $inactiveClasses;
@endphp

<a href="{{ $href }}"
   :title="sidebarCollapsed ? $el.querySelector('.sidebar-label')?.textContent.trim() : ''"
   class="{{ $classes }} flex items-center gap-x-3 px-3 py-2 text-sm font-medium transition-colors duration-150">
    {{-- Icon slot --}}
    <span class="shrink-0 w-5 h-5">
        {{ $icon }}
    </span>
    {{-- Label --}}
    <span class="sidebar-label truncate" x-show="!sidebarCollapsed" x-cloak>
        {{ $slot }}
    </span>
</a>
