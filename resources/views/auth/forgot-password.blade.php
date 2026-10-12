<x-auth-card title="Recuperar contraseña">
    <p class="text-sm text-slate-600">Escribe el correo de tu cuenta y te enviaremos un enlace para elegir una contraseña nueva.</p>
    <form method="POST" action="{{ route('password.email') }}" class="mt-5 space-y-4">
        @csrf
        <x-field name="email" type="email" label="Correo" autocomplete="email" required autofocus />
        <button class="w-full rounded-lg bg-teal-600 px-4 py-2.5 font-semibold text-white hover:bg-teal-500">Enviar enlace</button>
    </form>

    <x-slot:footer>
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1 font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="size-4" /> Volver a entrar</a>
    </x-slot:footer>
</x-auth-card>
