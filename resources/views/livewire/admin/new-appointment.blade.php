<div>
    @if ($open)
        @php
            $service = $this->selectedService;
            $newTime = $this->selectedSlot;
        @endphp
        <div class="fixed inset-0 z-40" role="dialog" aria-modal="true" aria-labelledby="new-appointment-title"
             x-data x-on:keydown.escape.window="$wire.close()" x-init="$nextTick(() => $refs.close.focus())">
            <div class="absolute inset-0 motion-safe:animate-fade-in bg-slate-900/40 backdrop-blur-[2px]" wire:click="close"></div>
            <form wire:submit="save" class="absolute inset-y-0 right-0 flex w-full max-w-md motion-safe:animate-slide-in flex-col bg-white shadow-2xl">
                <header class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <h2 id="new-appointment-title" class="text-xl font-bold text-slate-900">Nueva cita</h2>
                        <p class="text-sm text-slate-500">Para una llamada o alguien que llega sin cita. Queda confirmada.</p>
                    </div>
                    <button type="button" x-ref="close" wire:click="close" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Cerrar">
                        <x-icon name="x" class="size-5" />
                    </button>
                </header>

                <div class="flex-1 space-y-6 overflow-y-auto px-6 py-6">
                    @if ($this->services->isEmpty())
                        <p class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                            Primero crea un servicio en <a href="{{ route('admin.services') }}" class="font-medium text-teal-700 hover:underline">Servicios</a>.
                        </p>
                    @else
                        <div>
                            <label for="new-service" class="text-sm font-medium text-slate-700">Servicio</label>
                            <select id="new-service" wire:change="selectService($event.target.value)"
                                    class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/30 focus:outline-none">
                                @foreach ($this->services as $option)
                                    <option value="{{ $option->id }}" @selected($option->id === $serviceId)>{{ $option->name }} · {{ $option->formattedDuration() }} · {{ $option->formattedPrice() }}</option>
                                @endforeach
                            </select>
                            @error('serviceId') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <p class="text-sm font-medium text-slate-700">Día y hora</p>
                            @error('startsAt')
                                <p class="mt-2 flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800" role="alert">
                                    <x-icon name="alert" class="mt-0.5 size-4" /> {{ $message }}
                                </p>
                            @enderror
                            @if (collect($this->availability)->filter()->isEmpty())
                                <p class="mt-2 rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">No hay horarios libres para este servicio.</p>
                            @else
                                <x-time-picker :business="$this->business" :availability="$this->availability" :date="$date" :selected="$startsAt" class="mt-2" wire:key="picker-{{ $serviceId }}" />
                            @endif
                        </div>

                        <div class="grid gap-4 border-t border-slate-100 pt-6">
                            <x-field name="customerName" label="Nombre del cliente" wire:model="customerName" autocomplete="off" required />
                            <x-field name="customerPhone" type="tel" label="Teléfono (opcional)" wire:model="customerPhone" autocomplete="off" />
                            <x-field name="customerEmail" type="email" label="Correo (opcional)" wire:model="customerEmail" autocomplete="off"
                                     hint="Si lo escribes, le enviamos la confirmación, el recordatorio y el enlace para cambiar o cancelar." />
                            <x-field name="notes" type="textarea" label="Notas (opcional)" wire:model="notes" />
                        </div>
                    @endif
                </div>

                <footer class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <p class="min-w-0 text-sm">
                        <span class="block truncate text-slate-500">{{ $service?->name ?? 'Sin servicio' }}</span>
                        <span class="block truncate font-semibold text-slate-900 first-letter:uppercase">{{ $newTime ? $newTime->translatedFormat('D j M, H:i') : 'Elige un horario' }}</span>
                    </p>
                    <button type="submit" @disabled(! $newTime) wire:loading.attr="disabled" wire:target="save"
                            class="shrink-0 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500 disabled:cursor-not-allowed disabled:opacity-50">Crear cita</button>
                </footer>
            </form>
        </div>
    @endif
</div>
