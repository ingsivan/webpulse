<x-auth-card title="Entrar">
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-input name="email" type="email" label="Correo" autocomplete="email" required autofocus />
        <x-input name="password" type="password" label="Contraseña" autocomplete="current-password" required />
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="rounded border-slate-300"> Recordarme
        </label>
        <button class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white hover:bg-indigo-500">Entrar</button>
    </form>

    <x-slot:footer>
        ¿No tienes cuenta? <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:underline">Crea una</a>
    </x-slot:footer>
</x-auth-card>
