@props(['score', 'label', 'size' => 'md'])

@php
    $radius = 42;
    $circumference = 2 * M_PI * $radius;
    $value = $score ?? 0;
    $color = match (true) {
        $score === null => 'text-slate-300',
        $score >= 90 => 'text-emerald-500',
        $score >= 50 => 'text-amber-500',
        default => 'text-rose-500',
    };
    $box = $size === 'lg' ? 'size-32' : 'size-24';
@endphp

<div {{ $attributes->class('flex flex-col items-center gap-2') }}>
    <div class="relative {{ $box }}">
        <svg viewBox="0 0 100 100" class="size-full -rotate-90" aria-hidden="true">
            <circle cx="50" cy="50" r="{{ $radius }}" stroke-width="9" class="fill-none stroke-slate-200" />
            <circle cx="50" cy="50" r="{{ $radius }}" stroke-width="9" stroke-linecap="round"
                    class="fill-none stroke-current {{ $color }}"
                    stroke-dasharray="{{ $circumference }}"
                    stroke-dashoffset="{{ $circumference * (1 - $value / 100) }}" />
        </svg>
        <span class="absolute inset-0 flex items-center justify-center font-semibold {{ $size === 'lg' ? 'text-3xl' : 'text-2xl' }} text-slate-900">
            {{ $score ?? '–' }}
        </span>
    </div>
    <span class="text-sm font-medium text-slate-600">{{ $label }}</span>
</div>
