<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Schedule;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Works out which start times are free for a service.
 *
 * A slot is free when it fits inside one of the business's opening windows
 * for that day, the day is not blocked, it respects the minimum notice and
 * the booking window, and it does not overlap an appointment that still
 * holds its time. All calculations happen in the business's time zone.
 */
class AvailabilityService
{
    /**
     * Free start times for one day, in the business's time zone.
     *
     * @return list<CarbonImmutable>
     */
    public function slotsFor(Service $service, CarbonImmutable|string $date): array
    {
        $day = $this->localDay($service->business, $date);

        return $this->slotsBetween($service, $day, $day)[$day->toDateString()];
    }

    /**
     * Free start times for every day in a range, keyed by date (Y-m-d).
     *
     * @return array<string, list<CarbonImmutable>>
     */
    public function slotsBetween(Service $service, CarbonImmutable|string $from, CarbonImmutable|string $to): array
    {
        $business = $service->business;
        $from = $this->localDay($business, $from);
        $to = $this->localDay($business, $to);

        $schedules = $business->schedules()->get()->groupBy('day_of_week');
        $blockedDates = $business->blockedDates()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
            ->pluck('date')
            ->map(fn ($date) => CarbonImmutable::parse($date)->toDateString())
            ->flip();

        // Overnight windows can spill into the day after the range.
        $taken = Appointment::query()
            ->where('business_id', $business->id)
            ->blocking()
            ->overlapping($from, $to->addDays(2))
            ->get(['starts_at', 'ends_at']);

        $today = $business->now()->startOfDay();
        $lastBookableDay = $today->addDays($business->booking_window_days);
        $earliestStart = $business->now()->addMinutes($business->min_notice_minutes);

        $slots = [];

        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $key = $day->toDateString();
            $slots[$key] = [];

            if ($day->lt($today) || $day->gt($lastBookableDay) || $blockedDates->has($key)) {
                continue;
            }

            foreach ($schedules->get($day->dayOfWeek, collect()) as $schedule) {
                foreach ($this->slotsInWindow($schedule, $day, $service, $business) as $start) {
                    $end = $start->addMinutes($service->duration_minutes);

                    if ($start->gte($earliestStart) && ! $this->overlapsAny($taken, $start, $end)) {
                        $slots[$key][$start->getTimestamp()] = $start;
                    }
                }
            }

            ksort($slots[$key]);
            $slots[$key] = array_values($slots[$key]);
        }

        return $slots;
    }

    /**
     * Days within the booking window that have at least one free slot.
     *
     * @return list<CarbonImmutable>
     */
    public function availableDays(Service $service): array
    {
        $business = $service->business;
        $today = $business->now()->startOfDay();
        $slots = $this->slotsBetween($service, $today, $today->addDays($business->booking_window_days));

        return collect($slots)
            ->filter()
            ->keys()
            ->map(fn (string $date) => CarbonImmutable::parse($date, $business->timezone))
            ->values()
            ->all();
    }

    /**
     * Whether the exact start time is still bookable for the service.
     */
    public function isAvailable(Service $service, CarbonImmutable $start): bool
    {
        $local = $start->setTimezone($service->business->timezone);
        $day = $local->startOfDay();

        // The slot may belong to the previous day's overnight window.
        return collect($this->slotsBetween($service, $day->subDay(), $day))
            ->flatten()
            ->contains(fn (CarbonImmutable $slot) => $slot->getTimestamp() === $local->getTimestamp());
    }

    /**
     * Start times on the business's grid that fit inside one opening window.
     *
     * @return list<CarbonImmutable>
     */
    private function slotsInWindow(Schedule $schedule, CarbonImmutable $day, Service $service, Business $business): array
    {
        $opensAt = $day->setTimeFromTimeString($schedule->opensAtTime());
        $closesAt = $day->setTimeFromTimeString($schedule->closesAtTime());

        if ($schedule->crossesMidnight()) {
            $closesAt = $day->addDay()->setTimeFromTimeString($schedule->closesAtTime());
        }

        $starts = [];

        for ($start = $opensAt; $start->addMinutes($service->duration_minutes)->lte($closesAt); $start = $start->addMinutes($business->slot_interval_minutes)) {
            $starts[] = $start;
        }

        return $starts;
    }

    /**
     * @param  Collection<int, Appointment>  $taken
     */
    private function overlapsAny(Collection $taken, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $taken->contains(
            fn (Appointment $appointment) => $appointment->starts_at->lt($end) && $appointment->ends_at->gt($start)
        );
    }

    private function localDay(Business $business, CarbonImmutable|string $date): CarbonImmutable
    {
        if ($date instanceof CarbonImmutable) {
            return CarbonImmutable::parse($date->setTimezone($business->timezone)->toDateString(), $business->timezone);
        }

        return CarbonImmutable::parse($date, $business->timezone)->startOfDay();
    }
}
