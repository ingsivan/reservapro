<x-auth-card title="Nueva contraseña">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field name="email" type="email" label="Correo" :value="old('email', $email)" autocomplete="email" required />
        <x-field name="password" type="password" label="Contraseña nueva" autocomplete="new-password" required autofocus />
        <x-field name="password_confirmation" type="password" label="Repite la contraseña" autocomplete="new-password" required />
        <button class="w-full rounded-lg bg-teal-600 px-4 py-2.5 font-semibold text-white hover:bg-teal-500">Guardar contraseña</button>
    </form>
</x-auth-card>
