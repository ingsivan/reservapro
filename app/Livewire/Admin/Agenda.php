<?php

namespace App\Livewire\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\CancelledBy;
use App\Exceptions\SlotUnavailableException;
use App\Livewire\Concerns\ManagesBusiness;
use App\Livewire\Concerns\ShowsNotices;
use App\Models\Appointment;
use App\Models\StaffMember;
use App\Services\AppointmentBooker;
use App\Services\AvailabilityService;
use App\Support\CalendarLayout;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
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

    /** Only show appointments of this staff member ('' shows everyone's). */
    #[Url(as: 'equipo', except: '')]
    public string $staffFilter = '';

    /** Appointment open in the side panel. */
    public ?int $openAppointmentId = null;

    /** Whether the side panel shows the picker to move the open appointment. */
    public bool $rescheduling = false;

    public ?string $rescheduleDate = null;

    /** New start time as an ISO 8601 string with the business's offset. */
    public ?string $rescheduleStartsAt = null;

    public function mount(): void
    {
        $this->date = $this->parseDate($this->date)->toDateString();
        $this->filterByStaff($this->staffFilter);
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

    /**
     * Show the day of an appointment just created in the new-appointment panel.
     */
    #[On('appointment-created')]
    public function showCreatedAppointment(string $date): void
    {
        $this->showDate($this->parseDate($date));
    }

    public function filterByStatus(string $status): void
    {
        $this->statusFilter = AppointmentStatus::tryFrom($status)?->value ?? '';
        unset($this->visibleAppointments, $this->dayAppointments, $this->calendarLanes);
    }

    public function filterByStaff(string $staffMemberId): void
    {
        $this->staffFilter = (string) ($this->staffMembers->firstWhere('id', (int) $staffMemberId)?->id ?? '');
        unset($this->visibleAppointments, $this->dayAppointments, $this->calendarLanes);
    }

    public function viewAppointment(int $appointmentId): void
    {
        $this->openAppointmentId = $this->findAppointment($appointmentId)->id;
        $this->stopRescheduling();
    }

    public function closeAppointment(): void
    {
        $this->openAppointmentId = null;
        $this->stopRescheduling();
        unset($this->openAppointment);
    }

    /**
     * Open the appointment in the side panel with the picker for a new time.
     */
    public function startRescheduling(int $appointmentId): void
    {
        $this->viewAppointment($appointmentId);
        $appointment = $this->openAppointment;

        if (! $appointment->status->isActive() || $appointment->hasStarted()) {
            return;
        }

        $currentDate = $appointment->localStartsAt()->toDateString();
        $this->rescheduling = true;
        $this->rescheduleDate = ! empty($this->rescheduleAvailability[$currentDate])
            ? $currentDate
            : collect($this->rescheduleAvailability)->filter()->keys()->first();
    }

    public function stopRescheduling(): void
    {
        $this->reset('rescheduling', 'rescheduleDate', 'rescheduleStartsAt');
        $this->resetErrorBag('rescheduleSlot');
        unset($this->rescheduleAvailability);
    }

    public function selectRescheduleDate(string $date): void
    {
        if (array_key_exists($date, $this->rescheduleAvailability)) {
            $this->rescheduleDate = $date;
            $this->rescheduleStartsAt = null;
        }
    }

    public function selectRescheduleSlot(string $slot): void
    {
        $this->rescheduleStartsAt = collect($this->rescheduleAvailability[$this->rescheduleDate] ?? [])
            ->contains(fn (CarbonImmutable $start) => $start->toIso8601String() === $slot) ? $slot : null;
        $this->resetErrorBag('rescheduleSlot');
    }

    public function reschedule(AppointmentBooker $booker): void
    {
        if ($this->openAppointmentId === null || $this->rescheduleStartsAt === null) {
            return;
        }

        $appointment = $this->findAppointment($this->openAppointmentId);

        try {
            $booker->reschedule($appointment, CarbonImmutable::parse($this->rescheduleStartsAt), CancelledBy::Business);
        } catch (SlotUnavailableException $exception) {
            $this->rescheduleStartsAt = null;
            unset($this->rescheduleAvailability);
            $this->addError('rescheduleSlot', $exception->getMessage());

            return;
        }

        $this->stopRescheduling();
        $this->showDate($appointment->localStartsAt());
        $this->notify('Cita reprogramada.'.$this->customerNotice($appointment));
    }

    public function confirm(int $appointmentId, AppointmentBooker $booker): void
    {
        $appointment = $this->findAppointment($appointmentId);
        $booker->confirm($appointment);
        $this->notify('Cita confirmada.'.$this->customerNotice($appointment));
    }

    public function cancel(int $appointmentId, AppointmentBooker $booker): void
    {
        $appointment = $this->findAppointment($appointmentId);
        $booker->cancel($appointment, CancelledBy::Business);
        $this->notify('Cita cancelada.'.$this->customerNotice($appointment));
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
            ->with('service', 'staffMember')
            ->where('starts_at', '>=', $this->weekDays[0]->utc())
            ->where('starts_at', '<', $this->weekDays[6]->addDay()->utc())
            ->orderBy('starts_at')
            ->get()
            ->each->setRelation('business', $business)
            ->groupBy(fn (Appointment $appointment) => $appointment->localStartsAt()->toDateString());
    }

    /**
     * Week appointments after applying the status and staff filters.
     *
     * @return \Illuminate\Support\Collection<string, Collection<int, Appointment>>
     */
    #[Computed]
    public function visibleAppointments(): \Illuminate\Support\Collection
    {
        if ($this->statusFilter === '' && $this->staffFilter === '') {
            return $this->weekAppointments;
        }

        return $this->weekAppointments
            ->map(fn (Collection $day) => $day->filter(fn (Appointment $appointment) => ($this->statusFilter === '' || $appointment->status->value === $this->statusFilter)
                && ($this->staffFilter === '' || (string) $appointment->staff_member_id === $this->staffFilter))->values())
            ->filter(fn (Collection $day) => $day->isNotEmpty());
    }

    /**
     * Lane of each visible appointment in its day column, so overlapping
     * appointments (of different people) sit side by side.
     *
     * @return array<int, array{lane: int, lanes: int}>
     */
    #[Computed]
    public function calendarLanes(): array
    {
        return $this->visibleAppointments
            ->map(fn (Collection $day) => CalendarLayout::lanes($day))
            ->reduce(fn (array $lanes, array $day) => $lanes + $day, []);
    }

    /**
     * Everyone on the team, for the filter and the appointment details.
     *
     * @return Collection<int, StaffMember>
     */
    #[Computed]
    public function staffMembers(): Collection
    {
        return $this->business->staffMembers()->orderByDesc('is_active')->orderBy('name')->get();
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
            ? $this->business->appointments()->with('service', 'business', 'staffMember')->find($this->openAppointmentId)
            : null;
    }

    /**
     * Free times the open appointment could move to, keyed by date.
     *
     * @return array<string, list<CarbonImmutable>>
     */
    #[Computed]
    public function rescheduleAvailability(): array
    {
        $appointment = $this->openAppointment;

        return $appointment ? app(AvailabilityService::class)->slotsToReschedule($appointment, forBusiness: true) : [];
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
        unset($this->selectedDate, $this->weekDays, $this->weekAppointments, $this->visibleAppointments, $this->dayAppointments, $this->calendarLanes, $this->gridHours, $this->stats, $this->openAppointment);
    }

    /**
     * Ending for a toast, depending on whether the customer gets an email.
     */
    private function customerNotice(Appointment $appointment): string
    {
        return $appointment->customer_email ? ' Le avisamos al cliente por correo.' : '';
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
        unset($this->weekAppointments, $this->visibleAppointments, $this->dayAppointments, $this->calendarLanes, $this->stats, $this->openAppointment);

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
