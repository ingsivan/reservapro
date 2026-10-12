@props([
    'business',
    'availability',
    'date' => null,
    'selected' => null,
    'selectDate' => 'selectDate',
    'selectSlot' => 'selectSlot',
])

{{--
    Day strip plus the free times of the chosen day, grouped into morning,
    afternoon and evening. Calls the Livewire methods named in select-date
    and select-slot. Used by the booking wizard, the customer's reschedule
    page and the agenda's side panel.
--}}

@php
    use App\Support\TimeSlots;
    use Carbon\CarbonImmutable;

    $selectedDay = $date ? CarbonImmutable::parse($date) : null;
    $openWeekdays = $business->schedules->pluck('day_of_week')->unique()->all();
    $slotGroups = TimeSlots::groupByPartOfDay($date ? ($availability[$date] ?? []) : []);
@endphp

<div {{ $attributes }}>
    <div x-data="{ scroll(direction) { $refs.days.scrollBy({ left: direction * $refs.days.clientWidth * 0.8, behavior: 'smooth' }) } }"
         x-init="$nextTick(() => { const selected = $refs.days.querySelector('[aria-selected=true]'); if (selected) $refs.days.scrollLeft = selected.offsetLeft - $refs.days.offsetLeft - 8 })">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-slate-700 first-letter:uppercase">{{ ($selectedDay ?? $business->now())->translatedFormat('F Y') }}</p>
            <div class="flex gap-1">
                <button type="button" x-on:click="scroll(-1)" class="rounded-lg border border-slate-200 p-1.5 text-slate-600 hover:bg-slate-50" aria-label="Días anteriores"><x-icon name="chevron-left" class="size-4" /></button>
                <button type="button" x-on:click="scroll(1)" class="rounded-lg border border-slate-200 p-1.5 text-slate-600 hover:bg-slate-50" aria-label="Días siguientes"><x-icon name="chevron-right" class="size-4" /></button>
            </div>
        </div>
        <div x-ref="days" class="no-scrollbar -mx-1 mt-3 flex snap-x gap-2 overflow-x-auto px-1 pb-1" role="listbox" aria-label="Días">
            @foreach ($availability as $day => $daySlots)
                @php
                    $dayDate = CarbonImmutable::parse($day);
                    $isSelected = $date === $day;
                    $isFull = empty($daySlots);
                @endphp
                <button type="button" wire:click="{{ $selectDate }}('{{ $day }}')" wire:key="day-{{ $day }}" @disabled($isFull)
                        role="option" aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                        @class([
                            'flex w-[4.25rem] shrink-0 snap-start flex-col items-center rounded-xl border px-1 py-2.5 transition',
                            'border-teal-600 bg-teal-600 text-white shadow-md shadow-teal-600/20' => $isSelected,
                            'border-slate-200 text-slate-700 hover:border-teal-400 hover:bg-teal-50/50' => ! $isSelected && ! $isFull,
                            'cursor-not-allowed border-dashed border-slate-200 text-slate-300' => $isFull,
                        ])>
                    <span class="text-xs font-medium uppercase">{{ rtrim($dayDate->translatedFormat('D'), '.') }}</span>
                    <span class="text-xl leading-7 font-bold">{{ $dayDate->day }}</span>
                    <span @class(['text-[0.68rem] leading-4', 'text-teal-50' => $isSelected, 'text-emerald-600' => ! $isSelected && ! $isFull])>
                        {{ $isFull ? (in_array($dayDate->dayOfWeek, $openWeekdays) ? 'Sin lugar' : 'Cerrado') : trans_choice(':count libre|:count libres', count($daySlots)) }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    @if ($selectedDay)
        <div class="mt-6 border-t border-slate-100 pt-5" wire:loading.class="opacity-50" wire:target="{{ $selectDate }}">
            <p class="font-medium text-slate-900 first-letter:uppercase">{{ $selectedDay->translatedFormat('l j \d\e F') }}</p>
            <div class="mt-4 space-y-5">
                @foreach ($slotGroups as $group)
                    <div wire:key="group-{{ $date }}-{{ $group['label'] }}">
                        <p class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                            <x-icon :name="$group['icon']" class="size-4" /> {{ $group['label'] }}
                        </p>
                        <div class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5">
                            @foreach ($group['slots'] as $start)
                                <button type="button" wire:click="{{ $selectSlot }}('{{ $start->toIso8601String() }}')" wire:key="slot-{{ $start->getTimestamp() }}"
                                        @class([
                                            'rounded-lg border px-3 py-2.5 text-sm font-semibold tabular-nums transition',
                                            'border-teal-600 bg-teal-600 text-white' => $selected === $start->toIso8601String(),
                                            'border-slate-200 text-slate-700 hover:border-teal-500 hover:bg-teal-50 hover:text-teal-800' => $selected !== $start->toIso8601String(),
                                        ])>
                                    {{ $start->format('H:i') }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
