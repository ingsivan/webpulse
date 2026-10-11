<x-layout :title="'Auditoría de '.$audit->site->name">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm text-slate-500">Auditoría del {{ $audit->created_at->format('d/m/Y H:i') }}</p>
            <h1 class="mt-1 truncate text-2xl font-bold text-slate-900 sm:text-3xl">{{ $audit->site->name }}</h1>
            <a href="{{ $audit->site->url }}" rel="nofollow noopener" target="_blank" class="mt-1 block truncate text-sm text-indigo-600 hover:underline">{{ $audit->site->url }}</a>
        </div>
        <div class="flex shrink-0 gap-2">
            <a href="{{ route('sites.show', $audit->site) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Historial</a>
            @if ($audit->isFinished())
                <a href="{{ route('audits.report', $audit) }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Descargar PDF</a>
            @endif
        </div>
    </div>

    @unless ($audit->isFinished())
        <div class="mt-10 rounded-xl border border-slate-200 bg-white p-8 text-center"
             data-audit-status-url="{{ route('audits.status', $audit) }}">
            <p class="font-medium text-slate-900">Analizando el sitio…</p>
            <p class="mt-1 text-sm text-slate-500">Revisamos la página, sus enlaces y sus imágenes. Esto tarda unos segundos.</p>
            <div class="mx-auto mt-6 h-3 max-w-md overflow-hidden rounded-full bg-slate-200">
                <div data-progress-bar class="h-full rounded-full bg-indigo-600 transition-all duration-500" style="width: {{ $audit->progress }}%"></div>
            </div>
            <p class="mt-2 text-sm text-slate-500"><span data-progress-text>{{ $audit->progress }}</span>%</p>
        </div>
    @elseif ($audit->status === \App\Enums\AuditStatus::Failed)
        <div class="mt-10 rounded-xl border border-rose-200 bg-rose-50 p-6">
            <h2 class="font-semibold text-rose-900">No pudimos auditar el sitio</h2>
            <p class="mt-1 text-sm text-rose-800">{{ $audit->error_message }}</p>
        </div>
    @else
        <section class="mt-8 grid grid-cols-2 gap-6 rounded-xl border border-slate-200 bg-white p-6 sm:grid-cols-4">
            <x-score-ring :score="$audit->overall_score" label="Total" size="lg" />
            <x-score-ring :score="$audit->performance_score" label="Rendimiento" />
            <x-score-ring :score="$audit->seo_score" label="SEO" />
            <x-score-ring :score="$audit->links_score" label="Enlaces" />
        </section>

        <dl class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ([
                'Código HTTP' => $audit->http_status,
                'Tiempo de carga' => number_format($audit->load_time_ms, 0, ',', '.').' ms',
                'Problemas' => $audit->issues->count(),
                'Duración' => $audit->started_at && $audit->finished_at ? $audit->started_at->diffInSeconds($audit->finished_at, true).' s' : '–',
            ] as $term => $value)
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $term }}</dt>
                    <dd class="mt-1 text-lg font-semibold text-slate-900">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <section class="mt-10 space-y-8">
            @foreach (\App\Enums\IssueCategory::cases() as $category)
                @php $issues = $issuesByCategory->get($category->value, collect()); @endphp
                <div>
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900">
                        {{ $category->label() }}
                        <span class="rounded-full bg-slate-200 px-2 text-sm font-medium text-slate-700">{{ $issues->count() }}</span>
                    </h2>
                    @if ($issues->isEmpty())
                        <p class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">Todo en orden en esta categoría.</p>
                    @else
                        <ul class="mt-3 divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200 bg-white">
                            @foreach ($issues as $issue)
                                <li class="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:items-start sm:gap-3">
                                    <x-severity-badge :severity="$issue->severity" />
                                    <div class="min-w-0">
                                        <p class="text-sm text-slate-800">{{ $issue->message }}</p>
                                        @if ($issue->url && $issue->url !== $audit->site->url)
                                            <p class="truncate text-xs text-slate-500">{{ $issue->url }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </section>
    @endunless
</x-layout>
