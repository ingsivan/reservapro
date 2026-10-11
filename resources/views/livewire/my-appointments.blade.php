<div class="mx-auto max-w-3xl">
    <h1 class="text-2xl font-bold text-slate-900">Mis citas</h1>

    <x-notice :message="$notice" :error="$noticeIsError" class="mt-4" />

    <h2 class="mt-8 text-sm font-semibold uppercase tracking-wide text-slate-500">Próximas</h2>
    <div class="mt-3 space-y-3">
        @forelse ($this->upcoming as $appointment)
            <div wire:key="upcoming-{{ $appointment->id }}" class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="font-semibold text-slate-900">{{ $appointment->service->name }}</p>
                        <x-status-badge :status="$appointment->status" />
                    </div>
                    <p class="text-sm text-slate-600">{{ $appointment->business->name }}</p>
                    <p class="text-sm text-slate-600 first-letter:uppercase">{{ $appointment->whenLabel() }}</p>
                </div>
                @if ($appointment->canBeCancelledByCustomer())
                    <button type="button" wire:click="cancel({{ $appointment->id }})" wire:confirm="¿Seguro que quieres cancelar esta cita?"
                            class="self-start rounded-lg border border-rose-300 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50 sm:self-auto">Cancelar</button>
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
