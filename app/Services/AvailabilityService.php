<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\StaffMember;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Works out which start times are free for a service.
 *
 * A slot is free when it fits inside one of the business's opening windows
 * for that day, the day is not blocked, it respects the minimum notice and
 * the booking window, and it does not overlap an appointment that still
 * holds its time. All calculations happen in the business's time zone.
 *
 * A business with a team books people, not the business: a slot is free
 * when at least one staff member who offers the service works then and has
 * no appointment at that time. Without a team, the business itself is the
 * only agenda.
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
     * Pass $ignoring when moving an appointment, so its current time counts as
     * free. The business itself books on behalf of customers, so with
     * $forBusiness the minimum notice doesn't apply (only past times are hidden).
     * Pass $staffMember to only see that person's free times.
     *
     * @return array<string, list<CarbonImmutable>>
     */
    public function slotsBetween(Service $service, CarbonImmutable|string $from, CarbonImmutable|string $to, ?Appointment $ignoring = null, bool $forBusiness = false, ?StaffMember $staffMember = null): array
    {
        $business = $service->business;
        $from = $this->localDay($business, $from);
        $to = $this->localDay($business, $to);

        $agendas = $this->agendasFor($service, $staffMember);
        $allSchedules = Schedule::query()->where('business_id', $business->id)->get();
        $businessSchedules = $allSchedules->whereNull('staff_member_id');
        $blockedDates = $business->blockedDates()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
            ->pluck('date')
            ->map(fn ($date) => CarbonImmutable::parse($date)->toDateString())
            ->flip();

        // Overnight windows can spill into the day after the range.
        $taken = Appointment::query()
            ->where('business_id', $business->id)
            ->blocking()
            ->when($ignoring, fn ($query) => $query->whereKeyNot($ignoring->getKey()))
            ->overlapping($from, $to->addDays(2))
            ->get(['starts_at', 'ends_at', 'staff_member_id']);

        $today = $business->now()->startOfDay();
        $lastBookableDay = $today->addDays($business->booking_window_days);
        $earliestStart = $forBusiness ? $business->now() : $business->now()->addMinutes($business->min_notice_minutes);

        // Each agenda: its weekly windows by day and the appointments it can't overlap.
        $agendas = array_map(function (?StaffMember $person) use ($allSchedules, $businessSchedules, $taken) {
            $own = $person ? $allSchedules->where('staff_member_id', $person->id) : collect();

            return [
                'schedules' => ($own->isNotEmpty() ? $own : $businessSchedules)->groupBy('day_of_week'),
                // Appointments without a staff member (from before the team) block everyone.
                'taken' => $person ? $taken->filter(fn (Appointment $appointment) => in_array($appointment->staff_member_id, [null, $person->id], true)) : $taken,
            ];
        }, $agendas);

        $slots = [];

        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $key = $day->toDateString();
            $slots[$key] = [];

            if ($day->lt($today) || $day->gt($lastBookableDay) || $blockedDates->has($key)) {
                continue;
            }

            foreach ($agendas as $agenda) {
                foreach ($agenda['schedules']->get($day->dayOfWeek, collect()) as $schedule) {
                    foreach ($this->slotsInWindow($schedule, $day, $service, $business) as $start) {
                        $end = $start->addMinutes($service->duration_minutes);

                        if ($start->gte($earliestStart) && ! isset($slots[$key][$start->getTimestamp()]) && ! $this->overlapsAny($agenda['taken'], $start, $end)) {
                            $slots[$key][$start->getTimestamp()] = $start;
                        }
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
     * Free times an appointment could move to, in the business's booking
     * window and without its current start time, keyed by date (Y-m-d).
     *
     * @return array<string, list<CarbonImmutable>>
     */
    public function slotsToReschedule(Appointment $appointment, bool $forBusiness = false): array
    {
        $business = $appointment->business;
        $service = $appointment->service->setRelation('business', $business);
        $today = $business->now()->startOfDay();
        $currentStart = $appointment->starts_at->getTimestamp();

        return array_map(
            fn (array $slots) => array_values(array_filter($slots, fn (CarbonImmutable $start) => $start->getTimestamp() !== $currentStart)),
            $this->slotsBetween($service, $today, $today->addDays($business->booking_window_days), $appointment, $forBusiness, $appointment->staffMember),
        );
    }

    /**
     * Whether the exact start time is still bookable for the service (with
     * that staff member, when one is given).
     */
    public function isAvailable(Service $service, CarbonImmutable $start, ?Appointment $ignoring = null, bool $forBusiness = false, ?StaffMember $staffMember = null): bool
    {
        $local = $start->setTimezone($service->business->timezone);
        $day = $local->startOfDay();

        // The slot may belong to the previous day's overnight window.
        return collect($this->slotsBetween($service, $day->subDay(), $day, $ignoring, $forBusiness, $staffMember))
            ->flatten()
            ->contains(fn (CarbonImmutable $slot) => $slot->getTimestamp() === $local->getTimestamp());
    }

    /**
     * Staff members who offer the service and are free at that exact time,
     * the least busy that day first. Empty for a business without a team.
     *
     * @return list<StaffMember>
     */
    public function freeStaffAt(Service $service, CarbonImmutable $start, ?Appointment $ignoring = null, bool $forBusiness = false): array
    {
        $business = $service->business;
        $day = $start->setTimezone($business->timezone)->startOfDay();

        return $this->staffFor($service)
            ->filter(fn (StaffMember $staffMember) => $this->isAvailable($service, $start, $ignoring, $forBusiness, $staffMember))
            ->sortBy(fn (StaffMember $staffMember) => [
                $staffMember->appointments()->blocking()
                    ->where('starts_at', '>=', $day->utc())
                    ->where('starts_at', '<', $day->addDay()->utc())
                    ->count(),
                $staffMember->id,
            ])
            ->values()
            ->all();
    }

    /**
     * Active staff members who offer the service.
     *
     * @return EloquentCollection<int, StaffMember>
     */
    public function staffFor(Service $service): EloquentCollection
    {
        return $service->staffMembers()
            ->where('staff_members.business_id', $service->business_id)
            ->active()
            ->orderBy('name')
            ->get()
            ->each->setRelation('business', $service->business);
    }

    /**
     * Whose agendas to look at: one person, everyone who offers the service,
     * or the business itself (null) when it has no team.
     *
     * @return list<?StaffMember>
     */
    private function agendasFor(Service $service, ?StaffMember $staffMember): array
    {
        if ($staffMember !== null) {
            return [$staffMember];
        }

        return $service->business->hasTeam() ? $this->staffFor($service)->all() : [null];
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
