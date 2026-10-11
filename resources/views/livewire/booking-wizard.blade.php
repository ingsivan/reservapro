<div class="grid gap-8 lg:grid-cols-[1fr_18rem]">
    <div class="min-w-0">
        <div class="mb-8">
            <p class="text-sm font-medium text-teal-700">Reserva en línea</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ $business->name }}</h1>
            @if ($business->description)
                <p class="mt-2 max-w-2xl text-slate-600">{{ $business->description }}</p>
            @endif
        </div>

        @if ($this->bookedAppointment)
            @php($appointment = $this->bookedAppointment)
            <section class="rounded-xl border border-emerald-200 bg-white p-6 shadow-sm sm:p-8" aria-live="polite">
                <div class="flex size-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <h2 class="mt-4 text-2xl font-bold text-slate-900">
                    {{ $appointment->status === \App\Enums\AppointmentStatus::Pending ? '¡Solicitud enviada!' : '¡Cita confirmada!' }}
                </h2>
                <p class="mt-2 text-slate-600">
                    @if ($appointment->status === \App\Enums\AppointmentStatus::Pending)
                        {{ $business->name }} revisará tu solicitud y te avisaremos por correo cuando la confirme.
                    @else
                        Te enviamos los detalles a <strong>{{ $appointment->customer_email }}</strong>.
                    @endif
                </p>
                <dl class="mt-6 grid gap-4 rounded-lg bg-slate-50 p-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Servicio</dt><dd class="font-medium text-slate-900">{{ $appointment->service->name }}</dd></div>
                    <div><dt class="text-slate-500">Fecha</dt><dd class="font-medium text-slate-900 first-letter:uppercase">{{ $appointment->whenLabel() }}</dd></div>
                    <div><dt class="text-slate-500">Precio</dt><dd class="font-medium text-slate-900">{{ $appointment->formattedPrice() }}</dd></div>
                    <div><dt class="text-slate-500">Código</dt><dd class="font-mono font-semibold tracking-wider text-slate-900">{{ $appointment->code }}</dd></div>
                </dl>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ $appointment->manageUrl() }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ver o cancelar mi cita</a>
                    <button type="button" wire:click="startOver" class="rounded-lg px-4 py-2 text-sm font-semibold text-teal-700 hover:bg-teal-50">Reservar otra cita</button>
                </div>
            </section>
        @else
            <ol class="space-y-6">
                {{-- Step 1: service --}}
                <li class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="flex items-center gap-3 font-semibold text-slate-900">
                        <span class="flex size-7 items-center justify-center rounded-full bg-teal-600 text-sm text-white">1</span>
                        Elige un servicio
                    </h2>
                    @if ($this->services->isEmpty())
                        <p class="mt-4 text-sm text-slate-500">Este negocio todavía no tiene servicios disponibles.</p>
                    @else
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach ($this->services as $service)
                                <button type="button" wire:click="selectService({{ $service->id }})" wire:key="service-{{ $service->id }}"
                                        @class([
                                            'rounded-lg border p-4 text-left transition',
                                            'border-teal-600 bg-teal-50 ring-1 ring-teal-600' => $serviceId === $service->id,
                                            'border-slate-200 hover:border-slate-300 hover:bg-slate-50' => $serviceId !== $service->id,
                                        ])
                                        aria-pressed="{{ $serviceId === $service->id ? 'true' : 'false' }}">
                                    <span class="flex items-start justify-between gap-3">
                                        <span class="font-medium text-slate-900">{{ $service->name }}</span>
                                        <span class="shrink-0 font-semibold text-slate-900">{{ $service->formattedPrice() }}</span>
                                    </span>
                                    <span class="mt-1 block text-sm text-slate-500">{{ $service->formattedDuration() }}</span>
                                    @if ($service->description)
                                        <span class="mt-2 block text-sm text-slate-600">{{ $service->description }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </li>

                {{-- Step 2: day and time --}}
                <li @class(['rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6', 'opacity-60' => ! $this->selectedService])>
                    <h2 class="flex items-center gap-3 font-semibold text-slate-900">
                        <span @class(['flex size-7 items-center justify-center rounded-full text-sm', 'bg-teal-600 text-white' => $this->selectedService, 'bg-slate-200 text-slate-600' => ! $this->selectedService])>2</span>
                        Elige día y hora
                    </h2>

                    @if ($this->selectedService)
                        @error('slot')
                            <p class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800" role="alert">{{ $message }}</p>
                        @enderror

                        @if (collect($this->availability)->filter()->isEmpty())
                            <p class="mt-4 text-sm text-slate-500">No hay horarios libres en los próximos {{ $business->booking_window_days }} días. Prueba con otro servicio o vuelve más tarde.</p>
                        @else
                            <div class="-mx-1 mt-4 flex gap-2 overflow-x-auto px-1 pb-2" role="listbox" aria-label="Días">
                                @foreach ($this->availability as $day => $daySlots)
                                    @php($dayDate = \Carbon\CarbonImmutable::parse($day))
                                    <button type="button" wire:click="selectDate('{{ $day }}')" wire:key="day-{{ $day }}"
                                            @disabled(empty($daySlots))
                                            role="option" aria-selected="{{ $date === $day ? 'true' : 'false' }}"
                                            @class([
                                                'flex w-16 shrink-0 flex-col items-center rounded-lg border px-2 py-2 text-sm transition',
                                                'border-teal-600 bg-teal-600 text-white' => $date === $day,
                                                'border-slate-200 hover:border-teal-400' => $date !== $day && ! empty($daySlots),
                                                'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300' => empty($daySlots),
                                            ])>
                                        <span class="text-xs uppercase">{{ $dayDate->translatedFormat('D') }}</span>
                                        <span class="text-lg font-semibold">{{ $dayDate->day }}</span>
                                        <span class="text-xs">{{ $dayDate->translatedFormat('M') }}</span>
                                    </button>
                                @endforeach
                            </div>

                            @if ($date)
                                <p class="mt-4 text-sm font-medium text-slate-700 first-letter:uppercase">{{ \Carbon\CarbonImmutable::parse($date)->translatedFormat('l j \d\e F') }}</p>
                                <div class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-5">
                                    @foreach ($this->timeSlots as $start)
                                        <button type="button" wire:click="selectSlot('{{ $start->toIso8601String() }}')" wire:key="slot-{{ $start->getTimestamp() }}"
                                                @class([
                                                    'rounded-lg border px-3 py-2 text-sm font-medium transition',
                                                    'border-teal-600 bg-teal-600 text-white' => $startsAt === $start->toIso8601String(),
                                                    'border-slate-200 text-slate-700 hover:border-teal-400 hover:text-teal-800' => $startsAt !== $start->toIso8601String(),
                                                ])>
                                            {{ $start->format('H:i') }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    @endif
                </li>

                {{-- Step 3: contact details --}}
                <li @class(['rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6', 'opacity-60' => ! $this->selectedSlot])>
                    <h2 class="flex items-center gap-3 font-semibold text-slate-900">
                        <span @class(['flex size-7 items-center justify-center rounded-full text-sm', 'bg-teal-600 text-white' => $this->selectedSlot, 'bg-slate-200 text-slate-600' => ! $this->selectedSlot])>3</span>
                        Tus datos
                    </h2>

                    @if ($this->selectedSlot && $this->selectedService)
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-teal-50 px-4 py-3 text-sm text-teal-900">
                            <span>
                                <strong>{{ $this->selectedService->name }}</strong> ·
                                <span class="first-letter:uppercase">{{ $this->selectedSlot->translatedFormat('l j \d\e F, H:i') }}</span>
                            </span>
                            <button type="button" wire:click="changeSlot" class="font-medium text-teal-700 hover:underline">Cambiar</button>
                        </div>

                        <form wire:submit="book" class="mt-4 grid gap-4 sm:grid-cols-2">
                            <x-field name="customerName" label="Nombre completo" wire:model="customerName" autocomplete="name" required />
                            <x-field name="customerEmail" type="email" label="Correo" wire:model="customerEmail" autocomplete="email" required />
                            <x-field name="customerPhone" type="tel" label="Teléfono (opcional)" wire:model="customerPhone" autocomplete="tel" />
                            <div class="sm:col-span-2">
                                <x-field name="notes" type="textarea" label="Notas para el negocio (opcional)" wire:model="notes" />
                            </div>
                            <div class="sm:col-span-2">
                                <button type="submit" class="w-full rounded-lg bg-teal-600 px-4 py-3 font-semibold text-white hover:bg-teal-500 disabled:opacity-60 sm:w-auto" wire:loading.attr="disabled" wire:target="book">
                                    <span wire:loading.remove wire:target="book">Confirmar reserva · {{ $this->selectedService->formattedPrice() }}</span>
                                    <span wire:loading wire:target="book">Reservando…</span>
                                </button>
                                <p class="mt-2 text-xs text-slate-500">Recibirás un correo con un enlace para cancelar si lo necesitas.</p>
                            </div>
                        </form>
                    @endif
                </li>
            </ol>
        @endif
    </div>

    <aside class="space-y-4 lg:pt-20">
        <div class="rounded-xl border border-slate-200 bg-white p-5 text-sm shadow-sm">
            <h2 class="font-semibold text-slate-900">Horario</h2>
            <dl class="mt-3 space-y-1.5">
                @foreach ($business->weeklyHours() as $row)
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-600">{{ $row['day'] }}</dt>
                        <dd class="text-right font-medium text-slate-900">{{ $row['ranges'] ? implode(', ', $row['ranges']) : 'Cerrado' }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-3 text-xs text-slate-500">Horas en zona horaria {{ $business->timezone }}.</p>
        </div>
        @if ($business->address || $business->phone)
            <div class="rounded-xl border border-slate-200 bg-white p-5 text-sm shadow-sm">
                <h2 class="font-semibold text-slate-900">Contacto</h2>
                @if ($business->address)
                    <p class="mt-2 text-slate-600">{{ $business->address }}</p>
                @endif
                @if ($business->phone)
                    <p class="mt-1 text-slate-600">{{ $business->phone }}</p>
                @endif
            </div>
        @endif
    </aside>
</div>
