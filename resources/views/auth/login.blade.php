<x-auth-card title="Entrar">
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-field name="email" type="email" label="Correo" autocomplete="email" required autofocus />
        <x-field name="password" type="password" label="Contraseña" autocomplete="current-password" required />
        <div class="flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="size-4 rounded border-slate-300 accent-teal-600"> Recordarme
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-teal-700 hover:underline">¿Olvidaste tu contraseña?</a>
        </div>
        <button class="w-full rounded-lg bg-teal-600 px-4 py-2.5 font-semibold text-white hover:bg-teal-500">Entrar</button>
    </form>

    <div class="mt-6 border-t border-slate-200 pt-6">
        <form method="POST" action="{{ route('demo.login') }}">
            @csrf
            <button class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Entrar como admin demo</button>
        </form>
        <div class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
            <p class="font-medium text-slate-700">Cuentas de prueba (contraseña <code class="font-mono">password</code>)</p>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach (['Dueño' => \Database\Seeders\DatabaseSeeder::DEMO_ADMIN_EMAIL, 'Cliente' => \Database\Seeders\DatabaseSeeder::DEMO_CLIENT_EMAIL] as $label => $email)
                    <button type="button" class="rounded-md bg-white px-2 py-1 font-medium ring-1 ring-slate-200 hover:ring-teal-400"
                            x-data x-on:click="document.getElementById('email').value = @js($email); document.getElementById('password').value = 'password'">
                        {{ $label }}: {{ $email }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <x-slot:footer>
        ¿No tienes cuenta? <a href="{{ route('register') }}" class="font-medium text-teal-700 hover:underline">Crea una</a>
    </x-slot:footer>
</x-auth-card>
