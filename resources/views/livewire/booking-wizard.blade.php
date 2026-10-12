@php
    use App\Enums\AppointmentStatus;
    use Carbon\CarbonImmutable;

    $service = $this->selectedService;
    $selectedTime = $this->selectedSlot;
    $openingStatus = $business->openingStatus();
    $steps = [1 => 'Servicio', 2 => 'Día y hora', 3 => 'Tus datos'];
    $reachable = [1 => true, 2 => $service !== null, 3 => $selectedTime !== null];
    $selectedDay = $date ? CarbonImmutable::parse($date) : null;
@endphp

<div>
    {{-- Business header --}}
    <header class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($coverUrl = $business->coverUrl())
            <img src="{{ $coverUrl }}" alt="" class="h-32 w-full object-cover sm:h-48">
        @else
            <div class="relative h-24 bg-linear-to-br from-teal-700 via-teal-500 to-emerald-400 sm:h-32">
                <div class="bg-dots absolute inset-0"></div>
            </div>
        @endif
        <div class="flex flex-col gap-4 px-5 pb-5 sm:flex-row sm:gap-5 sm:px-6">
            <x-business-avatar :business="$business" class="relative -mt-10 size-20 shrink-0 self-start rounded-2xl border-4 border-white text-2xl shadow-md sm:-mt-12 sm:size-24 sm:text-3xl" />
            <div class="min-w-0 flex-1 sm:pt-4">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $business->name }}</h1>
                @if ($business->description)
                    <p class="mt-1 max-w-2xl text-slate-600">{{ $business->description }}</p>
                @endif
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-600">
                    <span @class([
                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 font-medium ring-1 ring-inset',
                        'bg-emerald-50 text-emerald-800 ring-emerald-600/20' => $openingStatus['isOpen'],
                        'bg-slate-100 text-slate-700 ring-slate-500/20' => ! $openingStatus['isOpen'],
                    ])>
                        <span @class(['size-2 rounded-full', 'bg-emerald-500' => $openingStatus['isOpen'], 'bg-slate-400' => ! $openingStatus['isOpen']])></span>
                        {{ $openingStatus['label'] }}
                    </span>
                    @if ($business->address)
                        <span class="inline-flex items-center gap-1.5"><x-icon name="map-pin" class="size-4 text-slate-400" /> {{ $business->address }}</span>
                    @endif
                    @if ($business->phone)
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $business->phone) }}" class="inline-flex items-center gap-1.5 hover:text-teal-700"><x-icon name="phone" class="size-4 text-slate-400" /> {{ $business->phone }}</a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <div class="mt-8 grid scroll-mt-24 gap-8 lg:grid-cols-[1fr_20rem]"
         x-data="{ reveal() { if ($el.getBoundingClientRect().top < 64) $el.scrollIntoView({ behavior: 'smooth' }) } }"
         x-init="$watch('$wire.step', () => reveal()); $watch('$wire.bookedCode', () => reveal())">
        <div class="min-w-0">
            @if ($appointment = $this->bookedAppointment)
                {{-- Done --}}
                @php
                    $isPending = $appointment->status === AppointmentStatus::Pending;
                @endphp
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-live="polite">
                    <div @class(['flex size-14 items-center justify-center rounded-full motion-safe:animate-pop-in', 'bg-amber-100 text-amber-700' => $isPending, 'bg-emerald-100 text-emerald-700' => ! $isPending])>
                        <x-icon :name="$isPending ? 'hourglass' : 'check'" class="size-7" />
                    </div>
                    <h2 class="mt-5 text-2xl font-bold text-slate-900">{{ $isPending ? '¡Solicitud enviada!' : '¡Cita confirmada!' }}</h2>
                    <p class="mt-2 text-slate-600">
                        @if ($isPending)
                            {{ $business->name }} revisará tu solicitud y te avisaremos por correo a <strong class="text-slate-900">{{ $appointment->customer_email }}</strong> cuando la confirme.
                        @else
                            Te enviamos los detalles a <strong class="text-slate-900">{{ $appointment->customer_email }}</strong>. ¡Te esperamos!
                        @endif
                    </p>

                    <dl class="mt-6 divide-y divide-slate-100 rounded-xl border border-slate-200 text-sm">
                        @foreach ([
                            ['tag', 'Servicio', $appointment->service->name.' · '.$appointment->service->formattedDuration()],
                            ['calendar', 'Fecha y hora', $appointment->whenLabel()],
                            ['wallet', 'Precio', $appointment->formattedPrice().' · se paga en el local'],
                        ] as [$icon, $label, $value])
                            <div class="flex items-start gap-3 px-4 py-3">
                                <x-icon :name="$icon" class="mt-0.5 size-5 text-slate-400" />
                                <dt class="w-28 shrink-0 text-slate-500">{{ $label }}</dt>
                                <dd class="font-medium text-slate-900 first-letter:uppercase">{{ $value }}</dd>
                            </div>
                        @endforeach
                        <div class="flex items-start gap-3 px-4 py-3">
                            <x-icon name="check-circle" class="mt-0.5 size-5 text-slate-400" />
                            <dt class="w-28 shrink-0 text-slate-500">Código</dt>
                            <dd class="font-mono font-semibold tracking-widest text-slate-900">{{ $appointment->code }}</dd>
                        </div>
                    </dl>

                    <p class="mt-6 text-sm font-medium text-slate-700">Guárdala en tu calendario</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <a href="{{ $appointment->googleCalendarUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <x-icon name="calendar-plus" class="size-4" /> Google Calendar
                        </a>
                        <a href="{{ $appointment->calendarFileUrl() }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <x-icon name="download" class="size-4" /> Apple / Outlook (.ics)
                        </a>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3 border-t border-slate-100 pt-6">
                        <a href="{{ $appointment->manageUrl() }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Ver, cambiar o cancelar mi cita</a>
                        <button type="button" wire:click="startOver" class="rounded-lg px-4 py-2 text-sm font-semibold text-teal-700 hover:bg-teal-50">Reservar otra cita</button>
                    </div>
                </section>
            @else
                {{-- Progress --}}
                <nav aria-label="Pasos de la reserva">
                    <ol class="flex items-center gap-2 sm:gap-3">
                        @foreach ($steps as $number => $label)
                            <li @class(['flex items-center gap-2 sm:gap-3', 'flex-1' => $number < 3])>
                                <button type="button" wire:click="goToStep({{ $number }})" @disabled(! $reachable[$number])
                                        @if ($step === $number) aria-current="step" @endif
                                        class="group flex items-center gap-2 disabled:cursor-not-allowed">
                                    <span @class([
                                        'flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold transition',
                                        'bg-teal-600 text-white ring-4 ring-teal-600/15' => $step === $number,
                                        'bg-teal-100 text-teal-700 group-hover:bg-teal-200' => $step > $number,
                                        'bg-slate-100 text-slate-400' => $step < $number,
                                    ])>
                                        @if ($step > $number)
                                            <x-icon name="check" class="size-4" />
                                        @else
                                            {{ $number }}
                                        @endif
                                    </span>
                                    <span @class(['hidden text-sm font-medium sm:block', 'text-slate-900' => $step >= $number, 'text-slate-400' => $step < $number])>{{ $label }}</span>
                                </button>
                                @if ($number < 3)
                                    <span @class(['h-0.5 flex-1 rounded-full', 'bg-teal-500' => $step > $number, 'bg-slate-200' => $step <= $number])></span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>

                <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    @if ($step === 1)
                        {{-- Step 1: service --}}
                        <h2 class="text-lg font-semibold text-slate-900">¿Qué servicio quieres?</h2>
                        <p class="text-sm text-slate-500">Elige uno para ver los horarios libres.</p>

                        @if ($this->services->isEmpty())
                            <p class="mt-6 rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Este negocio todavía no tiene servicios disponibles.</p>
                        @else
                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                @foreach ($this->services as $option)
                                    <button type="button" wire:click="selectService({{ $option->id }})" wire:key="service-{{ $option->id }}"
                                            aria-pressed="{{ $serviceId === $option->id ? 'true' : 'false' }}"
                                            @class([
                                                'group relative flex flex-col rounded-xl border p-4 text-left transition',
                                                'border-teal-600 bg-teal-50/60 ring-1 ring-teal-600' => $serviceId === $option->id,
                                                'border-slate-200 hover:-translate-y-0.5 hover:border-teal-300 hover:shadow-md' => $serviceId !== $option->id,
                                            ])>
                                        <span class="flex items-start justify-between gap-3">
                                            <span class="font-semibold text-slate-900">{{ $option->name }}</span>
                                            @if ($serviceId === $option->id)
                                                <span class="flex size-5 items-center justify-center rounded-full bg-teal-600 text-white"><x-icon name="check" class="size-3.5" /></span>
                                            @endif
                                        </span>
                                        @if ($option->description)
                                            <span class="mt-1 text-sm text-slate-600">{{ $option->description }}</span>
                                        @endif
                                        <span class="mt-4 flex items-center justify-between gap-3 pt-1 text-sm">
                                            <span class="inline-flex items-center gap-1.5 text-slate-500"><x-icon name="clock" class="size-4" /> {{ $option->formattedDuration() }}</span>
                                            <span class="font-semibold text-slate-900">{{ $option->formattedPrice() }}</span>
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    @elseif ($step === 2 && $service)
                        {{-- Step 2: day and time --}}
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">¿Cuándo te viene bien?</h2>
                                <p class="text-sm text-slate-500">{{ $service->name }} · {{ $service->formattedDuration() }}</p>
                            </div>
                            <button type="button" wire:click="goToStep(1)" class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-sm font-medium text-slate-600 hover:bg-slate-100">
                                <x-icon name="arrow-left" class="size-4" /> Servicio
                            </button>
                        </div>

                        @error('slot')
                            <p class="mt-4 flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800" role="alert">
                                <x-icon name="alert" class="mt-0.5 size-4" /> {{ $message }}
                            </p>
                        @enderror

                        @if (collect($this->availability)->filter()->isEmpty())
                            <p class="mt-6 rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                                No hay horarios libres en los próximos {{ $business->booking_window_days }} días. Prueba con otro servicio o vuelve más tarde.
                            </p>
                        @else
                            <x-time-picker :business="$business" :availability="$this->availability" :date="$date" :selected="$startsAt" class="mt-5" />
                        @endif
                    @elseif ($step === 3 && $service && $selectedTime)
                        {{-- Step 3: contact details --}}
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Tus datos</h2>
                                <p class="text-sm text-slate-500">Solo los usamos para enviarte la confirmación y el recordatorio.</p>
                            </div>
                            <button type="button" wire:click="changeSlot" class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-sm font-medium text-slate-600 hover:bg-slate-100">
                                <x-icon name="arrow-left" class="size-4" /> Horario
                            </button>
                        </div>

                        <div class="mt-5 flex items-center gap-3 rounded-xl bg-teal-50 px-4 py-3 text-sm text-teal-900 ring-1 ring-teal-600/10">
                            <x-icon name="calendar-check" class="size-5 text-teal-600" />
                            <p><strong>{{ $service->name }}</strong> · <span class="first-letter:uppercase">{{ $selectedTime->translatedFormat('l j \d\e F, H:i') }}</span></p>
                        </div>

                        <form id="booking-form" wire:submit="book" class="mt-5 grid gap-4 sm:grid-cols-2">
                            <x-field name="customerName" label="Nombre completo" wire:model="customerName" autocomplete="name" placeholder="Ana López" required />
                            <x-field name="customerEmail" type="email" label="Correo" wire:model="customerEmail" autocomplete="email" placeholder="ana@correo.com" required />
                            <x-field name="customerPhone" type="tel" label="Teléfono (opcional)" wire:model="customerPhone" autocomplete="tel" placeholder="55 1234 5678" />
                            <div class="sm:col-span-2">
                                <x-field name="notes" type="textarea" label="Notas para el negocio (opcional)" wire:model="notes" placeholder="¿Algo que debamos saber?" />
                            </div>
                            <div class="hidden sm:col-span-2 lg:block">
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-5 py-3 font-semibold text-white shadow-sm hover:bg-teal-500 disabled:opacity-60" wire:loading.attr="disabled" wire:target="book">
                                    <svg wire:loading wire:target="book" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity=".25" stroke-width="4"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"/></svg>
                                    <span wire:loading.remove wire:target="book">Confirmar reserva · {{ $service->formattedPrice() }}</span>
                                    <span wire:loading wire:target="book">Reservando…</span>
                                </button>
                                <p class="mt-2 text-xs text-slate-500">Recibirás un correo con un enlace para cancelar si lo necesitas.</p>
                            </div>
                        </form>
                    @endif
                </section>
            @endif
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
            @unless ($this->bookedAppointment)
                <div class="hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:block">
                    <h2 class="font-semibold text-slate-900">Tu reserva</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        @foreach ([
                            ['tag', 'Servicio', $service?->name],
                            ['calendar', 'Día', $selectedTime?->translatedFormat('l j \d\e F') ?? $selectedDay?->translatedFormat('l j \d\e F')],
                            ['clock', 'Hora', $selectedTime ? $selectedTime->format('H:i').' – '.$selectedTime->addMinutes($service->duration_minutes)->format('H:i') : null],
                        ] as [$icon, $label, $value])
                            <div class="flex items-start gap-3">
                                <x-icon :name="$icon" class="mt-0.5 size-4 text-slate-400" />
                                <dt class="sr-only">{{ $label }}</dt>
                                <dd @class(['first-letter:uppercase', 'font-medium text-slate-900' => $value, 'text-slate-400' => ! $value])>{{ $value ?? match ($label) { 'Servicio' => 'Elige un servicio', 'Día' => 'Elige un día', default => 'Elige una hora' } }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4">
                        <span class="text-sm text-slate-500">Total</span>
                        <span class="text-lg font-bold text-slate-900">{{ $service?->formattedPrice() ?? '—' }}</span>
                    </div>
                    @if ($step === 3 && $service && $selectedTime)
                        <button type="submit" form="booking-form" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 font-semibold text-white hover:bg-teal-500 disabled:opacity-60" wire:loading.attr="disabled" wire:target="book">
                            <span wire:loading.remove wire:target="book">Confirmar reserva</span>
                            <span wire:loading wire:target="book">Reservando…</span>
                        </button>
                    @endif
                </div>
            @endunless

            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm shadow-sm">
                <h2 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="clock" class="size-4 text-slate-400" /> Horario</h2>
                <dl class="mt-3 space-y-2">
                    @foreach ($business->compactWeeklyHours() as $row)
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">{{ $row['days'] }}</dt>
                            <dd @class(['text-right', 'font-medium text-slate-900' => $row['ranges'], 'text-slate-400' => ! $row['ranges']])>
                                @forelse ($row['ranges'] as $range)
                                    <span class="block tabular-nums">{{ $range }}</span>
                                @empty
                                    Cerrado
                                @endforelse
                            </dd>
                        </div>
                    @endforeach
                </dl>
                <p class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500">Horas en zona horaria {{ str_replace('_', ' ', $business->timezone) }}.</p>
            </div>
        </aside>
    </div>

    {{-- Mobile summary bar --}}
    @if ($service && ! $this->bookedAppointment)
        <div data-mobile-bar class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-4px_16px_rgba(15,23,42,0.06)] backdrop-blur-md lg:hidden">
            <div class="mx-auto flex max-w-xl items-center justify-between gap-3">
                <div class="min-w-0 text-sm">
                    <p class="truncate font-semibold text-slate-900">{{ $service->name }} · {{ $service->formattedPrice() }}</p>
                    <p class="truncate text-slate-500 first-letter:uppercase">{{ $selectedTime ? $selectedTime->translatedFormat('D j M, H:i') : 'Elige día y hora' }}</p>
                </div>
                @if ($step === 3 && $selectedTime)
                    <button type="submit" form="booking-form" class="shrink-0 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-500 disabled:opacity-60" wire:loading.attr="disabled" wire:target="book">
                        <span wire:loading.remove wire:target="book">Confirmar</span>
                        <span wire:loading wire:target="book">Reservando…</span>
                    </button>
                @elseif ($selectedTime)
                    <button type="button" wire:click="goToStep(3)" class="shrink-0 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-500">Continuar</button>
                @endif
            </div>
        </div>
    @endif
</div>
