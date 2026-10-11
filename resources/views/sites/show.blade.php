<x-layout :title="'Historial de '.$site->name">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm text-slate-500">Historial de auditorías</p>
            <h1 class="mt-1 truncate text-2xl font-bold text-slate-900 sm:text-3xl">{{ $site->name }}</h1>
            <p class="mt-1 truncate text-sm text-slate-500">{{ $site->url }}</p>
        </div>
        @can('update', $site)
            <form method="POST" action="{{ route('sites.audits.store', $site) }}">
                @csrf
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Auditar ahora</button>
            </form>
        @endcan
    </div>
    @error('url')
        <p class="mt-3 text-sm text-rose-600" role="alert">{{ $message }}</p>
    @enderror

    <section class="mt-8 rounded-xl border border-slate-200 bg-white p-4 sm:p-6">
        <h2 class="font-semibold text-slate-900">Puntuación total en el tiempo</h2>
        <div class="mt-4">
            <x-score-chart :history="$history" />
        </div>
    </section>

    <section class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Fecha</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 text-center font-medium">Total</th>
                        <th class="hidden px-4 py-3 text-center font-medium sm:table-cell">Rendimiento</th>
                        <th class="hidden px-4 py-3 text-center font-medium sm:table-cell">SEO</th>
                        <th class="hidden px-4 py-3 text-center font-medium sm:table-cell">Enlaces</th>
                        <th class="px-4 py-3 text-right font-medium">Carga</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($audits as $audit)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('audits.show', $audit) }}" class="font-medium text-indigo-600 hover:underline">{{ $audit->created_at->format('d/m/Y H:i') }}</a>
                            </td>
                            <td class="px-4 py-3"><x-audit-status :status="$audit->status" /></td>
                            <td class="px-4 py-3 text-center"><x-score-badge :score="$audit->overall_score" /></td>
                            <td class="hidden px-4 py-3 text-center sm:table-cell">{{ $audit->performance_score ?? '–' }}</td>
                            <td class="hidden px-4 py-3 text-center sm:table-cell">{{ $audit->seo_score ?? '–' }}</td>
                            <td class="hidden px-4 py-3 text-center sm:table-cell">{{ $audit->links_score ?? '–' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">{{ $audit->load_time_ms ? number_format($audit->load_time_ms, 0, ',', '.').' ms' : '–' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Sin auditorías todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $audits->links() }}</div>
</x-layout>
