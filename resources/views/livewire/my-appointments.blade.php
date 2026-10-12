<div class="mx-auto max-w-3xl">
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Mis citas</h1>

    <h2 class="mt-8 text-sm font-semibold uppercase tracking-wide text-slate-500">Próximas</h2>
    <div class="mt-3 space-y-3">
        @forelse ($this->upcoming as $appointment)
            <div wire:key="upcoming-{{ $appointment->id }}" class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex w-14 shrink-0 flex-col items-center rounded-xl bg-teal-50 py-1.5 ring-1 ring-teal-600/10">
                        <span class="text-xs font-semibold text-teal-700 uppercase">{{ rtrim($appointment->localStartsAt()->translatedFormat('M'), '.') }}</span>
                        <span class="text-xl leading-6 font-bold text-slate-900">{{ $appointment->localStartsAt()->day }}</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-slate-900">{{ $appointment->service->name }}</p>
                            <x-status-badge :status="$appointment->status" />
                        </div>
                        <p class="text-sm text-slate-600">{{ $appointment->business->name }}</p>
                        <p class="text-sm text-slate-600 first-letter:uppercase">{{ $appointment->whenLabel() }}</p>
                    </div>
                </div>
                @if ($appointment->canBeCancelledByCustomer())
                    <div class="flex gap-2 self-start sm:self-auto">
                        <a href="{{ $appointment->rescheduleUrl() }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cambiar horario</a>
                        <button type="button"
                                x-on:click="$dispatch('confirm-action', {
                                    title: '¿Cancelar esta cita?',
                                    message: 'Liberaremos el horario y le avisaremos al negocio.',
                                    confirmLabel: 'Sí, cancelar',
                                    action: () => $wire.cancel({{ $appointment->id }}),
                                })"
                                class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">Cancelar</button>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                No tienes citas próximas.
                @if ($slug = \App\Models\Business::query()->value('slug'))
                    <a href="{{ route('booking', $slug) }}" class="font-medium text-teal-700 hover:underline">Reserva una</a>.
                @endif
            </div>
        @endforelse
    </div>

    @if ($this->past->isNotEmpty())
        <h2 class="mt-10 text-sm font-semibold uppercase tracking-wide text-slate-500">Anteriores</h2>
        <ul class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white text-sm">
            @foreach ($this->past as $appointment)
                <li wire:key="past-{{ $appointment->id }}" class="flex items-center justify-between gap-4 px-4 py-3">
                    <span>
                        <span class="font-medium text-slate-900">{{ $appointment->service->name }}</span>
                        <span class="text-slate-500">· {{ $appointment->business->name }} · {{ $appointment->localStartsAt()->translatedFormat('j M Y, H:i') }}</span>
                    </span>
                    <x-status-badge :status="$appointment->status" />
                </li>
            @endforeach
        </ul>
    @endif
</div>
