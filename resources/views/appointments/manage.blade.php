@php
    use App\Enums\AppointmentStatus;

    $business = $appointment->business;
    $isCancelled = $appointment->status === AppointmentStatus::Cancelled;
@endphp

<x-layouts::app title="Tu cita">
    <div class="mx-auto max-w-xl">
        <a href="{{ route('booking', $business) }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-teal-700">
            <x-business-avatar :business="$business" class="size-7 rounded-lg text-xs" />
            {{ $business->name }}
        </a>
        <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">Tu cita</h1>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div @class(['flex items-center gap-4 px-6 py-5', 'bg-rose-50' => $isCancelled, 'bg-teal-50' => ! $isCancelled])>
                <div @class(['flex w-16 shrink-0 flex-col items-center rounded-xl bg-white py-2 shadow-sm ring-1', 'ring-rose-200' => $isCancelled, 'ring-teal-200' => ! $isCancelled])>
                    <span @class(['text-xs font-semibold uppercase', 'text-rose-600' => $isCancelled, 'text-teal-700' => ! $isCancelled])>{{ rtrim($appointment->localStartsAt()->translatedFormat('M'), '.') }}</span>
                    <span @class(['text-2xl leading-7 font-bold', 'text-slate-400 line-through' => $isCancelled, 'text-slate-900' => ! $isCancelled])>{{ $appointment->localStartsAt()->day }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-lg font-semibold text-slate-900">{{ $appointment->service->name }}</p>
                    <p class="text-slate-600 first-letter:uppercase">{{ $appointment->whenLabel() }}</p>
                </div>
                <x-status-badge :status="$appointment->status" class="self-start" />
            </div>

            <dl class="grid gap-4 px-6 py-5 text-sm sm:grid-cols-2">
                @if ($appointment->staffMember)
                    <div><dt class="text-slate-500">Te atiende</dt><dd class="font-medium text-slate-900">{{ $appointment->staffMember->name }}</dd></div>
                @endif
                <div><dt class="text-slate-500">A nombre de</dt><dd class="font-medium text-slate-900">{{ $appointment->customer_name }}</dd></div>
                <div><dt class="text-slate-500">Código</dt><dd class="font-mono font-semibold tracking-widest text-slate-900">{{ $appointment->code }}</dd></div>
                <div><dt class="text-slate-500">Precio</dt><dd class="font-medium text-slate-900">{{ $appointment->formattedPrice() }}</dd></div>
                @if ($business->address)
                    <div><dt class="text-slate-500">Dirección</dt><dd class="font-medium text-slate-900">{{ $business->address }}</dd></div>
                @endif
            </dl>

            @if ($appointment->status->isActive())
                <div class="flex flex-wrap gap-2 border-t border-slate-100 px-6 py-4">
                    <a href="{{ $appointment->googleCalendarUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <x-icon name="calendar-plus" class="size-4" /> Google Calendar
                    </a>
                    <a href="{{ $appointment->calendarFileUrl() }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <x-icon name="download" class="size-4" /> Descargar .ics
                    </a>
                    @if ($business->phone)
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $business->phone) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <x-icon name="phone" class="size-4" /> Llamar
                        </a>
                    @endif
                </div>
            @endif

            @if ($appointment->canBeRescheduledByCustomer())
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-6 py-4">
                    <p class="text-sm text-slate-600">¿No puedes a esa hora?</p>
                    <a href="{{ $appointment->rescheduleUrl() }}" class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">
                        <x-icon name="clock" class="size-4" /> Cambiar horario
                    </a>
                </div>
            @endif

            @if ($appointment->canBeCancelledByCustomer())
                <form method="POST" action="{{ $cancelUrl }}" x-data x-ref="cancelForm" class="border-t border-slate-100 bg-slate-50 px-6 py-4">
                    @csrf
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-slate-500">Puedes cambiarla o cancelarla hasta {{ $business->cancellation_notice_hours }} horas antes.</p>
                        <button type="button"
                                x-on:click="$dispatch('confirm-action', {
                                    title: '¿Cancelar tu cita?',
                                    message: 'Liberaremos el horario y le avisaremos al negocio. Esta acción no se puede deshacer.',
                                    confirmLabel: 'Sí, cancelar',
                                    action: () => $refs.cancelForm.submit(),
                                })"
                                class="rounded-lg border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Cancelar mi cita</button>
                    </div>
                </form>
            @elseif ($appointment->status->isActive())
                <p class="border-t border-slate-100 bg-slate-50 px-6 py-4 text-sm text-slate-600">
                    Ya pasó el plazo para cambiar o cancelar en línea.
                    @if ($business->phone)
                        Llama al {{ $business->phone }} si necesitas cambiarla.
                    @endif
                </p>
            @endif
        </div>

        <a href="{{ route('booking', $business) }}" class="mt-6 inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline">
            Reservar otra cita en {{ $business->name }} <x-icon name="arrow-right" class="size-4" />
        </a>
    </div>
</x-layouts::app>
