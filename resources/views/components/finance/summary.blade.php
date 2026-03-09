@props([
    'title',
    'value',
    'icon' => null,
    'trend' => null,
    'trendLabel' => null,
    'color' => 'indigo',
])

@php
    $colorMap = [
        'indigo'  => ['bg' => 'bg-indigo-50',  'text' => 'text-indigo-600',  'icon' => 'text-indigo-500',  'ring' => 'ring-indigo-500/20'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'icon' => 'text-emerald-500', 'ring' => 'ring-emerald-500/20'],
        'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600',   'icon' => 'text-amber-500',   'ring' => 'ring-amber-500/20'],
        'red'     => ['bg' => 'bg-red-50',      'text' => 'text-red-600',     'icon' => 'text-red-500',     'ring' => 'ring-red-500/20'],
        'blue'    => ['bg' => 'bg-blue-50',     'text' => 'text-blue-600',    'icon' => 'text-blue-500',    'ring' => 'ring-blue-500/20'],
        'slate'   => ['bg' => 'bg-slate-50',    'text' => 'text-slate-600',   'icon' => 'text-slate-500',   'ring' => 'ring-slate-500/20'],
    ];
    $c = $colorMap[$color] ?? $colorMap['indigo'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-slate-200/80 shadow-sm p-5']) }}>
    <div class="flex items-start justify-between">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ $title }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $value }}</p>

            @if($trend !== null || $trendLabel)
                <div class="mt-2 flex items-center gap-x-1.5 text-xs">
                    @if($trend !== null)
                        @if($trend > 0)
                            <span class="inline-flex items-center gap-x-0.5 font-medium text-emerald-600">
                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                                </svg>
                                {{ $trend }}%
                            </span>
                        @elseif($trend < 0)
                            <span class="inline-flex items-center gap-x-0.5 font-medium text-red-600">
                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5l15 15m0 0V8.25m0 11.25H8.25" />
                                </svg>
                                {{ abs($trend) }}%
                            </span>
                        @else
                            <span class="font-medium text-slate-500">0%</span>
                        @endif
                    @endif
                    @if($trendLabel)
                        <span class="text-slate-400">{{ $trendLabel }}</span>
                    @endif
                </div>
            @endif
        </div>

        @isset($icon)
            <div class="shrink-0 rounded-lg {{ $c['bg'] }} p-2.5 ring-1 {{ $c['ring'] }}">
                <span class="block h-5 w-5 {{ $c['icon'] }}">
                    {{ $icon }}
                </span>
            </div>
        @endisset
    </div>
</div>
