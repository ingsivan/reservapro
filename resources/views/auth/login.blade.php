<x-auth-card title="Entrar">
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-field name="email" type="email" label="Correo" autocomplete="email" required autofocus />
        <x-field name="password" type="password" label="Contraseña" autocomplete="current-password" required />
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="rounded border-slate-300"> Recordarme
        </label>
        <button class="w-full rounded-lg bg-teal-600 px-4 py-2.5 font-semibold text-white hover:bg-teal-500">Entrar</button>
    </form>

    <div class="mt-6 border-t border-slate-200 pt-6">
        <form method="POST" action="{{ route('demo.login') }}">
            @csrf
            <button class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Entrar como admin demo</button>
        </form>
    </div>

    <x-slot:footer>
        ¿No tienes cuenta? <a href="{{ route('register') }}" class="font-medium text-teal-700 hover:underline">Crea una</a>
    </x-slot:footer>
</x-auth-card>
