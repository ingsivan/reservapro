<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Business;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Appointments and income of a business over its last few calendar months,
 * counted in the business's time zone. The current month is included.
 */
class BusinessReport
{
    /** Periods offered in the reports screen, in months. */
    public const PERIODS = [3, 6, 12];

    private ?Collection $appointments = null;

    public function __construct(public readonly Business $business, public readonly int $months = 6) {}

    /**
     * First day of the oldest month, local midnight.
     */
    public function from(): CarbonImmutable
    {
        return $this->business->now()->startOfMonth()->subMonths($this->months - 1);
    }

    /**
     * First day after the current month, local midnight.
     */
    public function until(): CarbonImmutable
    {
        return $this->business->now()->startOfMonth()->addMonth();
    }

    /**
     * Every appointment in the period, oldest first, with its service.
     *
     * @return Collection<int, Appointment>
     */
    public function appointments(): Collection
    {
        return $this->appointments ??= $this->business->appointments()
            ->with('service', 'staffMember')
            ->where('starts_at', '>=', $this->from()->utc())
            ->where('starts_at', '<', $this->until()->utc())
            ->orderBy('starts_at')
            ->get()
            ->each->setRelation('business', $this->business);
    }

    /**
     * One row per month, oldest first, including months without appointments.
     *
     * @return list<array{month: CarbonImmutable, revenueCents: int, appointments: int, cancelled: int, noShows: int}>
     */
    public function monthly(): array
    {
        $byMonth = $this->appointments()->groupBy(fn (Appointment $appointment) => $appointment->localStartsAt()->format('Y-m'));
        $rows = [];

        for ($month = $this->from(); $month->lt($this->until()); $month = $month->addMonth()) {
            $appointments = $byMonth->get($month->format('Y-m'), new Collection);
            $rows[] = ['month' => $month, ...$this->summarize($appointments)];
        }

        return $rows;
    }

    /**
     * @return array{revenueCents: int, appointments: int, cancelled: int, noShows: int, averageTicketCents: int, cancellationRate: float}
     */
    public function totals(): array
    {
        $totals = $this->summarize($this->appointments());
        $paid = $this->paid($this->appointments())->count();
        $all = $this->appointments()->count();

        return $totals + [
            'averageTicketCents' => $paid ? intdiv($totals['revenueCents'], $paid) : 0,
            'cancellationRate' => $all ? $totals['cancelled'] / $all : 0.0,
        ];
    }

    /**
     * Services sorted by income, with their share of the total.
     *
     * @return list<array{name: string, appointments: int, revenueCents: int, share: float}>
     */
    public function byService(): array
    {
        return $this->breakdown($this->appointments(), 'service_id', fn (Appointment $appointment) => $appointment->service->name);
    }

    /**
     * People of the team sorted by income, with their share of the total.
     * Empty for a business without a team.
     *
     * @return list<array{name: string, appointments: int, revenueCents: int, share: float}>
     */
    public function byStaff(): array
    {
        $withStaff = $this->appointments()->whereNotNull('staff_member_id');

        return $withStaff->isEmpty() ? [] : $this->breakdown($withStaff, 'staff_member_id', fn (Appointment $appointment) => $appointment->staffMember->name);
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     * @param  callable(Appointment): string  $name
     * @return list<array{name: string, appointments: int, revenueCents: int, share: float}>
     */
    private function breakdown(Collection $appointments, string $groupBy, callable $name): array
    {
        $totalRevenue = max(1, $this->summarize($this->appointments())['revenueCents']);

        return $appointments
            ->groupBy($groupBy)
            ->map(function (Collection $group) use ($totalRevenue, $name) {
                $summary = $this->summarize($group);

                return [
                    'name' => $name($group->first()),
                    'appointments' => $summary['appointments'],
                    'revenueCents' => $summary['revenueCents'],
                    'share' => $summary['revenueCents'] / $totalRevenue,
                ];
            })
            ->sortByDesc('revenueCents')
            ->values()
            ->all();
    }

    /**
     * Income counts confirmed and completed appointments; "appointments"
     * counts everything except cancellations.
     *
     * @param  Collection<int, Appointment>  $appointments
     * @return array{revenueCents: int, appointments: int, cancelled: int, noShows: int}
     */
    private function summarize(Collection $appointments): array
    {
        return [
            'revenueCents' => (int) $this->paid($appointments)->sum('price_cents'),
            'appointments' => $appointments->where('status', '!=', AppointmentStatus::Cancelled)->count(),
            'cancelled' => $appointments->where('status', AppointmentStatus::Cancelled)->count(),
            'noShows' => $appointments->where('status', AppointmentStatus::NoShow)->count(),
        ];
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     * @return Collection<int, Appointment>
     */
    private function paid(Collection $appointments): Collection
    {
        return $appointments->filter(fn (Appointment $appointment) => in_array($appointment->status, [AppointmentStatus::Confirmed, AppointmentStatus::Completed], true));
    }
}
