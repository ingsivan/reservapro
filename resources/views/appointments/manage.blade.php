<x-layouts::app title="Tu cita">
    <div class="mx-auto max-w-xl">
        <p class="text-sm font-medium text-teal-700">{{ $appointment->business->name }}</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900">Tu cita</h1>

        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-lg font-semibold text-slate-900">{{ $appointment->service->name }}</p>
                    <p class="mt-1 text-slate-600 first-letter:uppercase">{{ $appointment->whenLabel() }}</p>
                </div>
                <x-status-badge :status="$appointment->status" />
            </div>

            <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-4 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">A nombre de</dt><dd class="font-medium text-slate-900">{{ $appointment->customer_name }}</dd></div>
                <div><dt class="text-slate-500">Código</dt><dd class="font-mono font-semibold tracking-wider text-slate-900">{{ $appointment->code }}</dd></div>
                <div><dt class="text-slate-500">Precio</dt><dd class="font-medium text-slate-900">{{ $appointment->formattedPrice() }}</dd></div>
                @if ($appointment->business->address)
                    <div><dt class="text-slate-500">Dirección</dt><dd class="font-medium text-slate-900">{{ $appointment->business->address }}</dd></div>
                @endif
            </dl>

            @if ($appointment->canBeCancelledByCustomer())
                <form method="POST" action="{{ $cancelUrl }}" class="mt-6 border-t border-slate-100 pt-6"
                      onsubmit="return confirm('¿Seguro que quieres cancelar tu cita?')">
                    @csrf
                    <button class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Cancelar mi cita</button>
                    <p class="mt-2 text-xs text-slate-500">Puedes cancelar hasta {{ $appointment->business->cancellation_notice_hours }} horas antes.</p>
                </form>
            @elseif ($appointment->status->isActive())
                <p class="mt-6 border-t border-slate-100 pt-6 text-sm text-slate-600">
                    Ya pasó el plazo para cancelar en línea.
                    @if ($appointment->business->phone)
                        Llama al {{ $appointment->business->phone }} si necesitas cambiarla.
                    @endif
                </p>
            @endif
        </div>

        <a href="{{ route('booking', $appointment->business) }}" class="mt-6 inline-block text-sm font-medium text-teal-700 hover:underline">Reservar otra cita en {{ $appointment->business->name }}</a>
    </div>
</x-layouts::app>
