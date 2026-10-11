<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte de {{ $audit->site->name }} · WebPulse</title>
    @vite(['resources/css/app.css'])
    <style>@page { margin: 16mm; }</style>
</head>
<body class="bg-white text-slate-800 antialiased">
    <div class="mx-auto max-w-3xl p-8 print:p-0">
        <div class="mb-6 flex items-center justify-between print:hidden">
            <a href="{{ route('audits.show', $audit) }}" class="text-sm text-indigo-600 hover:underline">← Volver a la auditoría</a>
            <button type="button" data-print class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Guardar como PDF</button>
        </div>

        <header class="border-b border-slate-200 pb-4">
            <p class="text-sm font-semibold text-indigo-600">WebPulse · Reporte técnico</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $audit->site->name }}</h1>
            <p class="text-sm text-slate-500">{{ $audit->site->url }} · {{ $audit->created_at->format('d/m/Y H:i') }}</p>
        </header>

        @if ($audit->status === \App\Enums\AuditStatus::Failed)
            <p class="mt-6 text-rose-700">La auditoría falló: {{ $audit->error_message }}</p>
        @else
            <table class="mt-6 w-full text-sm">
                <tbody class="divide-y divide-slate-200">
                    <tr><th class="py-2 text-left font-medium">Puntuación total</th><td class="py-2 text-right font-semibold">{{ $audit->overall_score }} / 100</td></tr>
                    <tr><th class="py-2 text-left font-medium">Rendimiento</th><td class="py-2 text-right">{{ $audit->performance_score }}</td></tr>
                    <tr><th class="py-2 text-left font-medium">SEO</th><td class="py-2 text-right">{{ $audit->seo_score }}</td></tr>
                    <tr><th class="py-2 text-left font-medium">Enlaces e imágenes</th><td class="py-2 text-right">{{ $audit->links_score }}</td></tr>
                    <tr><th class="py-2 text-left font-medium">Código HTTP</th><td class="py-2 text-right">{{ $audit->http_status }}</td></tr>
                    <tr><th class="py-2 text-left font-medium">Tiempo de carga</th><td class="py-2 text-right">{{ number_format($audit->load_time_ms, 0, ',', '.') }} ms</td></tr>
                </tbody>
            </table>

            @foreach (\App\Enums\IssueCategory::cases() as $category)
                @php $issues = $issuesByCategory->get($category->value, collect()); @endphp
                <section class="mt-8 break-inside-avoid-page">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $category->label() }} ({{ $issues->count() }})</h2>
                    @if ($issues->isEmpty())
                        <p class="mt-2 text-sm text-slate-600">Sin problemas detectados.</p>
                    @else
                        <table class="mt-2 w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-300 text-left text-xs uppercase text-slate-500">
                                    <th class="py-1 pr-3 font-medium">Severidad</th>
                                    <th class="py-1 font-medium">Problema</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach ($issues as $issue)
                                    <tr class="break-inside-avoid">
                                        <td class="py-2 pr-3 align-top whitespace-nowrap">{{ $issue->severity->label() }}</td>
                                        <td class="py-2 align-top">
                                            {{ $issue->message }}
                                            @if ($issue->url)
                                                <span class="block break-all text-xs text-slate-500">{{ $issue->url }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>
            @endforeach
        @endif

        <footer class="mt-10 border-t border-slate-200 pt-4 text-xs text-slate-400">Generado por WebPulse · {{ route('audits.show', $audit) }}</footer>
    </div>
    @vite(['resources/js/app.js'])
</body>
</html>
