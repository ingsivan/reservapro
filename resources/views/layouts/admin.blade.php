@props(['title' => null])

@php
    $user = auth()->user();
    $business = $user->business;
    $bookingUrl = route('booking', $business);
    $links = [
        ['admin.agenda', 'Agenda', 'calendar'],
        ['admin.services', 'Servicios', 'tag'],
        ['admin.team', 'Equipo', 'users'],
        ['admin.schedule', 'Horario', 'clock'],
        ['admin.reports', 'Reportes', 'chart'],
        ['admin.settings', 'Ajustes', 'sliders'],
    ];
@endphp

<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "{$title} · Panel · ReservaPro" : 'Panel · ReservaPro' }}</title>
    <x-app-meta />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full text-slate-800 antialiased">
    {{-- Sidebar (desktop) --}}
    <aside class="hidden border-r border-slate-200 bg-white lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
        <a href="{{ route('admin.agenda') }}" wire:navigate class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
            <x-business-avatar :business="$business" class="size-9 shrink-0 rounded-lg text-sm" />
            <span class="min-w-0">
                <span class="block truncate font-semibold text-slate-900">{{ $business->name }}</span>
                <span class="block text-xs text-slate-500">Panel de tu negocio</span>
            </span>
        </a>

        <nav class="flex-1 space-y-1 px-3 py-4" aria-label="Panel">
            @foreach ($links as [$route, $label, $icon])
                <a href="{{ route($route) }}" wire:navigate
                   @class([
                       'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                       'bg-teal-50 text-teal-800' => request()->routeIs($route),
                       'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs($route),
                   ])
                   @if (request()->routeIs($route)) aria-current="page" @endif>
                    <x-icon :name="$icon" :class="request()->routeIs($route) ? 'size-5 text-teal-600' : 'size-5 text-slate-400'" />
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="mx-3 mb-3 rounded-xl border border-slate-200 bg-slate-50 p-3" x-data="{ copied: false }">
            <p class="text-xs font-medium text-slate-500">Tu página de reservas</p>
            <p class="mt-1 truncate text-sm font-medium text-slate-800" title="{{ $bookingUrl }}">/reservar/{{ $business->slug }}</p>
            <div class="mt-2 flex gap-2">
                <a href="{{ $bookingUrl }}" target="_blank" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    <x-icon name="external" class="size-3.5" /> Abrir
                </a>
                <button type="button" x-on:click="navigator.clipboard.writeText(@js($bookingUrl)); copied = true; setTimeout(() => copied = false, 2000)"
                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    <x-icon name="copy" class="size-3.5" />
                    <span x-text="copied ? '¡Copiado!' : 'Copiar'">Copiar</span>
                </button>
            </div>
        </div>

        <x-install-app class="px-3 pb-3" />

        <div class="flex items-center gap-3 border-t border-slate-100 px-5 py-4">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-600">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-medium text-slate-900">{{ $user->name }}</span>
                <span class="block truncate text-xs text-slate-500">{{ $user->email }}</span>
            </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-md p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Salir" title="Salir">
                    <x-icon name="logout" class="size-5" />
                </button>
            </form>
        </div>
    </aside>

    {{-- Top bar (mobile and tablet) --}}
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur-md lg:hidden">
        <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('admin.agenda') }}" wire:navigate class="flex min-w-0 items-center gap-2 font-semibold text-slate-900">
                <x-business-avatar :business="$business" class="size-8 shrink-0 rounded-lg text-xs" />
                <span class="truncate">{{ $business->name }}</span>
            </a>
            <div class="flex shrink-0 items-center gap-1 text-sm">
                <x-install-app compact />
                <a href="{{ $bookingUrl }}" target="_blank" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900" aria-label="Ver página pública" title="Ver página pública">
                    <x-icon name="external" class="size-5" />
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900" aria-label="Salir" title="Salir">
                        <x-icon name="logout" class="size-5" />
                    </button>
                </form>
            </div>
        </div>
        <nav class="flex gap-1 overflow-x-auto px-2 sm:px-4" aria-label="Panel">
            @foreach ($links as [$route, $label, $icon])
                <a href="{{ route($route) }}" wire:navigate
                   @class([
                       'flex items-center gap-1.5 border-b-2 px-3 py-2.5 text-sm font-medium whitespace-nowrap',
                       'border-teal-600 text-teal-700' => request()->routeIs($route),
                       'border-transparent text-slate-600 hover:text-slate-900' => ! request()->routeIs($route),
                   ])
                   @if (request()->routeIs($route)) aria-current="page" x-init="$el.scrollIntoView({ block: 'nearest', inline: 'nearest' })" @endif>
                    <x-icon :name="$icon" class="size-4" />
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </header>

    <main class="lg:pl-64">
        <div class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-10">
            {{ $slot }}
        </div>
    </main>

    <x-toaster />
    <x-confirm-dialog />

    @livewireScripts
</body>
</html>
