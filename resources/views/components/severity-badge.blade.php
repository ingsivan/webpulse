@props(['severity'])

@php
    $classes = match ($severity) {
        \App\Enums\IssueSeverity::Error => 'bg-rose-100 text-rose-800',
        \App\Enums\IssueSeverity::Warning => 'bg-amber-100 text-amber-800',
        \App\Enums\IssueSeverity::Info => 'bg-sky-100 text-sky-800',
    };
@endphp

<span class="inline-flex shrink-0 rounded px-2 py-0.5 text-xs font-medium {{ $classes }}">{{ $severity->label() }}</span>
