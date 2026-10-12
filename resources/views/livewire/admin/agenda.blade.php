@php
    use App\Enums\AppointmentStatus;

    $grid = $this->gridHours;
    $lanes = $this->calendarLanes;
    $hasTeam = $this->staffMembers->isNotEmpty();
    $hourHeight = 52;
    $gridMinutes = ($grid['end'] - $grid['start']) * 60;
    $now = $this->business->now();
    $today = $now->toDateString();
    $nowOffset = ($now->hour - $grid['start']) * 60 + $now->minute;
    $blockColors = [
        'pending' => 'border-amber-400 bg-amber-50 text-amber-900 hover:bg-amber-100',
        'confirmed' => 'border-teal-500 bg-teal-50 text-teal-900 hover:bg-teal-100',
        'completed' => 'border-sky-400 bg-sky-50 text-sky-900 hover:bg-sky-100',
        'no_show' => 'border-slate-300 bg-slate-100 text-slate-500 hover:bg-slate-200',
        'cancelled' => 'border-rose-300 bg-rose-50/70 text-rose-400 line-through hover:bg-rose-50',
    ];
    $statCards = [
        ['Citas de hoy', $this->stats['today'], 'calendar-check', 'bg-teal-50 text-teal-700', null],
        ['Por confirmar', $this->stats['pending'], 'hourglass', 'bg-amber-50 text-amber-700', AppointmentStatus::Pending->value],
        ['Citas esta semana', $this->stats['week'], 'users', 'bg-sky-50 text-sky-700', null],
        ['Ingresos de la semana', $this->stats['revenue'], 'wallet', 'bg-emerald-50 text-emerald-700', null],
    ];
@endphp

<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-teal-700 first-letter:uppercase">{{ $now->translatedFormat('l j \d\e F') }}</p>
            <h1 class="mt-0.5 text-2xl font-bold tracking-tight text-slate-900">Agenda</h1>
        </div>
        <div class="flex items-center gap-2">
            <span class="mr-1 hidden text-sm text-slate-500 sm:block first-letter:uppercase">
                {{ $this->weekDays[0]->translatedFormat('j M') }} – {{ $this->weekDays[6]->translatedFormat('j M Y') }}
            </span>
            <div class="flex overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm">
                <button type="button" wire:click="previousWeek" class="px-2.5 py-2 text-slate-600 hover:bg-slate-50" aria-label="Semana anterior"><x-icon name="chevron-left" class="size-4" /></button>
                <button type="button" wire:click="goToToday" class="border-x border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hoy</button>
                <button type="button" wire:click="nextWeek" class="px-2.5 py-2 text-slate-600 hover:bg-slate-50" aria-label="Semana siguiente"><x-icon name="chevron-right" class="size-4" /></button>
            </div>
            <button type="button" x-on:click="$dispatch('new-appointment', { date: @js($date) })"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500 sm:px-4">
                <x-icon name="plus" class="size-4" /> <span class="max-[400px]:sr-only">Nueva cita</span>
            </button>
        </div>
    </div>

    {{-- Headline numbers --}}
    <dl class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($statCards as [$label, $value, $icon, $iconClasses, $filter])
            <div class="relative flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <span class="hidden size-10 shrink-0 items-center justify-center rounded-lg min-[400px]:flex {{ $iconClasses }}"><x-icon :name="$icon" class="size-5" /></span>
                <div class="min-w-0">
                    <dt class="text-xs leading-4 font-medium text-slate-500">{{ $label }}</dt>
                    <dd class="truncate text-lg font-bold text-slate-900 tabular-nums sm:text-xl">{{ $value }}</dd>
                </div>
                @if ($filter && $value)
                    <button type="button" wire:click="filterByStatus('{{ $filter }}')" class="absolute inset-0 rounded-xl hover:ring-2 hover:ring-amber-300" aria-label="Ver solo las citas por confirmar"></button>
                @endif
            </div>
        @endforeach
    </dl>

    {{-- Status filter --}}
    <div class="no-scrollbar mt-6 flex items-center gap-2 overflow-x-auto" role="group" aria-label="Filtrar por estado">
        @foreach (['' => 'Todas', 'pending' => 'Por confirmar', 'confirmed' => 'Confirmadas', 'completed' => 'Atendidas', 'no_show' => 'No asistieron', 'cancelled' => 'Canceladas'] as $value => $label)
            <button type="button" wire:click="filterByStatus('{{ $value }}')" aria-pressed="{{ $statusFilter === $value ? 'true' : 'false' }}"
                    @class([
                        'rounded-full px-3 py-1 text-sm font-medium whitespace-nowrap ring-1 transition ring-inset',
                        'bg-slate-900 text-white ring-slate-900' => $statusFilter === $value,
                        'bg-white text-slate-600 ring-slate-200 hover:ring-slate-300' => $statusFilter !== $value,
                    ])>{{ $label }}</button>
        @endforeach
    </div>

    {{-- Staff filter --}}
    @if ($hasTeam)
        <div class="no-scrollbar mt-3 flex items-center gap-2 overflow-x-auto" role="group" aria-label="Filtrar por persona">
            <button type="button" wire:click="filterByStaff('')" aria-pressed="{{ $staffFilter === '' ? 'true' : 'false' }}"
                    @class([
                        'rounded-full px-3 py-1 text-sm font-medium whitespace-nowrap ring-1 transition ring-inset',
                        'bg-slate-900 text-white ring-slate-900' => $staffFilter === '',
                        'bg-white text-slate-600 ring-slate-200 hover:ring-slate-300' => $staffFilter !== '',
                    ])>Todo el equipo</button>
            @foreach ($this->staffMembers as $person)
                <button type="button" wire:click="filterByStaff('{{ $person->id }}')" wire:key="staff-filter-{{ $person->id }}" aria-pressed="{{ $staffFilter === (string) $person->id ? 'true' : 'false' }}"
                        @class([
                            'inline-flex items-center gap-1.5 rounded-full py-1 pr-3 pl-1.5 text-sm font-medium whitespace-nowrap ring-1 transition ring-inset',
                            'bg-slate-900 text-white ring-slate-900' => $staffFilter === (string) $person->id,
                            'bg-white text-slate-600 ring-slate-200 hover:ring-slate-300' => $staffFilter !== (string) $person->id,
                        ])>
                    <x-staff-avatar :staff-member="$person" class="size-5 text-[9px]" /> {{ $person->firstName() }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- Day picker (mobile) --}}
    <div class="mt-4 grid grid-cols-7 gap-1 md:hidden">
        @foreach ($this->weekDays as $day)
            @php
                $key = $day->toDateString();
                $count = $this->visibleAppointments->get($key)?->count() ?? 0;
            @endphp
            <button type="button" wire:click="selectDate('{{ $key }}')" wire:key="pick-{{ $key }}"
                    @class([
                        'flex flex-col items-center rounded-lg py-2 text-xs',
                        'bg-teal-600 text-white' => $key === $date,
                        'bg-white text-slate-600 ring-1 ring-slate-200' => $key !== $date,
                    ])>
                <span class="uppercase">{{ mb_substr(rtrim($day->translatedFormat('D'), '.'), 0, 2) }}</span>
                <span @class(['text-base font-bold', 'text-teal-700' => $key === $today && $key !== $date])>{{ $day->day }}</span>
                <span @class(['mt-0.5 size-1.5 rounded-full', 'bg-current' => $count, 'bg-transparent' => ! $count])></span>
            </button>
        @endforeach
    </div>

    {{-- Weekly calendar (tablet and desktop) --}}
    <div class="mt-4 hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
        <div class="grid grid-cols-[3.5rem_repeat(7,1fr)] border-b border-slate-200">
            <div></div>
            @foreach ($this->weekDays as $day)
                @php
                    $key = $day->toDateString();
                @endphp
                <button type="button" wire:click="selectDate('{{ $key }}')" wire:key="head-{{ $key }}"
                        @class([
                            'flex flex-col items-center border-l border-slate-100 py-2.5 text-sm transition',
                            'bg-teal-50' => $key === $date,
                            'hover:bg-slate-50' => $key !== $date,
                        ])>
                    <span @class(['text-xs font-medium uppercase', 'text-teal-700' => $key === $today, 'text-slate-500' => $key !== $today])>{{ rtrim($day->translatedFormat('D'), '.') }}</span>
                    <span @class(['mt-0.5 flex size-8 items-center justify-center rounded-full text-base font-semibold', 'bg-teal-600 text-white' => $key === $today, 'text-slate-900' => $key !== $today])>{{ $day->day }}</span>
                </button>
            @endforeach
        </div>

        <div class="relative grid grid-cols-[3.5rem_repeat(7,1fr)]" style="height: {{ ($grid['end'] - $grid['start']) * $hourHeight }}px">
            <div class="relative">
                @for ($hour = $grid['start'] + 1; $hour < $grid['end']; $hour++)
                    <span class="absolute right-2 -translate-y-1/2 text-xs text-slate-400 tabular-nums" style="top: {{ ($hour - $grid['start']) * $hourHeight }}px">{{ sprintf('%02d:00', $hour) }}</span>
                @endfor
            </div>
            @foreach ($this->weekDays as $day)
                @php
                    $key = $day->toDateString();
                @endphp
                <div wire:key="col-{{ $key }}" @class(['relative border-l border-slate-100', 'bg-teal-50/40' => $key === $date])>
                    @for ($hour = $grid['start'] + 1; $hour < $grid['end']; $hour++)
                        <div class="absolute inset-x-0 border-t border-slate-100" style="top: {{ ($hour - $grid['start']) * $hourHeight }}px"></div>
                    @endfor
                    @foreach ($this->visibleAppointments->get($key, []) as $appointment)
                        @php
                            $start = $appointment->localStartsAt();
                            $offset = max(0, ($start->hour - $grid['start']) * 60 + $start->minute);
                            $length = min($appointment->starts_at->diffInMinutes($appointment->ends_at), $gridMinutes - $offset);
                            $height = max(20, $length / 60 * $hourHeight - 2);
                            ['lane' => $lane, 'lanes' => $laneCount] = $lanes[$appointment->id] ?? ['lane' => 0, 'lanes' => 1];
                        @endphp
                        <button type="button" wire:click="viewAppointment({{ $appointment->id }})" wire:key="block-{{ $appointment->id }}"
                                class="absolute overflow-hidden rounded-md border-l-[3px] px-1.5 py-0.5 text-left text-xs leading-4 transition {{ $blockColors[$appointment->status->value] }}"
                                style="top: {{ $offset / 60 * $hourHeight + 1 }}px; height: {{ $height }}px; left: calc(4px + (100% - 8px) * {{ $lane }} / {{ $laneCount }}); width: calc((100% - 8px) / {{ $laneCount }} - {{ $laneCount > 1 ? 2 : 0 }}px)"
                                title="{{ $start->format('H:i') }} · {{ $appointment->service->name }} · {{ $appointment->customer_name }}{{ $appointment->staffMember ? ' · '.$appointment->staffMember->name : '' }}">
                            <span class="flex items-center gap-1 truncate">
                                @if ($appointment->staffMember)
                                    <span class="size-1.5 shrink-0 rounded-full {{ $appointment->staffMember->dotClasses() }}" aria-hidden="true"></span>
                                @endif
                                @if ($laneCount < 3)
                                    <span class="truncate"><span class="font-semibold tabular-nums">{{ $start->format('H:i') }}</span>{{ $laneCount === 1 ? ' '.$appointment->customer_name : '' }}</span>
                                @endif
                            </span>
                            @if ($height >= 34 && $laneCount === 1)
                                <span class="block truncate opacity-75">{{ $appointment->service->name }}</span>
                            @endif
                        </button>
                    @endforeach
                    @if ($key === $today && $nowOffset > 0 && $nowOffset < $gridMinutes)
                        <div class="pointer-events-none absolute inset-x-0 z-10 flex items-center" style="top: {{ $nowOffset / 60 * $hourHeight }}px" aria-hidden="true">
                            <span class="-ml-1 size-2.5 rounded-full bg-rose-500"></span>
                            <span class="h-0.5 flex-1 bg-rose-500"></span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Selected day --}}
    <section class="mt-8">
        <div class="flex items-baseline justify-between gap-4">
            <h2 class="text-lg font-semibold text-slate-900 first-letter:uppercase">{{ $this->selectedDate->translatedFormat('l j \d\e F') }}</h2>
            <p class="text-sm text-slate-500">{{ trans_choice(':count cita|:count citas', $this->dayAppointments->count()) }}</p>
        </div>

        <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            @forelse ($this->dayAppointments as $appointment)
                <article wire:key="appointment-{{ $appointment->id }}" class="flex flex-col gap-3 border-b border-slate-100 p-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" wire:click="viewAppointment({{ $appointment->id }})" class="flex min-w-0 flex-1 gap-4 text-left">
                        <div class="w-14 shrink-0 text-center">
                            <p class="text-lg font-bold text-slate-900 tabular-nums">{{ $appointment->localStartsAt()->format('H:i') }}</p>
                            <p class="text-xs text-slate-500 tabular-nums">{{ $appointment->localEndsAt()->format('H:i') }}</p>
                        </div>
                        <div class="min-w-0 border-l border-slate-100 pl-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-slate-900">{{ $appointment->customer_name }}</p>
                                <x-status-badge :status="$appointment->status" />
                            </div>
                            <p class="text-sm text-slate-600">{{ $appointment->service->name }} · {{ $appointment->formattedPrice() }}</p>
                            @if ($appointment->staffMember)
                                <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                                    <x-staff-avatar :staff-member="$appointment->staffMember" class="size-5 text-[9px]" /> Con {{ $appointment->staffMember->name }}
                                </p>
                            @endif
                            @if ($appointment->notes)
                                <p class="mt-1 truncate text-sm text-slate-500 italic">“{{ $appointment->notes }}”</p>
                            @endif
                        </div>
                    </button>
                    <x-admin.appointment-actions :appointment="$appointment" class="sm:justify-end" />
                </article>
            @empty
                <div class="flex flex-col items-center px-6 py-10 text-center">
                    <span class="flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><x-icon name="calendar" class="size-6" /></span>
                    <p class="mt-3 font-medium text-slate-900">{{ $statusFilter ? 'No hay citas con ese estado este día' : 'No hay citas este día' }}</p>
                    <p class="mt-1 text-sm text-slate-500">Comparte tu página de reservas o agenda una cita por teléfono.</p>
                    <button type="button" x-on:click="$dispatch('new-appointment', { date: @js($date) })" class="mt-4 inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <x-icon name="plus" class="size-4" /> Agendar en este día
                    </button>
                </div>
            @endforelse
        </div>
    </section>

    <livewire:admin.new-appointment />

    {{-- Appointment side panel --}}
    @if ($appointment = $this->openAppointment)
        <div class="fixed inset-0 z-40" role="dialog" aria-modal="true" aria-labelledby="appointment-title"
             x-data x-on:keydown.escape.window="$wire.closeAppointment()" x-init="$nextTick(() => $refs.close.focus())">
            <div class="absolute inset-0 motion-safe:animate-fade-in bg-slate-900/40 backdrop-blur-[2px]" wire:click="closeAppointment"></div>
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-md motion-safe:animate-slide-in flex-col bg-white shadow-2xl">
                <header class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="min-w-0">
                        <x-status-badge :status="$appointment->status" />
                        <h2 id="appointment-title" class="mt-2 truncate text-xl font-bold text-slate-900">{{ $appointment->customer_name }}</h2>
                        <p class="text-sm text-slate-500">Cita {{ $appointment->code }}</p>
                    </div>
                    <button type="button" x-ref="close" wire:click="closeAppointment" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Cerrar">
                        <x-icon name="x" class="size-5" />
                    </button>
                </header>

                @if ($rescheduling)
                    <div class="flex-1 overflow-y-auto px-6 py-6">
                        <h3 class="font-semibold text-slate-900">Reprogramar cita</h3>
                        <p class="text-sm text-slate-500">
                            Ahora: <span class="first-letter:uppercase">{{ $appointment->localStartsAt()->translatedFormat('l j \d\e F, H:i') }}</span> · {{ $appointment->service->name }}{{ $appointment->staffMember ? ' con '.$appointment->staffMember->firstName() : '' }}
                        </p>

                        @error('rescheduleSlot')
                            <p class="mt-4 flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800" role="alert">
                                <x-icon name="alert" class="mt-0.5 size-4" /> {{ $message }}
                            </p>
                        @enderror

                        @if (collect($this->rescheduleAvailability)->filter()->isEmpty())
                            <p class="mt-5 rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">No hay otros horarios libres en tu ventana de reservas.</p>
                        @else
                            <x-time-picker :business="$this->business" :availability="$this->rescheduleAvailability" :date="$rescheduleDate" :selected="$rescheduleStartsAt"
                                           select-date="selectRescheduleDate" select-slot="selectRescheduleSlot" class="mt-5" />
                        @endif
                    </div>
                @else
                    <dl class="flex-1 space-y-5 overflow-y-auto px-6 py-6 text-sm">
                        <div class="flex gap-3">
                            <x-icon name="calendar" class="mt-0.5 size-5 text-slate-400" />
                            <div>
                                <dt class="sr-only">Fecha</dt>
                                <dd class="font-medium text-slate-900 first-letter:uppercase">{{ $appointment->localStartsAt()->translatedFormat('l j \d\e F') }}</dd>
                                <dd class="text-slate-500 tabular-nums">{{ $appointment->localStartsAt()->format('H:i') }} – {{ $appointment->localEndsAt()->format('H:i') }}</dd>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <x-icon name="tag" class="mt-0.5 size-5 text-slate-400" />
                            <div>
                                <dt class="sr-only">Servicio</dt>
                                <dd class="font-medium text-slate-900">{{ $appointment->service->name }}</dd>
                                <dd class="text-slate-500">{{ $appointment->service->formattedDuration() }} · {{ $appointment->formattedPrice() }}</dd>
                            </div>
                        </div>
                        @if ($appointment->staffMember)
                            <div class="flex gap-3">
                                <x-staff-avatar :staff-member="$appointment->staffMember" class="size-5 text-[9px]" />
                                <div>
                                    <dt class="sr-only">Te atiende</dt>
                                    <dd class="font-medium text-slate-900">{{ $appointment->staffMember->name }}</dd>
                                    @if ($appointment->staffMember->title)
                                        <dd class="text-slate-500">{{ $appointment->staffMember->title }}</dd>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="flex gap-3">
                            <x-icon name="mail" class="mt-0.5 size-5 text-slate-400" />
                            <div class="min-w-0">
                                <dt class="sr-only">Correo</dt>
                                @if ($appointment->customer_email)
                                    <dd><a href="mailto:{{ $appointment->customer_email }}" class="break-all font-medium text-teal-700 hover:underline">{{ $appointment->customer_email }}</a></dd>
                                @else
                                    <dd class="text-slate-500">Sin correo: avísale por teléfono si cambias o cancelas la cita.</dd>
                                @endif
                            </div>
                        </div>
                        @if ($appointment->customer_phone)
                            <div class="flex gap-3">
                                <x-icon name="phone" class="mt-0.5 size-5 text-slate-400" />
                                <div>
                                    <dt class="sr-only">Teléfono</dt>
                                    <dd><a href="tel:{{ preg_replace('/[^\d+]/', '', $appointment->customer_phone) }}" class="font-medium text-teal-700 hover:underline">{{ $appointment->customer_phone }}</a></dd>
                                </div>
                            </div>
                        @endif
                        @if ($appointment->notes)
                            <div class="rounded-lg bg-amber-50 p-3 text-amber-900 ring-1 ring-amber-600/10">
                                <dt class="text-xs font-semibold tracking-wide uppercase">Nota del cliente</dt>
                                <dd class="mt-1">{{ $appointment->notes }}</dd>
                            </div>
                        @endif
                        <div class="border-t border-slate-100 pt-5 text-xs text-slate-500">
                            <dt class="sr-only">Historial</dt>
                            <dd>Reservada el {{ $appointment->created_at->setTimezone($this->business->timezone)->translatedFormat('j \d\e F, H:i') }}</dd>
                            @if ($appointment->confirmed_at)
                                <dd>Confirmada el {{ $appointment->confirmed_at->setTimezone($this->business->timezone)->translatedFormat('j \d\e F, H:i') }}</dd>
                            @endif
                            @if ($appointment->cancelled_at)
                                <dd>Cancelada el {{ $appointment->cancelled_at->setTimezone($this->business->timezone)->translatedFormat('j \d\e F, H:i') }} por {{ $appointment->cancelled_by?->value === 'customer' ? 'el cliente' : 'el negocio' }}</dd>
                            @endif
                        </div>
                    </dl>
                @endif

                @if ($rescheduling)
                    @php
                        $newTime = $rescheduleStartsAt ? \Carbon\CarbonImmutable::parse($rescheduleStartsAt) : null;
                    @endphp
                    <footer class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                        <p class="min-w-0 text-sm">
                            <span class="block text-slate-500">Nuevo horario</span>
                            <span class="block truncate font-semibold text-slate-900 first-letter:uppercase">{{ $newTime ? $newTime->translatedFormat('D j M, H:i') : 'Sin elegir' }}</span>
                        </p>
                        <div class="flex shrink-0 gap-2">
                            <button type="button" wire:click="stopRescheduling" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Volver</button>
                            <button type="button" wire:click="reschedule" @disabled(! $newTime) wire:loading.attr="disabled" wire:target="reschedule"
                                    class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500 disabled:cursor-not-allowed disabled:opacity-50">Guardar</button>
                        </div>
                    </footer>
                @elseif ($appointment->status->isActive())
                    <footer class="border-t border-slate-100 bg-slate-50 px-6 py-4">
                        <x-admin.appointment-actions :appointment="$appointment" size="lg" />
                    </footer>
                @endif
            </aside>
        </div>
    @endif
</div>
