<x-auth-card title="Crear cuenta">
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <x-input name="name" label="Nombre" autocomplete="name" required autofocus />
        <x-input name="email" type="email" label="Correo" autocomplete="email" required />
        <x-input name="password" type="password" label="Contraseña" autocomplete="new-password" required />
        <x-input name="password_confirmation" type="password" label="Repite la contraseña" autocomplete="new-password" required />
        <button class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white hover:bg-indigo-500">Crear cuenta</button>
    </form>

    <x-slot:footer>
        ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:underline">Entra</a>
    </x-slot:footer>
</x-auth-card>
