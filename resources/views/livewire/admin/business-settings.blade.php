@php
    $selectClasses = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/30 focus:outline-none';
@endphp

<div class="max-w-3xl">
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Ajustes</h1>
    <p class="text-sm text-slate-500">Datos públicos de tu negocio y reglas para las reservas.</p>

    <section class="mt-6 flex flex-col gap-4 rounded-xl bg-linear-to-br from-teal-600 to-emerald-500 p-5 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between" x-data="{ copied: false }">
        <div class="min-w-0">
            <p class="flex items-center gap-2 text-sm font-medium text-teal-50"><x-icon name="link" class="size-4" /> Tu página de reservas</p>
            <p class="mt-1 truncate font-semibold">{{ route('booking', $this->business) }}</p>
        </div>
        <div class="flex shrink-0 gap-2">
            <button type="button" x-on:click="navigator.clipboard.writeText(@js(route('booking', $this->business))); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-2 text-sm font-semibold ring-1 ring-white/30 hover:bg-white/25">
                <x-icon name="copy" class="size-4" /> <span x-text="copied ? '¡Copiado!' : 'Copiar enlace'">Copiar enlace</span>
            </button>
            <a href="{{ route('booking', $this->business) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-teal-800 hover:bg-teal-50">
                <x-icon name="external" class="size-4" /> Abrir
            </a>
        </div>
    </section>

    {{-- Logo and cover: each upload is saved right away --}}
    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="relative">
            @if ($coverUrl = $this->business->coverUrl())
                <img src="{{ $coverUrl }}" alt="Foto de portada actual" class="h-32 w-full object-cover sm:h-40">
            @else
                <div class="relative h-32 bg-linear-to-br from-teal-700 via-teal-500 to-emerald-400 sm:h-40">
                    <div class="bg-dots absolute inset-0"></div>
                </div>
            @endif
            <div class="absolute right-3 bottom-3 flex gap-2">
                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-white/90 px-3 py-1.5 text-sm font-semibold text-slate-800 shadow-sm backdrop-blur hover:bg-white">
                    <x-icon name="pencil" class="size-4" />
                    <span wire:loading.remove wire:target="cover">{{ $coverUrl ? 'Cambiar portada' : 'Subir portada' }}</span>
                    <span wire:loading wire:target="cover">Subiendo…</span>
                    <input type="file" wire:model="cover" accept="image/jpeg,image/png,image/webp" class="sr-only">
                </label>
                @if ($coverUrl)
                    <button type="button" wire:click="removeCover" class="rounded-lg bg-white/90 px-2 py-1.5 text-slate-600 shadow-sm backdrop-blur hover:bg-white hover:text-rose-700" aria-label="Quitar foto de portada" title="Quitar foto de portada">
                        <x-icon name="trash" class="size-4" />
                    </button>
                @endif
            </div>
        </div>
        <div class="flex flex-col gap-4 px-5 pb-5 sm:flex-row sm:items-end sm:px-6">
            <x-business-avatar :business="$this->business" class="relative -mt-10 size-20 shrink-0 rounded-2xl border-4 border-white text-2xl shadow-md" />
            <div class="min-w-0 flex-1">
                <h2 class="font-semibold text-slate-900">Imagen de tu página</h2>
                <p class="text-sm text-slate-500">Logo cuadrado de al menos 96 px y portada horizontal de al menos 800 × 200 px. JPG, PNG o WebP.</p>
                @error('logo') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                @error('cover') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex shrink-0 gap-2">
                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <span wire:loading.remove wire:target="logo">{{ $this->business->logoUrl() ? 'Cambiar logo' : 'Subir logo' }}</span>
                    <span wire:loading wire:target="logo">Subiendo…</span>
                    <input type="file" wire:model="logo" accept="image/jpeg,image/png,image/webp" class="sr-only">
                </label>
                @if ($this->business->logoUrl())
                    <button type="button" wire:click="removeLogo" class="rounded-lg px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">Quitar</button>
                @endif
            </div>
        </div>
    </section>

    <form wire:submit="save" class="mt-6 space-y-6">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="font-semibold text-slate-900">Tu negocio</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-field name="name" label="Nombre" wire:model="name" required />
                <div>
                    <label for="slug" class="text-sm font-medium text-slate-700">Dirección de tu página</label>
                    <div class="mt-1 flex rounded-lg border border-slate-300 focus-within:border-teal-500 focus-within:ring-2 focus-within:ring-teal-500/30">
                        <span class="flex items-center border-r border-slate-200 bg-slate-50 px-3 text-sm text-slate-500">/reservar/</span>
                        <input id="slug" wire:model="slug" class="w-full rounded-r-lg px-3 py-2 focus:outline-none" required>
                    </div>
                    @error('slug')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <x-field name="description" type="textarea" label="Descripción" wire:model="description" />
                </div>
                <x-field name="address" label="Dirección física" wire:model="address" />
                <x-field name="phone" type="tel" label="Teléfono" wire:model="phone" />
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="font-semibold text-slate-900">Reglas de reserva</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="timezone" class="text-sm font-medium text-slate-700">Zona horaria</label>
                    <select id="timezone" wire:model="timezone" class="{{ $selectClasses }}">
                        @foreach (\App\Livewire\Admin\BusinessSettings::TIMEZONES + [$timezone => $timezone] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('timezone') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="slotIntervalMinutes" class="text-sm font-medium text-slate-700">Mostrar horarios cada</label>
                    <select id="slotIntervalMinutes" wire:model="slotIntervalMinutes" class="{{ $selectClasses }}">
                        @foreach ([10, 15, 20, 30, 45, 60] as $minutes)
                            <option value="{{ $minutes }}">{{ $minutes }} minutos</option>
                        @endforeach
                    </select>
                    @error('slotIntervalMinutes') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="minNoticeMinutes" class="text-sm font-medium text-slate-700">Anticipación mínima</label>
                    <select id="minNoticeMinutes" wire:model="minNoticeMinutes" class="{{ $selectClasses }}">
                        @foreach ([0 => 'Sin mínimo', 30 => '30 minutos', 60 => '1 hora', 120 => '2 horas', 240 => '4 horas', 720 => '12 horas', 1440 => '1 día', 2880 => '2 días'] as $minutes => $label)
                            <option value="{{ $minutes }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('minNoticeMinutes') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <x-field name="bookingWindowDays" type="number" label="Se puede reservar hasta (días)" wire:model="bookingWindowDays" min="1" max="180" />
                <x-field name="cancellationNoticeHours" type="number" label="Cancelación en línea hasta (horas antes)" wire:model="cancellationNoticeHours" min="0" max="168" />
                <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm sm:col-span-2">
                    <input type="checkbox" wire:model="requiresConfirmation" class="mt-0.5 size-4 rounded border-slate-300 accent-teal-600">
                    <span>
                        <span class="font-medium text-slate-900">Confirmar cada cita manualmente</span>
                        <span class="block text-slate-500">Las reservas quedan «por confirmar» hasta que las apruebes. Mientras tanto, el horario queda apartado.</span>
                    </span>
                </label>
            </div>
        </section>

        <button type="submit" class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-500">Guardar cambios</button>
    </form>
</div>
