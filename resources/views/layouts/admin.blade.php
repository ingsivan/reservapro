@props(['title' => null])

@php
    $business = auth()->user()->business;
    $links = [
        ['admin.agenda', 'Agenda'],
        ['admin.services', 'Servicios'],
        ['admin.schedule', 'Horario'],
        ['admin.settings', 'Ajustes'],
    ];
@endphp

<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "{$title} · Panel · ReservaPro" : 'Panel · ReservaPro' }}</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%230d9488'/><rect x='7' y='9' width='18' height='16' rx='2.5' stroke='white' stroke-width='2.2' fill='none'/><path d='M7 14h18M12 6.5v4M20 6.5v4' stroke='white' stroke-width='2.2' stroke-linecap='round'/></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-full flex-col text-slate-800 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('admin.agenda') }}" class="flex min-w-0 items-center gap-2 font-semibold text-slate-900">
                <x-logo class="size-8 shrink-0" />
                <span class="truncate">{{ $business->name }}</span>
            </a>
            <div class="flex shrink-0 items-center gap-1 text-sm sm:gap-2">
                <a href="{{ route('booking', $business) }}" target="_blank" class="hidden rounded-md px-3 py-2 font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 sm:block">Ver página pública ↗</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-md px-3 py-2 font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">Salir</button>
                </form>
            </div>
        </div>
        <nav class="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4 sm:px-6" aria-label="Panel">
            @foreach ($links as [$route, $label])
                <a href="{{ route($route) }}" wire:navigate
                   @class([
                       'whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium',
                       'border-teal-600 text-teal-700' => request()->routeIs($route),
                       'border-transparent text-slate-600 hover:border-slate-300 hover:text-slate-900' => ! request()->routeIs($route),
                   ])
                   @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6">
        <x-flash />

        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
