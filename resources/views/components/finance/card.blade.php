@props([
    'title' => null,
    'subtitle' => null,
    'padding' => true,
    'noDivider' => false,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-slate-200/80 shadow-sm']) }}>
    @if($title || isset($action))
        <div class="flex items-center justify-between px-5 py-4 {{ !$noDivider ? 'border-b border-slate-100' : '' }}">
            <div>
                @if($title)
                    <h3 class="text-sm font-semibold text-slate-800">{{ $title }}</h3>
                @endif
                @if($subtitle)
                    <p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($action)
                <div class="flex items-center gap-2">
                    {{ $action }}
                </div>
            @endisset
        </div>
    @endif

    <div class="{{ $padding ? 'p-5' : '' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-slate-100 px-5 py-3">
            {{ $footer }}
        </div>
    @endisset
</div>
