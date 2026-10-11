<x-layouts::app>
    <section class="mx-auto max-w-3xl text-center">
        <p class="inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1 text-sm font-medium text-teal-800 ring-1 ring-teal-600/20">Laravel 13 · Livewire 4 · Tailwind CSS 4</p>
        <h1 class="mt-6 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">Tus clientes reservan solos. Tú solo atiendes.</h1>
        <p class="mt-4 text-lg text-slate-600">
            ReservaPro es un sistema de citas para negocios de servicios: barberías, consultorios, estudios.
            Tus clientes eligen servicio, día y hora libre en tu página; tú ves todo en una agenda semanal y nadie reserva dos veces el mismo horario.
        </p>
        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            @if ($demoBusiness)
                <a href="{{ route('booking', $demoBusiness) }}" class="rounded-lg bg-teal-600 px-5 py-3 font-semibold text-white hover:bg-teal-500">Reservar en el negocio demo</a>
            @endif
            @guest
                <form method="POST" action="{{ route('demo.login') }}">
                    @csrf
                    <button class="w-full rounded-lg border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50">Entrar como admin demo</button>
                </form>
            @else
                <a href="{{ auth()->user()->homeRoute() }}" class="rounded-lg border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50">Ir a mi cuenta</a>
            @endguest
        </div>
    </section>

    <section class="mt-16 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ([
            ['Reserva en tres pasos', 'El cliente elige servicio, día y hora, y deja sus datos. Solo ve horarios que de verdad están libres.'],
            ['Cero dobles reservas', 'Cada reserva se guarda dentro de una transacción con bloqueo. Si dos personas eligen la misma hora, solo una la obtiene.'],
            ['Horarios flexibles', 'Varios turnos por día, horarios que cruzan la medianoche, días festivos bloqueados y anticipación mínima.'],
            ['Correos automáticos', 'Confirmación al reservar, aviso al negocio, recordatorio 24 horas antes y avisos de cancelación.'],
            ['Cancelación sin cuenta', 'Cada correo trae un enlace firmado para ver o cancelar la cita, con el plazo que defina el negocio.'],
            ['Agenda semanal', 'Calendario con las citas de la semana, ingresos estimados y acciones para confirmar, cancelar o marcar asistencia.'],
        ] as [$title, $text])
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">{{ $title }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ $text }}</p>
            </div>
        @endforeach
    </section>
</x-layouts::app>
