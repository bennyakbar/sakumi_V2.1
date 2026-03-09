@props([
    'striped' => false,
    'hoverable' => true,
    'compact' => false,
])

@php
    $cellPadding = $compact ? 'px-4 py-2.5' : 'px-5 py-3.5';
@endphp

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-xl border border-slate-200/80 bg-white shadow-sm']) }}>
    <table class="min-w-full divide-y divide-slate-200">
        @isset($head)
            <thead>
                <tr class="bg-slate-50/80">
                    {{ $head }}
                </tr>
            </thead>
        @endisset

        <tbody class="divide-y divide-slate-100 {{ $striped ? '[&>tr:nth-child(even)]:bg-slate-50/50' : '' }} {{ $hoverable ? '[&>tr]:hover:bg-slate-50/80 [&>tr]:transition-colors [&>tr]:duration-100' : '' }}">
            {{ $slot }}
        </tbody>

        @isset($foot)
            <tfoot class="border-t border-slate-200 bg-slate-50/60">
                {{ $foot }}
            </tfoot>
        @endisset
    </table>

    @isset($pagination)
        <div class="border-t border-slate-100 px-5 py-3">
            {{ $pagination }}
        </div>
    @endisset
</div>
