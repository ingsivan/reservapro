<?php

namespace App\Livewire\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\CancelledBy;
use App\Livewire\Concerns\ManagesBusiness;
use App\Livewire\Concerns\ShowsNotices;
use App\Models\Appointment;
use App\Services\AppointmentBooker;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Weekly calendar of appointments plus the list for the selected day.
 */
#[Layout('layouts::admin')]
#[Title('Agenda')]
class Agenda extends Component
{
    use ManagesBusiness, ShowsNotices;

    #[Url]
    public string $date = '';

    /** Only show appointments with this status ('' shows all). */
    #[Url(as: 'estado', except: '')]
    public string $statusFilter = '';

    /** Appointment open in the side panel. */
    public ?int $openAppointmentId = null;

    public function mount(): void
    {
        $this->date = $this->parseDate($this->date)->toDateString();
    }

    public function selectDate(string $date): void
    {
        $this->showDate($this->parseDate($date));
    }

    public function previousWeek(): void
    {
        $this->showDate($this->selectedDate->subWeek());
    }

    public function nextWeek(): void
    {
        $this->showDate($this->selectedDate->addWeek());
    }

    public function goToToday(): void
    {
        $this->showDate($this->business->now());
    }

    public function filterByStatus(string $status): void
    {
        $this->statusFilter = AppointmentStatus::tryFrom($status)?->value ?? '';
        unset($this->visibleAppointments, $this->dayAppointments);
    }

    public function viewAppointment(int $appointmentId): void
    {
        $this->openAppointmentId = $this->findAppointment($appointmentId)->id;
        unset($this->openAppointment);
    }

    public function closeAppointment(): void
    {
        $this->openAppointmentId = null;
        unset($this->openAppointment);
    }

    public function confirm(int $appointmentId, AppointmentBooker $booker): void
    {
        $booker->confirm($this->findAppointment($appointmentId));
        $this->notify('Cita confirmada. Le avisamos al cliente por correo.');
    }

    public function cancel(int $appointmentId, AppointmentBooker $booker): void
    {
        $booker->cancel($this->findAppointment($appointmentId), CancelledBy::Business);
        $this->notify('Cita cancelada. Le avisamos al cliente por correo.');
    }

    public function markCompleted(int $appointmentId): void
    {
        $this->markPast($appointmentId, AppointmentStatus::Completed);
    }

    public function markNoShow(int $appointmentId): void
    {
        $this->markPast($appointmentId, AppointmentStatus::NoShow);
    }

    #[Computed]
    public function selectedDate(): CarbonImmutable
    {
        return $this->parseDate($this->date);
    }

    /**
     * @return list<CarbonImmutable>
     */
    #[Computed]
    public function weekDays(): array
    {
        $monday = $this->selectedDate->startOfWeek(CarbonImmutable::MONDAY);

        return array_map(fn (int $offset) => $monday->addDays($offset), range(0, 6));
    }

    /**
     * Appointments of the visible week, grouped by local date (Y-m-d).
     *
     * @return \Illuminate\Support\Collection<string, Collection<int, Appointment>>
     */
    #[Computed]
    public function weekAppointments(): \Illuminate\Support\Collection
    {
        $business = $this->business;

        return $business->appointments()
            ->with('service')
            ->where('starts_at', '>=', $this->weekDays[0]->utc())
            ->where('starts_at', '<', $this->weekDays[6]->addDay()->utc())
            ->orderBy('starts_at')
            ->get()
            ->each->setRelation('business', $business)
            ->groupBy(fn (Appointment $appointment) => $appointment->localStartsAt()->toDateString());
    }

    /**
     * Week appointments after applying the status filter.
     *
     * @return \Illuminate\Support\Collection<string, Collection<int, Appointment>>
     */
    #[Computed]
    public function visibleAppointments(): \Illuminate\Support\Collection
    {
        if ($this->statusFilter === '') {
            return $this->weekAppointments;
        }

        return $this->weekAppointments
            ->map(fn (Collection $day) => $day->filter(fn (Appointment $appointment) => $appointment->status->value === $this->statusFilter)->values())
            ->filter(fn (Collection $day) => $day->isNotEmpty());
    }

    /**
     * @return Collection<int, Appointment>
     */
    #[Computed]
    public function dayAppointments(): Collection
    {
        return $this->visibleAppointments->get($this->date, new Collection);
    }

    #[Computed]
    public function openAppointment(): ?Appointment
    {
        return $this->openAppointmentId
            ? $this->business->appointments()->with('service', 'business')->find($this->openAppointmentId)
            : null;
    }

    /**
     * Hour range shown on the weekly grid, from the opening hours and appointments.
     *
     * @return array{start: int, end: int}
     */
    #[Computed]
    public function gridHours(): array
    {
        $schedules = $this->business->schedules;
        $start = $schedules->isEmpty() ? 9 : (int) $schedules->min(fn ($schedule) => (int) substr($schedule->opens_at, 0, 2));
        $end = $schedules->isEmpty() ? 18 : (int) $schedules->max(fn ($schedule) => $schedule->crossesMidnight() ? 24 : (int) ceil(((int) substr($schedule->closes_at, 0, 2) * 60 + (int) substr($schedule->closes_at, 3, 2)) / 60));

        foreach ($this->weekAppointments->flatten() as $appointment) {
            $start = min($start, $appointment->localStartsAt()->hour);
            $end = max($end, $appointment->localEndsAt()->isSameDay($appointment->localStartsAt()) ? (int) ceil(($appointment->localEndsAt()->hour * 60 + $appointment->localEndsAt()->minute) / 60) : 24);
        }

        return ['start' => $start, 'end' => max($end, $start + 1)];
    }

    /**
     * Headline numbers for the selected week.
     *
     * @return array{today: int, pending: int, week: int, revenue: string}
     */
    #[Computed]
    public function stats(): array
    {
        $business = $this->business;
        $today = $business->now()->startOfDay();
        $week = $this->weekAppointments->flatten();

        return [
            'today' => $business->appointments()->blocking()
                ->where('starts_at', '>=', $today->utc())
                ->where('starts_at', '<', $today->addDay()->utc())
                ->count(),
            'pending' => $business->appointments()
                ->where('status', AppointmentStatus::Pending)
                ->where('starts_at', '>=', now())
                ->count(),
            'week' => $week->filter(fn (Appointment $appointment) => $appointment->status !== AppointmentStatus::Cancelled)->count(),
            'revenue' => Money::format($week
                ->filter(fn (Appointment $appointment) => in_array($appointment->status, [AppointmentStatus::Confirmed, AppointmentStatus::Completed], true))
                ->sum('price_cents')),
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.agenda');
    }

    /**
     * Change the selected day and drop the computed values that depend on it.
     */
    private function showDate(CarbonImmutable $date): void
    {
        $this->date = $date->toDateString();
        unset($this->selectedDate, $this->weekDays, $this->weekAppointments, $this->visibleAppointments, $this->dayAppointments, $this->gridHours, $this->stats);
    }

    private function markPast(int $appointmentId, AppointmentStatus $status): void
    {
        $appointment = $this->findAppointment($appointmentId);

        if ($appointment->status->isActive() && $appointment->hasStarted()) {
            $appointment->update(['status' => $status]);
            $this->notify("Cita marcada como «{$status->label()}».");
        }
    }

    private function findAppointment(int $appointmentId): Appointment
    {
        $appointment = $this->business->appointments()->with('service', 'business.owner')->findOrFail($appointmentId);
        unset($this->weekAppointments, $this->visibleAppointments, $this->dayAppointments, $this->stats, $this->openAppointment);

        return $appointment;
    }

    private function parseDate(?string $date): CarbonImmutable
    {
        $timezone = $this->business->timezone;

        try {
            $parsed = $date ? CarbonImmutable::createFromFormat('!Y-m-d', $date, $timezone) : null;
        } catch (InvalidFormatException) {
            $parsed = null;
        }

        return $parsed ?: $this->business->now()->startOfDay();
    }
}
