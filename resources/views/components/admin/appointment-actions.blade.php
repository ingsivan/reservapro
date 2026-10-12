@props(['appointment', 'size' => 'sm'])

@php
    use App\Enums\AppointmentStatus;

    $base = $size === 'lg' ? 'px-4 py-2 text-sm' : 'px-3 py-1.5 text-sm';
@endphp

@if ($appointment->status->isActive())
    <div {{ $attributes->class('flex flex-wrap gap-2') }}>
        @if ($appointment->hasStarted())
            <button type="button" wire:click="markCompleted({{ $appointment->id }})" class="{{ $base }} inline-flex items-center gap-1.5 rounded-lg bg-sky-600 font-semibold text-white hover:bg-sky-500">
                <x-icon name="check" class="size-4" /> Atendida
            </button>
            <button type="button" wire:click="markNoShow({{ $appointment->id }})" class="{{ $base }} rounded-lg border border-slate-300 bg-white font-semibold text-slate-700 hover:bg-slate-50">No asistió</button>
        @else
            @if ($appointment->status === AppointmentStatus::Pending)
                <button type="button" wire:click="confirm({{ $appointment->id }})" class="{{ $base }} inline-flex items-center gap-1.5 rounded-lg bg-teal-600 font-semibold text-white hover:bg-teal-500">
                    <x-icon name="check" class="size-4" /> Confirmar
                </button>
            @endif
            <button type="button" wire:click="startRescheduling({{ $appointment->id }})" class="{{ $base }} inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white font-semibold text-slate-700 hover:bg-slate-50">
                <x-icon name="clock" class="size-4" /> Reprogramar
            </button>
            <button type="button"
                    x-on:click="$dispatch('confirm-action', {
                        title: @js('¿Cancelar la cita de '.$appointment->customer_name.'?'),
                        message: 'Liberaremos el horario y le avisaremos al cliente por correo.',
                        confirmLabel: 'Cancelar cita',
                        action: () => $wire.cancel({{ $appointment->id }}),
                    })"
                    class="{{ $base }} rounded-lg border border-rose-200 bg-white font-semibold text-rose-700 hover:bg-rose-50">Cancelar</button>
        @endif
    </div>
@endif
