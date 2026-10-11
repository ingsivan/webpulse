@props(['score'])

@php
    $classes = match (true) {
        $score === null => 'bg-slate-100 text-slate-500',
        $score >= 90 => 'bg-emerald-100 text-emerald-800',
        $score >= 50 => 'bg-amber-100 text-amber-800',
        default => 'bg-rose-100 text-rose-800',
    };
@endphp

<span {{ $attributes->class("inline-flex min-w-10 justify-center rounded-full px-2 py-0.5 text-sm font-semibold {$classes}") }}>
    {{ $score ?? '–' }}
</span>
