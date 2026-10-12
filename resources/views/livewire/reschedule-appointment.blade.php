@php
    $business = $appointment->business;
    $newTime = $this->selectedSlot;
@endphp

<div class="mx-auto max-w-xl">
    <a href="{{ $appointment->manageUrl() }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-teal-700">
        <x-icon name="arrow-left" class="size-4" /> Tu cita
    </a>
    <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">Cambiar horario</h1>
    <p class="text-sm text-slate-500">{{ $appointment->service->name }}{{ $appointment->staffMember ? ' con '.$appointment->staffMember->firstName() : '' }} en {{ $business->name }} · {{ $appointment->service->formattedDuration() }}</p>

    <div class="mt-5 flex items-center gap-3 rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-700">
        <x-icon name="calendar" class="size-5 text-slate-500" />
        <p>Ahora: <span class="font-semibold text-slate-900 first-letter:uppercase">{{ $appointment->whenLabel() }}</span></p>
    </div>

    @if (! $appointment->canBeRescheduledByCustomer())
        <p class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            Esta cita ya no se puede cambiar en línea.
            @if ($business->phone)
                Llama al {{ $business->phone }} para buscar otro horario.
            @endif
        </p>
    @else
        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="font-semibold text-slate-900">Elige el nuevo día y hora</h2>

            @error('slot')
                <p class="mt-4 flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800" role="alert">
                    <x-icon name="alert" class="mt-0.5 size-4" /> {{ $message }}
                </p>
            @enderror

            @if (collect($this->availability)->filter()->isEmpty())
                <p class="mt-5 rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                    No hay otros horarios libres en los próximos {{ $business->booking_window_days }} días.
                </p>
            @else
                <x-time-picker :business="$business" :availability="$this->availability" :date="$date" :selected="$startsAt" class="mt-5" />
            @endif
        </section>

        <div class="sticky bottom-4 mt-6 flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur-md">
            <p class="min-w-0 text-sm">
                <span class="block text-slate-500">Nuevo horario</span>
                <span class="block truncate font-semibold text-slate-900 first-letter:uppercase">{{ $newTime ? $newTime->translatedFormat('l j \d\e F, H:i') : 'Sin elegir' }}</span>
            </p>
            <button type="button" wire:click="save" @disabled(! $newTime) wire:loading.attr="disabled" wire:target="save"
                    class="shrink-0 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-500 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="save">Cambiar mi cita</span>
                <span wire:loading wire:target="save">Guardando…</span>
            </button>
        </div>
    @endif
</div>
