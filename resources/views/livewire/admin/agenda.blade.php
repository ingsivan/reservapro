@php
    use App\Enums\AppointmentStatus;

    $grid = $this->gridHours;
    $hourHeight = 48;
    $gridMinutes = ($grid['end'] - $grid['start']) * 60;
    $today = $this->business->now()->toDateString();
    $blockColors = [
        'pending' => 'border-amber-400 bg-amber-50 text-amber-900',
        'confirmed' => 'border-teal-500 bg-teal-50 text-teal-900',
        'completed' => 'border-sky-400 bg-sky-50 text-sky-900',
        'no_show' => 'border-slate-300 bg-slate-100 text-slate-500',
        'cancelled' => 'border-rose-300 bg-rose-50/60 text-rose-400 line-through',
    ];
@endphp

<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Agenda</h1>
            <p class="text-sm text-slate-500 first-letter:uppercase">
                Semana del {{ $this->weekDays[0]->translatedFormat('j \d\e F') }} al {{ $this->weekDays[6]->translatedFormat('j \d\e F \d\e Y') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="previousWeek" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" aria-label="Semana anterior">←</button>
            <button type="button" wire:click="goToToday" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Hoy</button>
            <button type="button" wire:click="nextWeek" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" aria-label="Semana siguiente">→</button>
        </div>
    </div>

    <dl class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['Citas de hoy', $this->stats['today']],
            ['Por confirmar', $this->stats['pending']],
            ['Citas esta semana', $this->stats['week']],
            ['Ingresos de la semana', $this->stats['revenue']],
        ] as [$label, $value])
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <dt class="text-xs font-medium text-slate-500">{{ $label }}</dt>
                <dd class="mt-1 text-2xl font-bold text-slate-900">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    <x-notice :message="$notice" :error="$noticeIsError" class="mt-6" />

    {{-- Weekly calendar --}}
    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="min-w-[44rem]">
            <div class="grid grid-cols-[3.5rem_repeat(7,1fr)] border-b border-slate-200">
                <div></div>
                @foreach ($this->weekDays as $day)
                    @php $key = $day->toDateString(); @endphp
                    <button type="button" wire:click="selectDate('{{ $key }}')" wire:key="head-{{ $key }}"
                            @class([
                                'flex flex-col items-center border-l border-slate-100 py-2 text-sm',
                                'bg-teal-50' => $key === $date,
                                'hover:bg-slate-50' => $key !== $date,
                            ])>
                        <span class="text-xs uppercase text-slate-500">{{ $day->translatedFormat('D') }}</span>
                        <span @class(['mt-0.5 flex size-8 items-center justify-center rounded-full text-base font-semibold', 'bg-teal-600 text-white' => $key === $today, 'text-slate-900' => $key !== $today])>{{ $day->day }}</span>
                    </button>
                @endforeach
            </div>

            <div class="relative grid grid-cols-[3.5rem_repeat(7,1fr)]" style="height: {{ ($grid['end'] - $grid['start']) * $hourHeight }}px">
                <div class="relative">
                    @for ($hour = $grid['start']; $hour < $grid['end']; $hour++)
                        <span class="absolute right-2 -translate-y-1/2 text-xs text-slate-400" style="top: {{ ($hour - $grid['start']) * $hourHeight }}px">{{ $hour > $grid['start'] ? sprintf('%02d:00', $hour) : '' }}</span>
                    @endfor
                </div>
                @foreach ($this->weekDays as $day)
                    @php $key = $day->toDateString(); @endphp
                    <div wire:key="col-{{ $key }}" @class(['relative border-l border-slate-100', 'bg-teal-50/40' => $key === $date])>
                        @for ($hour = $grid['start'] + 1; $hour < $grid['end']; $hour++)
                            <div class="absolute inset-x-0 border-t border-slate-100" style="top: {{ ($hour - $grid['start']) * $hourHeight }}px"></div>
                        @endfor
                        @foreach ($this->weekAppointments->get($key, []) as $appointment)
                            @php
                                $start = $appointment->localStartsAt();
                                $offset = max(0, ($start->hour - $grid['start']) * 60 + $start->minute);
                                $length = min($appointment->starts_at->diffInMinutes($appointment->ends_at), $gridMinutes - $offset);
                            @endphp
                            <button type="button" wire:click="selectDate('{{ $key }}')" wire:key="block-{{ $appointment->id }}"
                                    class="absolute inset-x-1 truncate rounded-md border-l-4 px-1.5 py-0.5 text-left text-xs leading-4 {{ $blockColors[$appointment->status->value] }}"
                                    style="top: {{ $offset / 60 * $hourHeight }}px; height: {{ max(18, $length / 60 * $hourHeight - 2) }}px"
                                    title="{{ $start->format('H:i') }} · {{ $appointment->service->name }} · {{ $appointment->customer_name }}">
                                <span class="font-semibold">{{ $start->format('H:i') }}</span> {{ $appointment->customer_name }}
                            </button>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Selected day --}}
    <section class="mt-8">
        <h2 class="text-lg font-semibold text-slate-900 first-letter:uppercase">{{ $this->selectedDate->translatedFormat('l j \d\e F') }}</h2>

        <div class="mt-3 space-y-3">
            @forelse ($this->dayAppointments as $appointment)
                <article wire:key="appointment-{{ $appointment->id }}" class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex gap-4">
                        <div class="w-14 shrink-0 text-center">
                            <p class="text-lg font-bold text-slate-900">{{ $appointment->localStartsAt()->format('H:i') }}</p>
                            <p class="text-xs text-slate-500">{{ $appointment->localEndsAt()->format('H:i') }}</p>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-slate-900">{{ $appointment->customer_name }}</p>
                                <x-status-badge :status="$appointment->status" />
                            </div>
                            <p class="text-sm text-slate-600">{{ $appointment->service->name }} · {{ $appointment->formattedPrice() }}</p>
                            <p class="truncate text-sm text-slate-500">
                                <a href="mailto:{{ $appointment->customer_email }}" class="hover:underline">{{ $appointment->customer_email }}</a>
                                @if ($appointment->customer_phone)
                                    · {{ $appointment->customer_phone }}
                                @endif
                            </p>
                            @if ($appointment->notes)
                                <p class="mt-1 text-sm italic text-slate-500">“{{ $appointment->notes }}”</p>
                            @endif
                        </div>
                    </div>

                    @if ($appointment->status->isActive())
                        <div class="flex flex-wrap gap-2 sm:justify-end">
                            @if ($appointment->hasStarted())
                                <button type="button" wire:click="markCompleted({{ $appointment->id }})" class="rounded-lg bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-500">Atendida</button>
                                <button type="button" wire:click="markNoShow({{ $appointment->id }})" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">No asistió</button>
                            @else
                                @if ($appointment->status === AppointmentStatus::Pending)
                                    <button type="button" wire:click="confirm({{ $appointment->id }})" class="rounded-lg bg-teal-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-teal-500">Confirmar</button>
                                @endif
                                <button type="button" wire:click="cancel({{ $appointment->id }})" wire:confirm="¿Cancelar la cita de {{ $appointment->customer_name }}? Le avisaremos por correo."
                                        class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">Cancelar</button>
                            @endif
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                    No hay citas este día.
                </div>
            @endforelse
        </div>
    </section>
</div>
