<x-layout title="Mis sitios">
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Mis sitios</h1>
        <p class="text-slate-600">WebPulse audita estos sitios según la frecuencia que elijas y te escribe a {{ auth()->user()->email }} si alguno se cae.</p>
    </div>

    <form method="POST" action="{{ route('sites.store') }}" class="mt-8 grid gap-3 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-end">
        @csrf
        <div>
            <label for="url" class="text-sm font-medium text-slate-700">Dirección</label>
            <input id="url" name="url" type="text" inputmode="url" required value="{{ old('url') }}" placeholder="ejemplo.com"
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 focus:outline-none" />
        </div>
        <div>
            <label for="name" class="text-sm font-medium text-slate-700">Nombre <span class="font-normal text-slate-400">(opcional)</span></label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Mi tienda"
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 focus:outline-none" />
        </div>
        <div>
            <label for="check_interval_minutes" class="text-sm font-medium text-slate-700">Revisar</label>
            <select id="check_interval_minutes" name="check_interval_minutes" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                @foreach ($intervals as $minutes => $label)
                    <option value="{{ $minutes }}" @selected((int) old('check_interval_minutes', 60) === $minutes)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-lg bg-indigo-600 px-5 py-2 font-semibold text-white hover:bg-indigo-500">Agregar</button>
        @if ($errors->any())
            <div class="text-sm text-rose-600 sm:col-span-4" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
    </form>

    <section class="mt-8 grid gap-4 md:grid-cols-2">
        @forelse ($sites as $site)
            <article class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="flex items-center gap-2 font-semibold text-slate-900">
                            <span class="size-2.5 shrink-0 rounded-full {{ $site->is_down ? 'bg-rose-500' : ($site->latestAudit ? 'bg-emerald-500' : 'bg-slate-300') }}"
                                  title="{{ $site->is_down ? 'Caído' : 'En línea' }}"></span>
                            <a href="{{ route('sites.show', $site) }}" class="truncate hover:underline">{{ $site->name }}</a>
                        </h2>
                        <p class="truncate text-sm text-slate-500">{{ $site->url }}</p>
                    </div>
                    <x-score-badge :score="$site->latestAudit?->overall_score" />
                </div>

                <p class="mt-3 text-xs text-slate-500">
                    @if ($site->last_checked_at)
                        Última revisión {{ $site->last_checked_at->diffForHumans() }}
                    @else
                        Sin revisar todavía
                    @endif
                    · {{ $site->is_monitored ? strtolower($intervals[$site->check_interval_minutes] ?? '') : 'monitoreo pausado' }}
                </p>

                <div class="mt-4 flex flex-wrap gap-2 text-sm">
                    <form method="POST" action="{{ route('sites.audits.store', $site) }}">
                        @csrf
                        <button class="rounded-md bg-slate-900 px-3 py-1.5 font-medium text-white hover:bg-slate-700">Auditar ahora</button>
                    </form>
                    <form method="POST" action="{{ route('sites.update', $site) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="name" value="{{ $site->name }}">
                        <input type="hidden" name="check_interval_minutes" value="{{ $site->check_interval_minutes }}">
                        <input type="hidden" name="is_monitored" value="{{ $site->is_monitored ? 0 : 1 }}">
                        <button class="rounded-md border border-slate-300 px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-50">
                            {{ $site->is_monitored ? 'Pausar monitoreo' : 'Reanudar monitoreo' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('sites.destroy', $site) }}" data-confirm="¿Eliminar {{ $site->name }} y todo su historial?">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-md px-3 py-1.5 font-medium text-rose-600 hover:bg-rose-50">Eliminar</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-slate-500 md:col-span-2">
                Todavía no agregas sitios. Escribe una dirección arriba para empezar.
            </p>
        @endforelse
    </section>
</x-layout>
