@props([
    'color' => 'slate',
    'size' => 'sm',
    'dot' => false,
])

@php
    $colorMap = [
        'green'   => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'yellow'  => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'amber'   => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'red'     => 'bg-red-50 text-red-700 ring-red-600/20',
        'blue'    => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'indigo'  => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
        'slate'   => 'bg-slate-50 text-slate-700 ring-slate-600/20',
        'purple'  => 'bg-purple-50 text-purple-700 ring-purple-600/20',
    ];

    $dotColorMap = [
        'green'   => 'fill-emerald-500',
        'emerald' => 'fill-emerald-500',
        'yellow'  => 'fill-amber-500',
        'amber'   => 'fill-amber-500',
        'red'     => 'fill-red-500',
        'blue'    => 'fill-blue-500',
        'indigo'  => 'fill-indigo-500',
        'slate'   => 'fill-slate-500',
        'purple'  => 'fill-purple-500',
    ];

    $sizeMap = [
        'xs' => 'px-1.5 py-0.5 text-[0.625rem]',
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-xs',
    ];

    $classes = ($colorMap[$color] ?? $colorMap['slate']) . ' ' . ($sizeMap[$size] ?? $sizeMap['sm']);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-x-1 rounded-md font-medium ring-1 ring-inset {$classes}"]) }}>
    @if($dot)
        <svg class="h-1.5 w-1.5 {{ $dotColorMap[$color] ?? $dotColorMap['slate'] }}" viewBox="0 0 6 6" aria-hidden="true">
            <circle cx="3" cy="3" r="3" />
        </svg>
    @endif
    {{ $slot }}
</span>
