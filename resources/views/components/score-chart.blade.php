@props(['history'])

{{-- Line chart of overall scores, drawn as inline SVG so it needs no JavaScript library. --}}
@php
    $width = 640;
    $height = 200;
    $padding = 28;
    $count = $history->count();
    $step = $count > 1 ? ($width - 2 * $padding) / ($count - 1) : 0;
    $points = $history->values()->map(fn ($audit, $index) => [
        'x' => round($padding + $index * $step, 1),
        'y' => round($height - $padding - ($audit->overall_score / 100) * ($height - 2 * $padding), 1),
        'audit' => $audit,
    ]);
    $line = $points->map(fn ($point) => "{$point['x']},{$point['y']}")->implode(' ');
@endphp

@if ($count === 0)
    <p class="py-10 text-center text-sm text-slate-500">Todavía no hay auditorías terminadas.</p>
@else
    <svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-auto w-full" role="img" aria-label="Evolución de la puntuación total">
        @foreach ([0, 50, 100] as $mark)
            @php $y = $height - $padding - ($mark / 100) * ($height - 2 * $padding); @endphp
            <line x1="{{ $padding }}" x2="{{ $width - $padding }}" y1="{{ $y }}" y2="{{ $y }}" class="stroke-slate-200" stroke-dasharray="4 4" />
            <text x="{{ $padding - 8 }}" y="{{ $y + 4 }}" text-anchor="end" class="fill-slate-400 text-[10px]">{{ $mark }}</text>
        @endforeach

        @if ($count > 1)
            <polyline points="{{ $line }}" class="fill-none stroke-indigo-500" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
        @endif

        @foreach ($points as $point)
            <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" class="fill-white stroke-indigo-600" stroke-width="2">
                <title>{{ $point['audit']->created_at->format('d/m/Y H:i') }}: {{ $point['audit']->overall_score }}</title>
            </circle>
        @endforeach
    </svg>
@endif
