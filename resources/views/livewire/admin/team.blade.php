@php
    use App\Models\StaffMember;

    $activeServiceCount = $this->services->where('is_active', true)->count();
@endphp

<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Equipo</h1>
            <p class="text-sm text-slate-500">Cada persona tiene su propia agenda. Tus clientes eligen con quién reservar o dejan que les asignemos a quien esté libre.</p>
        </div>
        <button type="button" wire:click="create" class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500">
            <x-icon name="plus" class="size-4" /> Agregar persona
        </button>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->staffMembers as $staffMember)
            @php
                $offered = $staffMember->services->where('is_active', true);
            @endphp
            <article wire:key="staff-{{ $staffMember->id }}" @class(['flex flex-col rounded-xl border bg-white p-5 shadow-sm transition', 'border-slate-200' => $staffMember->is_active, 'border-dashed border-slate-300 bg-slate-50/60' => ! $staffMember->is_active])>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <x-staff-avatar :staff-member="$staffMember" @class(['size-11 text-sm', 'opacity-60' => ! $staffMember->is_active]) />
                        <div class="min-w-0">
                            <h2 @class(['truncate font-semibold', 'text-slate-900' => $staffMember->is_active, 'text-slate-500' => ! $staffMember->is_active])>{{ $staffMember->name }}</h2>
                            <p class="truncate text-sm text-slate-500">{{ $staffMember->title ?: 'Sin puesto' }}</p>
                        </div>
                    </div>
                    <button type="button" role="switch" aria-checked="{{ $staffMember->is_active ? 'true' : 'false' }}" wire:click="toggleActive({{ $staffMember->id }})"
                            aria-label="{{ $staffMember->is_active ? 'Dejar de recibir' : 'Volver a recibir' }} reservas con {{ $staffMember->name }}"
                            title="{{ $staffMember->is_active ? 'Recibe reservas' : 'No recibe reservas' }}"
                            @class(['relative inline-flex h-6 w-11 shrink-0 rounded-full transition', 'bg-teal-600' => $staffMember->is_active, 'bg-slate-300' => ! $staffMember->is_active])>
                        <span @class(['absolute top-0.5 size-5 rounded-full bg-white shadow transition-all', 'left-[1.375rem]' => $staffMember->is_active, 'left-0.5' => ! $staffMember->is_active])></span>
                    </button>
                </div>

                <div class="mt-4 flex flex-wrap gap-1.5">
                    @if ($offered->isEmpty())
                        <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-amber-600/20">No ofrece ningún servicio</span>
                    @elseif ($offered->count() === $activeServiceCount)
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">Todos los servicios</span>
                    @else
                        @foreach ($offered->sortBy('name') as $service)
                            <span wire:key="staff-{{ $staffMember->id }}-service-{{ $service->id }}" class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $service->name }}</span>
                        @endforeach
                    @endif
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                    <a href="{{ route('admin.schedule', ['equipo' => $staffMember->id]) }}" wire:navigate class="inline-flex items-center gap-1.5 hover:text-teal-700">
                        <x-icon name="clock" class="size-4" /> {{ $staffMember->schedules_count ? 'Horario propio' : 'Horario del negocio' }}
                    </a>
                    <span class="inline-flex items-center gap-1.5"><x-icon name="calendar" class="size-4" /> {{ trans_choice('{0} Sin citas próximas|{1} :count cita próxima|[2,*] :count citas próximas', $staffMember->upcoming_count) }}</span>
                    @unless ($staffMember->is_active)
                        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-600">No recibe reservas</span>
                    @endunless
                </div>

                <div class="mt-auto pt-4">
                    <div class="flex gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="edit({{ $staffMember->id }})" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            <x-icon name="pencil" class="size-4" /> Editar
                        </button>
                        <button type="button"
                                x-on:click="$dispatch('confirm-action', {
                                    title: @js('¿Quitar a '.$staffMember->name.' del equipo?'),
                                    message: 'Si ya tiene citas registradas, solo dejará de recibir reservas para conservar el historial.',
                                    confirmLabel: 'Quitar',
                                    action: () => $wire.delete({{ $staffMember->id }}),
                                })"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50">
                            <x-icon name="trash" class="size-4" /> Quitar
                        </button>
                    </div>
                </div>
            </article>
        @empty
            <div class="flex flex-col items-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center sm:col-span-2 xl:col-span-3">
                <span class="flex size-12 items-center justify-center rounded-full bg-teal-50 text-teal-600"><x-icon name="users" class="size-6" /></span>
                <p class="mt-3 font-medium text-slate-900">Por ahora atiendes tú solo</p>
                <p class="mt-1 max-w-md text-sm text-slate-500">Si trabajas con más personas, agrégalas para que cada una tenga su agenda y se puedan atender varias citas a la misma hora. Las citas que ya tienes pasarán a la primera persona que agregues.</p>
                <button type="button" wire:click="create" class="mt-4 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Agregar persona</button>
            </div>
        @endforelse
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="staff-form-title"
             x-data x-on:keydown.escape.window="$wire.cancelForm()" x-init="$nextTick(() => $el.querySelector('input')?.focus())">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px] motion-safe:animate-fade-in" wire:click="cancelForm"></div>
            <form wire:submit="save" class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl motion-safe:animate-fade-in sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 id="staff-form-title" class="font-semibold text-slate-900">{{ $editingId ? 'Editar persona' : 'Agregar persona' }}</h2>
                    <button type="button" wire:click="cancelForm" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Cerrar"><x-icon name="x" class="size-5" /></button>
                </div>
                <div class="grid gap-4 overflow-y-auto px-6 py-5 sm:grid-cols-2">
                    <x-field name="name" label="Nombre" wire:model="name" placeholder="Ej. Diego Ramírez" required />
                    <x-field name="title" label="Puesto (opcional)" wire:model="title" placeholder="Ej. Barbero" />

                    <fieldset class="sm:col-span-2">
                        <legend class="text-sm font-medium text-slate-700">Color en la agenda</legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach (StaffMember::COLORS as $key => $classes)
                                <label wire:key="color-{{ $key }}" class="cursor-pointer">
                                    <input type="radio" wire:model.live="color" value="{{ $key }}" class="peer sr-only">
                                    <span class="flex size-9 items-center justify-center rounded-full ring-2 ring-transparent ring-offset-2 transition peer-checked:ring-slate-900 peer-focus-visible:ring-teal-500 {{ $classes['dot'] }}">
                                        <span class="sr-only">{{ $classes['label'] }}</span>
                                        @if ($color === $key)
                                            <x-icon name="check" class="size-4 text-white" />
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset class="sm:col-span-2">
                        <legend class="text-sm font-medium text-slate-700">Servicios que ofrece</legend>
                        @if ($this->services->isEmpty())
                            <p class="mt-2 text-sm text-slate-500">Todavía no tienes servicios. Créalos en <a href="{{ route('admin.services') }}" wire:navigate class="font-medium text-teal-700 hover:underline">Servicios</a>.</p>
                        @else
                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                @foreach ($this->services as $service)
                                    <label wire:key="offer-{{ $service->id }}" class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm has-checked:border-teal-600 has-checked:bg-teal-50/60">
                                        <input type="checkbox" wire:model="serviceIds" value="{{ $service->id }}" class="size-4 rounded border-slate-300 accent-teal-600">
                                        <span class="min-w-0">
                                            <span class="block truncate font-medium text-slate-900">{{ $service->name }}</span>
                                            @unless ($service->is_active)
                                                <span class="block text-xs text-slate-500">Oculto</span>
                                            @endunless
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        @error('serviceIds')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </fieldset>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelForm" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">{{ $editingId ? 'Guardar cambios' : 'Agregar al equipo' }}</button>
                </div>
            </form>
        </div>
    @endif
</div>
