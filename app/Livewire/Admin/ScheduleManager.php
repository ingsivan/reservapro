<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ManagesBusiness;
use App\Livewire\Concerns\ShowsNotices;
use App\Models\BlockedDate;
use App\Models\Schedule;
use App\Models\StaffMember;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Weekly opening hours and closed days. With a team, each person works the
 * business's hours or hours of their own.
 */
#[Layout('layouts::admin')]
#[Title('Horario')]
class ScheduleManager extends Component
{
    use ManagesBusiness, ShowsNotices;

    /** Whose hours are shown: a staff member, or the business (null). */
    #[Url(as: 'equipo')]
    public ?int $staffMemberId = null;

    public int|string $newDay = 1;

    public string $newOpensAt = '09:00';

    public string $newClosesAt = '18:00';

    public string $blockedDate = '';

    public string $blockedReason = '';

    public function mount(): void
    {
        $this->showHoursOf($this->staffMemberId);
    }

    public function showHoursOf(?int $staffMemberId): void
    {
        $this->staffMemberId = $staffMemberId ? $this->business->staffMembers()->find($staffMemberId)?->id : null;
        $this->resetValidation();
        unset($this->staffMember, $this->schedulesByDay);
    }

    /**
     * Start the staff member's own hours from a copy of the business's.
     */
    public function useOwnHours(): void
    {
        $staffMember = $this->staffMember;

        if ($staffMember === null || $staffMember->schedules()->exists()) {
            return;
        }

        foreach ($this->business->schedules()->get() as $schedule) {
            $staffMember->schedules()->create([
                'business_id' => $this->business->id,
                'day_of_week' => $schedule->day_of_week,
                'opens_at' => $schedule->opensAtTime(),
                'closes_at' => $schedule->closesAtTime(),
            ]);
        }

        $this->notify("Copiamos el horario del negocio. Ajústalo para {$staffMember->firstName()}.");
        unset($this->schedulesByDay);
    }

    public function useBusinessHours(): void
    {
        $staffMember = $this->staffMember;

        if ($staffMember === null) {
            return;
        }

        $staffMember->schedules()->delete();
        $this->notify("{$staffMember->firstName()} vuelve a trabajar en el horario del negocio.");
        unset($this->schedulesByDay);
    }

    public function addWindow(): void
    {
        if ($this->followsBusinessHours) {
            return;
        }

        $validated = $this->validate([
            'newDay' => ['required', 'integer', 'between:0,6'],
            'newOpensAt' => ['required', 'date_format:H:i'],
            'newClosesAt' => ['required', 'date_format:H:i', 'different:newOpensAt'],
        ], [
            'newClosesAt.different' => 'La hora de cierre debe ser distinta a la de apertura.',
        ]);

        $day = (int) $validated['newDay'];

        if ($this->overlapsExistingWindow($day, $validated['newOpensAt'], $validated['newClosesAt'])) {
            $this->addError('newOpensAt', 'Ese horario se cruza con otro del mismo día.');

            return;
        }

        $this->editableSchedules()->create([
            'business_id' => $this->business->id,
            'day_of_week' => $day,
            'opens_at' => $validated['newOpensAt'],
            'closes_at' => $validated['newClosesAt'],
        ]);

        $this->notify('Horario agregado.');
        unset($this->schedulesByDay);
    }

    public function removeWindow(int $scheduleId): void
    {
        $this->editableSchedules()->findOrFail($scheduleId)->delete();
        $this->notify('Horario eliminado. Las citas ya reservadas se mantienen.');
        unset($this->schedulesByDay);
    }

    public function blockDate(): void
    {
        $business = $this->business;
        $validated = $this->validate([
            'blockedDate' => [
                'required', 'date_format:Y-m-d', 'after_or_equal:'.$business->now()->toDateString(),
                function (string $attribute, string $value, Closure $fail) use ($business) {
                    if ($business->blockedDates()->whereDate('date', $value)->exists()) {
                        $fail('Ese día ya está bloqueado.');
                    }
                },
            ],
            'blockedReason' => ['nullable', 'string', 'max:100'],
        ], [
            'blockedDate.after_or_equal' => 'Elige hoy o una fecha futura.',
        ]);

        $business->blockedDates()->create([
            'date' => $validated['blockedDate'],
            'reason' => $validated['blockedReason'] ?: null,
        ]);

        $affected = $business->appointments()->blocking()
            ->where('starts_at', '>=', CarbonImmutable::parse($validated['blockedDate'], $business->timezone)->utc())
            ->where('starts_at', '<', CarbonImmutable::parse($validated['blockedDate'], $business->timezone)->addDay()->utc())
            ->count();

        $this->reset('blockedDate', 'blockedReason');
        $this->notify($affected
            ? "Día bloqueado. Ojo: ya hay {$affected} cita(s) ese día; revísalas en la agenda."
            : 'Día bloqueado. Nadie podrá reservar esa fecha.', isError: $affected > 0);
        unset($this->blockedDates);
    }

    public function unblockDate(int $blockedDateId): void
    {
        $this->business->blockedDates()->findOrFail($blockedDateId)->delete();
        $this->notify('Día desbloqueado.');
        unset($this->blockedDates);
    }

    #[Computed]
    public function staffMember(): ?StaffMember
    {
        return $this->staffMemberId ? $this->business->staffMembers()->find($this->staffMemberId) : null;
    }

    /**
     * @return Collection<int, StaffMember>
     */
    #[Computed]
    public function staffMembers(): Collection
    {
        return $this->business->staffMembers()->withCount('schedules')->orderByDesc('is_active')->orderBy('name')->get();
    }

    /**
     * Whether the selected staff member works the business's hours.
     */
    #[Computed]
    public function followsBusinessHours(): bool
    {
        return $this->staffMember !== null && ! $this->staffMember->schedules()->exists();
    }

    /**
     * The windows shown: the business's, or the staff member's own (or the
     * business's again, read only, when they don't have any).
     *
     * @return array<int, array{day: int, name: string, windows: Collection<int, Schedule>}>
     */
    #[Computed]
    public function schedulesByDay(): array
    {
        unset($this->followsBusinessHours);
        $query = $this->followsBusinessHours ? $this->business->schedules() : $this->editableSchedules();
        $schedules = $query->orderBy('opens_at')->get()->groupBy('day_of_week');

        return collect([1, 2, 3, 4, 5, 6, 0])
            ->map(fn (int $day) => [
                'day' => $day,
                'name' => Schedule::dayName($day),
                'windows' => $schedules->get($day, new Collection),
            ])
            ->all();
    }

    /**
     * @return Collection<int, BlockedDate>
     */
    #[Computed]
    public function blockedDates(): Collection
    {
        return $this->business->blockedDates()
            ->where('date', '>=', $this->business->now()->toDateString())
            ->orderBy('date')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.admin.schedule-manager');
    }

    /**
     * The schedules being edited: the business's, or the staff member's own.
     */
    private function editableSchedules(): HasMany
    {
        return $this->staffMember?->schedules() ?? $this->business->schedules();
    }

    /**
     * Compare minute ranges; windows that cross midnight end after 24:00.
     */
    private function overlapsExistingWindow(int $day, string $opensAt, string $closesAt): bool
    {
        [$start, $end] = $this->minuteRange($opensAt, $closesAt);

        return $this->editableSchedules()->where('day_of_week', $day)->get()
            ->contains(function (Schedule $schedule) use ($start, $end) {
                [$otherStart, $otherEnd] = $this->minuteRange($schedule->opensAtTime(), $schedule->closesAtTime());

                return $start < $otherEnd && $end > $otherStart;
            });
    }

    /**
     * @return array{int, int}
     */
    private function minuteRange(string $opensAt, string $closesAt): array
    {
        $toMinutes = fn (string $time) => (int) substr($time, 0, 2) * 60 + (int) substr($time, 3, 2);
        $start = $toMinutes($opensAt);
        $end = $toMinutes($closesAt);

        return [$start, $end <= $start ? $end + 1440 : $end];
    }
}
