@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between']) }}>
    {{-- Left: title & subtitle --}}
    @if($title || $subtitle)
        <div class="min-w-0">
            @if($title)
                <h2 class="text-lg font-semibold tracking-tight text-slate-800">{{ $title }}</h2>
            @endif
            @if($subtitle)
                <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    {{-- Right: action buttons --}}
    <div class="flex flex-wrap items-center gap-2 shrink-0">
        {{ $slot }}
    </div>
</div>
