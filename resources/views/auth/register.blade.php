<x-auth-card title="Crear cuenta">
    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ type: @js(old('account_type', 'client')) }">
        @csrf
        <fieldset>
            <legend class="text-sm font-medium text-slate-700">¿Para qué usarás ReservaPro?</legend>
            <div class="mt-2 grid grid-cols-2 gap-2">
                <label class="flex cursor-pointer flex-col rounded-lg border px-3 py-2 text-sm has-checked:border-teal-600 has-checked:bg-teal-50">
                    <input type="radio" name="account_type" value="client" x-model="type" class="sr-only">
                    <span class="font-semibold text-slate-900">Reservar citas</span>
                    <span class="text-xs text-slate-500">Soy cliente</span>
                </label>
                <label class="flex cursor-pointer flex-col rounded-lg border px-3 py-2 text-sm has-checked:border-teal-600 has-checked:bg-teal-50">
                    <input type="radio" name="account_type" value="admin" x-model="type" class="sr-only">
                    <span class="font-semibold text-slate-900">Recibir reservas</span>
                    <span class="text-xs text-slate-500">Tengo un negocio</span>
                </label>
            </div>
            @error('account_type')
                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </fieldset>
        <div x-show="type === 'admin'" x-cloak>
            <x-field name="business_name" label="Nombre del negocio" placeholder="Ej. Barbería Don Pepe" />
        </div>
        <x-field name="name" label="Tu nombre" autocomplete="name" required />
        <x-field name="email" type="email" label="Correo" autocomplete="email" required />
        <x-field name="password" type="password" label="Contraseña" autocomplete="new-password" hint="Al menos 8 caracteres." required />
        <x-field name="password_confirmation" type="password" label="Confirma la contraseña" autocomplete="new-password" required />
        <button class="w-full rounded-lg bg-teal-600 px-4 py-2.5 font-semibold text-white hover:bg-teal-500">Crear cuenta</button>
    </form>

    <x-slot:footer>
        ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-medium text-teal-700 hover:underline">Entra</a>
    </x-slot:footer>
</x-auth-card>
