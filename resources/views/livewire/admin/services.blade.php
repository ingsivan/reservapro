<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Servicios</h1>
            <p class="text-sm text-slate-500">Lo que tus clientes pueden reservar, con su duración y precio.</p>
        </div>
        @unless ($showForm)
            <button type="button" wire:click="create" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Nuevo servicio</button>
        @endunless
    </div>

    <x-notice :message="$notice" :error="$noticeIsError" class="mt-6" />

    @if ($showForm)
        <form wire:submit="save" class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="font-semibold text-slate-900">{{ $editingId ? 'Editar servicio' : 'Nuevo servicio' }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-field name="name" label="Nombre" wire:model="name" placeholder="Ej. Corte de cabello" required />
                <div class="grid grid-cols-2 gap-4">
                    <x-field name="durationMinutes" type="number" label="Duración (min)" wire:model="durationMinutes" min="5" max="480" step="5" required />
                    <x-field name="price" type="number" label="Precio ($)" wire:model="price" min="0" step="0.01" placeholder="250.00" required />
                </div>
                <div class="sm:col-span-2">
                    <x-field name="description" type="textarea" label="Descripción (opcional)" wire:model="description" />
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-teal-600"> Visible para reservar
                </label>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Guardar</button>
                <button type="button" wire:click="cancelForm" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</button>
            </div>
        </form>
    @endif

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        @forelse ($this->services as $service)
            <div wire:key="service-{{ $service->id }}" class="flex flex-col gap-3 border-b border-slate-100 p-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p @class(['font-semibold', 'text-slate-900' => $service->is_active, 'text-slate-400' => ! $service->is_active])>{{ $service->name }}</p>
                        @unless ($service->is_active)
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Oculto</span>
                        @endunless
                    </div>
                    <p class="text-sm text-slate-500">
                        {{ $service->formattedDuration() }} · {{ $service->formattedPrice() }}
                        @if ($service->upcoming_count)
                            · {{ trans_choice(':count cita próxima|:count citas próximas', $service->upcoming_count) }}
                        @endif
                    </p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <button type="button" wire:click="edit({{ $service->id }})" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Editar</button>
                    <button type="button" wire:click="toggleActive({{ $service->id }})" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ $service->is_active ? 'Ocultar' : 'Mostrar' }}</button>
                    <button type="button" wire:click="delete({{ $service->id }})" wire:confirm="¿Eliminar «{{ $service->name }}»?" class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">Eliminar</button>
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-sm text-slate-500">
                Todavía no tienes servicios. Crea el primero para empezar a recibir reservas.
            </div>
        @endforelse
    </div>
</div>
