<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Servicios</h1>
            <p class="text-sm text-slate-500">Lo que tus clientes pueden reservar, con su duración y precio.</p>
        </div>
        <button type="button" wire:click="create" class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500">
            <x-icon name="plus" class="size-4" /> Nuevo servicio
        </button>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->services as $service)
            <article wire:key="service-{{ $service->id }}" @class(['flex flex-col rounded-xl border bg-white p-5 shadow-sm transition', 'border-slate-200' => $service->is_active, 'border-dashed border-slate-300 bg-slate-50/60' => ! $service->is_active])>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 @class(['truncate font-semibold', 'text-slate-900' => $service->is_active, 'text-slate-500' => ! $service->is_active])>{{ $service->name }}</h2>
                        <p class="mt-0.5 text-lg font-bold text-slate-900">{{ $service->formattedPrice() }}</p>
                    </div>
                    <button type="button" role="switch" aria-checked="{{ $service->is_active ? 'true' : 'false' }}" wire:click="toggleActive({{ $service->id }})"
                            aria-label="{{ $service->is_active ? 'Ocultar' : 'Mostrar' }} «{{ $service->name }}» en la página de reservas"
                            title="{{ $service->is_active ? 'Visible para reservar' : 'Oculto' }}"
                            @class(['relative inline-flex h-6 w-11 shrink-0 rounded-full transition', 'bg-teal-600' => $service->is_active, 'bg-slate-300' => ! $service->is_active])>
                        <span @class(['absolute top-0.5 size-5 rounded-full bg-white shadow transition-all', 'left-[1.375rem]' => $service->is_active, 'left-0.5' => ! $service->is_active])></span>
                    </button>
                </div>

                @if ($service->description)
                    <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ $service->description }}</p>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                    <span class="inline-flex items-center gap-1.5"><x-icon name="clock" class="size-4" /> {{ $service->formattedDuration() }}</span>
                    <span class="inline-flex items-center gap-1.5"><x-icon name="calendar" class="size-4" /> {{ trans_choice('{0} Sin citas próximas|{1} :count cita próxima|[2,*] :count citas próximas', $service->upcoming_count) }}</span>
                    @unless ($service->is_active)
                        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-600">Oculto</span>
                    @endunless
                </div>

                <div class="mt-auto pt-4">
                    <div class="flex gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="edit({{ $service->id }})" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            <x-icon name="pencil" class="size-4" /> Editar
                        </button>
                        <button type="button"
                                x-on:click="$dispatch('confirm-action', {
                                    title: @js('¿Eliminar «'.$service->name.'»?'),
                                    message: 'Si ya tiene citas registradas, lo ocultaremos en lugar de borrarlo para conservar el historial.',
                                    confirmLabel: 'Eliminar',
                                    action: () => $wire.delete({{ $service->id }}),
                                })"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">
                            <x-icon name="trash" class="size-4" /> Eliminar
                        </button>
                    </div>
                </div>
            </article>
        @empty
            <div class="flex flex-col items-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center sm:col-span-2 xl:col-span-3">
                <span class="flex size-12 items-center justify-center rounded-full bg-teal-50 text-teal-600"><x-icon name="tag" class="size-6" /></span>
                <p class="mt-3 font-medium text-slate-900">Todavía no tienes servicios</p>
                <p class="mt-1 text-sm text-slate-500">Crea el primero para empezar a recibir reservas.</p>
                <button type="button" wire:click="create" class="mt-4 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Crear servicio</button>
            </div>
        @endforelse
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="service-form-title"
             x-data x-on:keydown.escape.window="$wire.cancelForm()" x-init="$nextTick(() => $el.querySelector('input')?.focus())">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px] motion-safe:animate-fade-in" wire:click="cancelForm"></div>
            <form wire:submit="save" class="relative w-full max-w-lg overflow-hidden rounded-t-2xl bg-white shadow-2xl motion-safe:animate-fade-in sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 id="service-form-title" class="font-semibold text-slate-900">{{ $editingId ? 'Editar servicio' : 'Nuevo servicio' }}</h2>
                    <button type="button" wire:click="cancelForm" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Cerrar"><x-icon name="x" class="size-5" /></button>
                </div>
                <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-field name="name" label="Nombre" wire:model="name" placeholder="Ej. Corte de cabello" required autofocus />
                    </div>
                    <x-field name="durationMinutes" type="number" label="Duración (min)" wire:model="durationMinutes" min="5" max="480" step="5" required />
                    <x-field name="price" type="number" label="Precio ($)" wire:model="price" min="0" step="0.01" placeholder="250.00" required />
                    <div class="sm:col-span-2">
                        <x-field name="description" type="textarea" label="Descripción (opcional)" wire:model="description" placeholder="Qué incluye el servicio" />
                    </div>
                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm sm:col-span-2">
                        <input type="checkbox" wire:model="isActive" class="mt-0.5 size-4 rounded border-slate-300 accent-teal-600">
                        <span>
                            <span class="font-medium text-slate-900">Visible para reservar</span>
                            <span class="block text-slate-500">Si lo desmarcas, el servicio se guarda pero no aparece en tu página.</span>
                        </span>
                    </label>
                </div>
                <div class="flex flex-col-reverse gap-2 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelForm" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">{{ $editingId ? 'Guardar cambios' : 'Crear servicio' }}</button>
                </div>
            </form>
        </div>
    @endif
</div>
