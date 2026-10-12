@php
    $minutesOf = fn (string $time) => (int) substr($time, 0, 2) * 60 + (int) substr($time, 3, 2);
@endphp

<div>
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Horario</h1>
    <p class="text-sm text-slate-500">Los clientes solo ven horarios libres dentro de estas horas. Zona horaria: {{ str_replace('_', ' ', $this->business->timezone) }}.</p>


    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Horario semanal</h2>
            <ul class="divide-y divide-slate-100">
                @foreach ($this->schedulesByDay as $row)
                    <li wire:key="day-{{ $row['day'] }}" class="flex flex-col gap-2 px-5 py-3 sm:flex-row sm:items-center">
                        <span class="w-28 shrink-0 text-sm font-medium text-slate-700">{{ $row['name'] }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap gap-2">
                                @forelse ($row['windows'] as $window)
                                    <span wire:key="window-{{ $window->id }}" class="inline-flex items-center gap-1 rounded-full bg-teal-50 py-1 pr-1 pl-3 text-sm text-teal-900 ring-1 ring-teal-600/20">
                                        {{ $window->label() }}
                                        @if ($window->crossesMidnight())
                                            <span class="text-xs text-teal-700">(+1 día)</span>
                                        @endif
                                        <button type="button" wire:click="removeWindow({{ $window->id }})" class="flex size-5 items-center justify-center rounded-full text-teal-700 hover:bg-teal-100" aria-label="Quitar {{ $window->label() }} del {{ $row['name'] }}"><x-icon name="x" class="size-3.5" /></button>
                                    </span>
                                @empty
                                    <span class="text-sm text-slate-400">Cerrado</span>
                                @endforelse
                            </div>
                            <div class="relative mt-2 h-1.5 rounded-full bg-slate-100" aria-hidden="true">
                                @foreach ($row['windows'] as $window)
                                    @php
                                        $opens = $minutesOf($window->opensAtTime());
                                        $closes = $window->crossesMidnight() ? 1440 : $minutesOf($window->closesAtTime());
                                    @endphp
                                    <span class="absolute inset-y-0 rounded-full bg-teal-500" style="left: {{ $opens / 14.4 }}%; width: {{ ($closes - $opens) / 14.4 }}%"></span>
                                @endforeach
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <form wire:submit="addWindow" class="border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                <p class="text-sm font-medium text-slate-700">Agregar horario</p>
                <div class="mt-2 flex flex-wrap items-end gap-3">
                    <div>
                        <label for="newDay" class="sr-only">Día</label>
                        <select id="newDay" wire:model="newDay" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            @foreach ([1, 2, 3, 4, 5, 6, 0] as $day)
                                <option value="{{ $day }}">{{ \App\Models\Schedule::dayName($day) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="newOpensAt" class="block text-xs text-slate-500">Abre</label>
                        <input id="newOpensAt" type="time" wire:model="newOpensAt" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="newClosesAt" class="block text-xs text-slate-500">Cierra</label>
                        <input id="newClosesAt" type="time" wire:model="newClosesAt" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Agregar</button>
                </div>
                @foreach (['newDay', 'newOpensAt', 'newClosesAt'] as $field)
                    @error($field)
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                @endforeach
                <p class="mt-2 text-xs text-slate-500">Puedes agregar varios horarios por día (por ejemplo, 9–14 y 16–20). Si la hora de cierre es menor que la de apertura, el horario termina al día siguiente.</p>
            </form>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Días cerrados</h2>
            <form wire:submit="blockDate" class="space-y-3 px-5 py-4">
                <x-field name="blockedDate" type="date" label="Fecha" wire:model="blockedDate" required />
                <x-field name="blockedReason" label="Motivo (opcional)" wire:model="blockedReason" placeholder="Ej. Día festivo" />
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Bloquear día</button>
            </form>
            <ul class="divide-y divide-slate-100 border-t border-slate-100">
                @forelse ($this->blockedDates as $blocked)
                    <li wire:key="blocked-{{ $blocked->id }}" class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <span>
                            <span class="font-medium text-slate-900 first-letter:uppercase">{{ $blocked->date->translatedFormat('l j \d\e F') }}</span>
                            @if ($blocked->reason)
                                <span class="block text-slate-500">{{ $blocked->reason }}</span>
                            @endif
                        </span>
                        <button type="button" wire:click="unblockDate({{ $blocked->id }})" class="rounded-lg px-2 py-1 font-medium text-slate-600 hover:bg-slate-100">Quitar</button>
                    </li>
                @empty
                    <li class="px-5 py-4 text-sm text-slate-400">No hay días bloqueados.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
