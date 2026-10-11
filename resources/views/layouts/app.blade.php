@props(['title' => null])

<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "{$title} · ReservaPro" : 'ReservaPro · Reservaciones en línea' }}</title>
    <meta name="description" content="Reservaciones en línea para negocios de servicios: horarios, citas, confirmaciones y recordatorios por correo.">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%230d9488'/><rect x='7' y='9' width='18' height='16' rx='2.5' stroke='white' stroke-width='2.2' fill='none'/><path d='M7 14h18M12 6.5v4M20 6.5v4' stroke='white' stroke-width='2.2' stroke-linecap='round'/></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-full flex-col text-slate-800 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold text-slate-900">
                <x-logo />
                ReservaPro
            </a>
            <div class="flex items-center gap-1 text-sm sm:gap-2">
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.agenda') }}" class="rounded-md px-3 py-2 font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">Mi panel</a>
                    @else
                        <a href="{{ route('my-appointments') }}" class="rounded-md px-3 py-2 font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">Mis citas</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-md px-3 py-2 font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">Salir</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-md px-3 py-2 font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">Entrar</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-teal-600 px-3 py-2 font-medium text-white hover:bg-teal-500">Crear cuenta</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 sm:py-12">
        <x-flash />

        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-sm text-slate-500 sm:flex-row sm:justify-between sm:px-6">
            <p>ReservaPro · Proyecto de portafolio hecho con Laravel y Livewire</p>
            <a href="https://github.com/ingsivan/reservapro" class="hover:text-slate-700">Código en GitHub</a>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
