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
}
