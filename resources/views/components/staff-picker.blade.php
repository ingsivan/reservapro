@props(['staff', 'selected' => null, 'select' => 'selectStaff', 'anyLabel' => 'Quien esté libre'])

{{-- "With whom?" choice: whoever is free, or one person of the team. --}}
<div {{ $attributes }}>
    <p class="text-sm font-medium text-slate-700">¿Con quién?</p>
    <div class="mt-2 flex flex-wrap gap-2" role="group" aria-label="Elegir con quién">
        <button type="button" wire:click="{{ $select }}(null)" aria-pressed="{{ $selected === null ? 'true' : 'false' }}"
                @class([
                    'inline-flex shrink-0 items-center gap-2 rounded-full py-1 pr-3.5 pl-1 text-sm font-medium whitespace-nowrap ring-1 transition ring-inset',
                    'bg-teal-600 text-white ring-teal-600' => $selected === null,
                    'bg-white text-slate-700 ring-slate-200 hover:ring-teal-300' => $selected !== null,
                ])>
            <span @class(['flex size-7 items-center justify-center rounded-full', 'bg-white/20' => $selected === null, 'bg-slate-100 text-slate-500' => $selected !== null])><x-icon name="users" class="size-4" /></span>
            {{ $anyLabel }}
        </button>
        @foreach ($staff as $person)
            <button type="button" wire:click="{{ $select }}({{ $person->id }})" wire:key="pick-staff-{{ $person->id }}" aria-pressed="{{ $selected === $person->id ? 'true' : 'false' }}"
                    @class([
                        'inline-flex shrink-0 items-center gap-2 rounded-full py-1 pr-3.5 pl-1 text-sm font-medium whitespace-nowrap ring-1 transition ring-inset',
                        'bg-teal-600 text-white ring-teal-600' => $selected === $person->id,
                        'bg-white text-slate-700 ring-slate-200 hover:ring-teal-300' => $selected !== $person->id,
                    ])>
                <x-staff-avatar :staff-member="$person" class="size-7 text-xs" />
                <span class="text-left leading-4">
                    {{ $person->name }}
                    @if ($person->title)
                        <span @class(['block text-xs font-normal', 'text-teal-50' => $selected === $person->id, 'text-slate-500' => $selected !== $person->id])>{{ $person->title }}</span>
                    @endif
                </span>
            </button>
        @endforeach
    </div>
</div>
