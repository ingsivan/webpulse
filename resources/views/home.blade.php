<x-layout>
    <section class="mx-auto max-w-3xl text-center">
        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Auditoría web gratuita</p>
        <h1 class="mt-3 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">¿Qué tan sano está tu sitio web?</h1>
        <p class="mt-4 text-lg text-slate-600">
            WebPulse revisa enlaces rotos, problemas de SEO, imágenes pesadas y tiempos de carga, y te da una puntuación con lo que conviene arreglar primero.
        </p>

        <form method="POST" action="{{ route('audits.store') }}" class="mt-8 flex flex-col gap-3 sm:flex-row">
            @csrf
            <label for="url" class="sr-only">Dirección del sitio</label>
            <input id="url" name="url" type="text" inputmode="url" autocomplete="url" required
                   value="{{ old('url') }}" placeholder="ejemplo.com"
                   class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-base shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 focus:outline-none" />
            <button class="shrink-0 rounded-lg bg-indigo-600 px-6 py-3 font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:outline-none">
                Auditar sitio
            </button>
        </form>
        @error('url')
            <p class="mt-3 text-sm text-rose-600" role="alert">{{ $message }}</p>
        @enderror
    </section>

    <section class="mx-auto mt-16 grid max-w-5xl gap-4 sm:grid-cols-3">
        @foreach ([
            ['Rendimiento', 'Tiempo de respuesta, redirecciones, peso del HTML e imágenes demasiado grandes.'],
            ['SEO', 'Título, meta description, encabezados, idioma, Open Graph, URL canónica y textos alternativos.'],
            ['Enlaces', 'Revisa los enlaces y las imágenes de la página y te dice cuáles están rotos.'],
        ] as [$heading, $text])
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h2 class="font-semibold text-slate-900">{{ $heading }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ $text }}</p>
            </div>
        @endforeach
    </section>

    @if ($recentAudits->isNotEmpty())
        <section class="mx-auto mt-16 max-w-5xl">
            <h2 class="text-lg font-semibold text-slate-900">Auditorías recientes</h2>
            <ul class="mt-4 divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200 bg-white">
                @foreach ($recentAudits as $audit)
                    <li>
                        <a href="{{ route('audits.show', $audit) }}" class="flex items-center justify-between gap-4 px-5 py-3 hover:bg-slate-50">
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-slate-900">{{ $audit->site->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $audit->created_at->diffForHumans() }}</span>
                            </span>
                            <x-score-badge :score="$audit->overall_score" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @guest
        <section class="mx-auto mt-16 max-w-5xl rounded-2xl bg-indigo-600 px-6 py-8 text-white sm:flex sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Monitorea tus sitios automáticamente</h2>
                <p class="mt-1 text-indigo-100">Crea una cuenta y te avisamos por correo si alguno de tus sitios se cae.</p>
            </div>
            <a href="{{ route('register') }}" class="mt-4 inline-block rounded-lg bg-white px-5 py-2.5 font-semibold text-indigo-700 hover:bg-indigo-50 sm:mt-0">Crear cuenta gratis</a>
        </section>
    @endguest
</x-layout>
