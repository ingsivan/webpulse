@props(['title' => null])

<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "{$title} · WebPulse" : 'WebPulse · Monitor de sitios web' }}</title>
    <meta name="description" content="Audita cualquier sitio web: enlaces rotos, SEO, imágenes pesadas y tiempos de carga.">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%234f46e5'/><path d='M5 17h6l3-7 4 13 3-6h6' stroke='white' stroke-width='2.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col text-slate-800 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold text-slate-900">
                <svg class="size-8" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="8" class="fill-indigo-600"/><path d="M5 17h6l3-7 4 13 3-6h6" stroke="white" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                WebPulse
            </a>
            <div class="flex items-center gap-2 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-md px-3 py-2 font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">Mis sitios</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-md px-3 py-2 font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">Salir</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-md px-3 py-2 font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">Entrar</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-indigo-600 px-3 py-2 font-medium text-white hover:bg-indigo-500">Crear cuenta</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 sm:py-12">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-sm text-slate-500 sm:flex-row sm:justify-between sm:px-6">
            <p>WebPulse · Proyecto de portafolio hecho con Laravel</p>
            <a href="https://github.com/ingsivan/webpulse" class="hover:text-slate-700">Código en GitHub</a>
        </div>
    </footer>
</body>
</html>
