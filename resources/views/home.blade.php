<x-layouts::app>
    {{-- Hero --}}
    <section class="relative left-1/2 -mt-8 w-screen -translate-x-1/2 overflow-hidden bg-slate-950 sm:-mt-12">
        <img
            src="{{ asset('images/hero-peluqueria.webp') }}"
            srcset="{{ asset('images/hero-peluqueria-960.webp') }} 960w, {{ asset('images/hero-peluqueria.webp') }} 1920w"
            sizes="100vw"
            alt=""
            fetchpriority="high"
            class="absolute inset-0 size-full object-cover object-center opacity-60"
        >
        <div aria-hidden="true" class="absolute inset-0 bg-linear-to-r from-slate-950 via-slate-950/85 to-slate-950/30"></div>
        <div aria-hidden="true" class="absolute inset-x-0 bottom-0 h-24 bg-linear-to-t from-slate-950/80 to-transparent"></div>
        <div aria-hidden="true" class="absolute -top-32 -left-32 size-96 rounded-full bg-teal-500/25 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-6xl items-center gap-14 px-4 pt-14 pb-20 sm:px-6 lg:grid-cols-[1.15fr_1fr] lg:pt-20 lg:pb-24">
            <div class="text-center lg:text-left">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-sm font-medium text-teal-200 ring-1 ring-white/20 backdrop-blur">
                    <x-icon name="sparkles" class="size-4 text-teal-300" /> Para peluquerías, barberías y estéticas
                </p>
                <h1 class="mt-6 text-4xl font-bold tracking-tight text-balance text-white sm:text-5xl lg:text-6xl">
                    Tus clientes reservan solos. <span class="block text-teal-300">Tú te dedicas a cortar.</span>
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-lg text-pretty text-slate-300 lg:mx-0">
                    Tus clientes eligen servicio, estilista, día y hora desde el celular, a cualquier hora. Tú ves todas las citas en una agenda y ellos reciben su recordatorio por correo y WhatsApp para que no falten.
                </p>
                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row lg:justify-start">
                    @if ($demoBusiness)
                        <a href="{{ route('booking', $demoBusiness) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-500 px-5 py-3 font-semibold text-white shadow-lg shadow-teal-500/30 hover:bg-teal-400">
                            Probar una reserva <x-icon name="arrow-right" class="size-4" />
                        </a>
                    @endif
                    @guest
                        <form method="POST" action="{{ route('demo.login') }}">
                            @csrf
                            <button class="w-full rounded-xl bg-white/10 px-5 py-3 font-semibold text-white ring-1 ring-white/25 backdrop-blur hover:bg-white/20">Ver el panel del negocio</button>
                        </form>
                    @else
                        <a href="{{ auth()->user()->homeRoute() }}" class="rounded-xl bg-white/10 px-5 py-3 font-semibold text-white ring-1 ring-white/25 backdrop-blur hover:bg-white/20">Ir a mi cuenta</a>
                    @endguest
                </div>
                <ul class="mt-8 flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm text-slate-300 lg:justify-start">
                    @foreach (['Reserva en 3 pasos', 'Sin crear cuenta', 'Cero citas encimadas'] as $highlight)
                        <li class="flex items-center gap-1.5"><x-icon name="check-circle" class="size-4 text-teal-300" /> {{ $highlight }}</li>
                    @endforeach
                </ul>
            </div>

            {{-- Product preview --}}
            <div class="relative mx-auto w-full max-w-md" aria-hidden="true">
                <div class="absolute -inset-3 -z-10 rotate-3 rounded-3xl bg-linear-to-br from-teal-400 to-emerald-300 opacity-80"></div>
                <div class="overflow-hidden rounded-2xl bg-white shadow-2xl shadow-black/40">
                    <div class="relative h-14 bg-linear-to-br from-teal-700 via-teal-500 to-emerald-400"><div class="bg-dots absolute inset-0"></div></div>
                    <div class="px-5 pb-5">
                        <div class="relative -mt-7 flex items-start gap-3">
                            <span class="flex size-14 shrink-0 items-center justify-center rounded-xl border-4 border-white bg-slate-900 text-lg font-bold text-white">BC</span>
                            <div class="min-w-0 pt-8">
                                <p class="truncate font-semibold text-slate-900">Barbería El Clásico</p>
                                <p class="flex items-center gap-1.5 text-xs text-emerald-700"><span class="size-1.5 rounded-full bg-emerald-500"></span> Abierto · cierra a las 20:00</p>
                            </div>
                        </div>
                        <div class="mt-5 rounded-xl border border-teal-600 bg-teal-50/60 p-3 ring-1 ring-teal-600">
                            <div class="flex items-center justify-between text-sm"><span class="font-semibold text-slate-900">Corte y barba</span><span class="font-semibold">$320.00</span></div>
                            <p class="mt-1 flex items-center gap-1 text-xs text-slate-500"><x-icon name="clock" class="size-3.5" /> 1 h · con Luis</p>
                        </div>
                        <div class="mt-4 grid grid-cols-5 gap-1.5 text-center">
                            @foreach (['LUN' => 12, 'MAR' => 13, 'MIÉ' => 14, 'JUE' => 15, 'VIE' => 16] as $dayName => $dayNumber)
                                <div @class(['rounded-lg border py-1.5', 'border-teal-600 bg-teal-600 text-white' => $dayNumber === 13, 'border-slate-200 text-slate-700' => $dayNumber !== 13])>
                                    <p class="text-[0.6rem] font-medium">{{ $dayName }}</p>
                                    <p class="text-sm font-bold">{{ $dayNumber }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 grid grid-cols-4 gap-1.5 text-center text-xs font-semibold">
                            @foreach (['10:00', '11:30', '12:00', '13:00', '16:30', '17:00', '18:00', '19:00'] as $time)
                                <span @class(['rounded-md border py-1.5', 'border-teal-600 bg-teal-600 text-white' => $time === '12:00', 'border-slate-200 text-slate-600' => $time !== '12:00'])>{{ $time }}</span>
                            @endforeach
                        </div>
                        <div class="mt-4 rounded-lg bg-slate-900 py-2.5 text-center text-sm font-semibold text-white">Confirmar reserva · $320.00</div>
                    </div>
                </div>
                <div class="absolute -bottom-7 -left-6 hidden items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-xl ring-1 ring-slate-200 sm:flex">
                    <span class="flex size-9 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"><x-icon name="check" class="size-5" /></span>
                    <span class="text-sm">
                        <span class="block font-semibold text-slate-900">¡Cita confirmada!</span>
                        <span class="block text-xs text-slate-500">Mar 13 · 12:00 · recordatorio por WhatsApp</span>
                    </span>
                </div>
                <div class="absolute -top-5 -right-3 hidden items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 shadow-lg ring-1 ring-slate-200 sm:flex">
                    <x-icon name="bell" class="size-4 text-teal-600" /> Nueva cita · 12:00
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="mt-20">
        <div class="text-center">
            <p class="text-sm font-semibold tracking-wide text-teal-700 uppercase">Cómo funciona</p>
            <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Una reserva en menos de un minuto</h2>
        </div>
        <ol class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach ([
                ['tag', 'Elige el servicio', 'Con precio y duración a la vista, sin llamadas ni mensajes.'],
                ['calendar', 'Escoge día y hora', 'Solo aparecen los horarios que de verdad están libres.'],
                ['mail', 'Recibe la confirmación', 'Un correo con el resumen, recordatorio un día antes y enlace para cancelar.'],
            ] as $index => [$icon, $title, $text])
                <li class="relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <span class="absolute top-6 right-6 text-5xl font-bold text-slate-100">{{ $index + 1 }}</span>
                    <span class="relative flex size-11 items-center justify-center rounded-xl bg-teal-600 text-white shadow-md shadow-teal-600/25"><x-icon :name="$icon" class="size-5" /></span>
                    <h3 class="relative mt-4 font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="relative mt-1 text-sm text-slate-600">{{ $text }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Features --}}
    <section class="mt-24">
        <div class="text-center">
            <p class="text-sm font-semibold tracking-wide text-teal-700 uppercase">Para el negocio</p>
            <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Todo lo que necesitas para no perder citas</h2>
        </div>
        <div class="mt-10 grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['shield', 'Cero dobles reservas', 'Cada reserva se guarda dentro de una transacción con bloqueo. Si dos personas eligen la misma hora, solo una la obtiene.'],
                ['clock', 'Horarios flexibles', 'Varios turnos por día, horarios que cruzan la medianoche, días festivos bloqueados y anticipación mínima.'],
                ['bell', 'Correos automáticos', 'Confirmación al reservar, aviso al negocio, recordatorio 24 horas antes y avisos de cancelación.'],
                ['link', 'Cancelación sin cuenta', 'Cada correo trae un enlace firmado para ver o cancelar la cita, con el plazo que defina el negocio.'],
                ['calendar-check', 'Agenda semanal', 'Calendario con las citas de la semana, filtros por estado y acciones para confirmar, cancelar o marcar asistencia.'],
                ['wallet', 'Ingresos a la vista', 'Citas del día, pendientes por confirmar e ingresos estimados de la semana en un vistazo.'],
            ] as [$icon, $title, $text])
                <div class="flex gap-4">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700 ring-1 ring-teal-600/10"><x-icon :name="$icon" class="size-5" /></span>
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $text }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Call to action --}}
    <section class="relative mt-24 overflow-hidden rounded-3xl bg-slate-900 px-6 py-12 text-center sm:px-12">
        <div aria-hidden="true" class="absolute -top-24 -right-24 size-72 rounded-full bg-teal-500/30 blur-3xl"></div>
        <div aria-hidden="true" class="absolute -bottom-24 -left-24 size-72 rounded-full bg-emerald-500/20 blur-3xl"></div>
        <h2 class="relative text-3xl font-bold tracking-tight text-white">Pruébalo ahora, sin crear cuenta</h2>
        <p class="relative mx-auto mt-3 max-w-xl text-slate-300">Haz una reserva en la barbería demo y luego entra al panel para verla aparecer en la agenda.</p>
        <div class="relative mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            @if ($demoBusiness)
                <a href="{{ route('booking', $demoBusiness) }}" class="rounded-xl bg-teal-500 px-5 py-3 font-semibold text-white hover:bg-teal-400">Hacer una reserva</a>
            @endif
            @guest
                <a href="{{ route('register') }}" class="rounded-xl bg-white/10 px-5 py-3 font-semibold text-white ring-1 ring-white/20 hover:bg-white/20">Crear mi negocio</a>
            @endguest
        </div>
    </section>
</x-layouts::app>
