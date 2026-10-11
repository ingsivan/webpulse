@props(['status'])

@php
    $classes = match ($status) {
        \App\Enums\AuditStatus::Completed => 'bg-emerald-100 text-emerald-800',
        \App\Enums\AuditStatus::Failed => 'bg-rose-100 text-rose-800',
        default => 'bg-indigo-100 text-indigo-800',
    };
@endphp

<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $classes }}">{{ $status->label() }}</span>
