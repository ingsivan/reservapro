<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'name', 'slug', 'description', 'address', 'phone', 'timezone',
    'slot_interval_minutes', 'min_notice_minutes', 'booking_window_days',
    'cancellation_notice_hours', 'requires_confirmation',
])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'slot_interval_minutes' => 'integer',
            'min_notice_minutes' => 'integer',
            'booking_window_days' => 'integer',
            'cancellation_notice_hours' => 'integer',
            'requires_confirmation' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function blockedDates(): HasMany
    {
        return $this->hasMany(BlockedDate::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * The current moment in the business's own time zone.
     */
    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone);
    }

    /**
     * Up to two letters for the business avatar, skipping short words like "El" or "de".
     */
    public function initials(): string
    {
        $words = collect(preg_split('/\s+/', trim($this->name)))->filter(fn (string $word) => mb_strlen($word) > 2);

        return ($words->isEmpty() ? collect([$this->name]) : $words)
            ->take(2)
            ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }

    /**
     * Opening hours for the public page, grouped by day starting on Monday.
     *
     * @return array<int, array{day: string, ranges: list<string>}>
     */
    public function weeklyHours(): array
    {
        $byDay = $this->schedules->sortBy('opens_at')->groupBy('day_of_week');

        return collect([1, 2, 3, 4, 5, 6, 0])
            ->map(fn (int $day) => [
                'day' => Schedule::dayName($day),
                'ranges' => $byDay->get($day, collect())->map->label()->values()->all(),
            ])
            ->all();
    }

    /**
     * Opening hours with consecutive days that share the same ranges merged,
     * e.g. "Lun – Vie: 10:00 – 14:00, 16:00 – 20:00".
     *
     * @return list<array{days: string, ranges: list<string>}>
     */
    public function compactWeeklyHours(): array
    {
        $groups = [];

        foreach ($this->weeklyHours() as $row) {
            $last = array_key_last($groups);

            if ($last !== null && $groups[$last]['ranges'] === $row['ranges']) {
                $groups[$last]['last'] = $row['day'];
            } else {
                $groups[] = ['first' => $row['day'], 'last' => $row['day'], 'ranges' => $row['ranges']];
            }
        }

        return array_map(fn (array $group) => [
            'days' => $group['first'] === $group['last']
                ? $group['first']
                : mb_substr($group['first'], 0, 3).' – '.mb_substr($group['last'], 0, 3),
            'ranges' => $group['ranges'],
        ], $groups);
    }

    /**
     * Whether the business is open right now and when that changes, for the
     * badge on the public page.
     *
     * @return array{isOpen: bool, label: string}
     */
    public function openingStatus(): array
    {
        $now = $this->now();
        $today = $now->startOfDay();
        $byDay = $this->schedules->groupBy('day_of_week');
        $blocked = $this->blockedDates()
            ->whereBetween('date', [$today->subDay()->toDateString(), $today->addWeek()->toDateString().' 23:59:59'])
            ->pluck('date')
            ->map(fn ($date) => CarbonImmutable::parse($date)->toDateString())
            ->flip();

        $nextOpening = null;

        // Yesterday's overnight window may still be open.
        for ($day = $today->subDay(); $day->lte($today->addWeek()); $day = $day->addDay()) {
            if ($blocked->has($day->toDateString())) {
                continue;
            }

            foreach ($byDay->get($day->dayOfWeek, collect()) as $schedule) {
                $opensAt = $day->setTimeFromTimeString($schedule->opensAtTime());
                $closesAt = $day->addDays($schedule->crossesMidnight() ? 1 : 0)->setTimeFromTimeString($schedule->closesAtTime());

                if ($now->gte($opensAt) && $now->lt($closesAt)) {
                    return ['isOpen' => true, 'label' => 'Abierto · cierra a las '.$closesAt->format('H:i')];
                }

                if ($opensAt->gt($now) && ($nextOpening === null || $opensAt->lt($nextOpening))) {
                    $nextOpening = $opensAt;
                }
            }
        }

        if ($nextOpening === null) {
            return ['isOpen' => false, 'label' => 'Cerrado'];
        }

        $when = match (true) {
            $nextOpening->isSameDay($today) => 'hoy',
            $nextOpening->isSameDay($today->addDay()) => 'mañana',
            default => 'el '.$nextOpening->translatedFormat('l'),
        };

        return ['isOpen' => false, 'label' => "Cerrado · abre {$when} a las {$nextOpening->format('H:i')}"];
    }
}
