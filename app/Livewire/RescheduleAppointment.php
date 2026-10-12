<?php

namespace App\Livewire;

use App\Enums\CancelledBy;
use App\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Services\AppointmentBooker;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Lets a customer move their appointment to another free time from the
 * signed link in their email.
 *
 * The appointment is locked, so later Livewire requests can't swap it for
 * another one even though they don't carry the URL signature.
 */
class RescheduleAppointment extends Component
{
    #[Locked]
    public Appointment $appointment;

    public ?string $date = null;

    /** Selected start time as an ISO 8601 string with the business's offset. */
    public ?string $startsAt = null;

    public function mount(Appointment $appointment): void
    {
        $this->appointment = $appointment->load('business', 'service');

        $currentDate = $appointment->localStartsAt()->toDateString();
        $this->date = ! empty($this->availability[$currentDate])
            ? $currentDate
            : collect($this->availability)->filter()->keys()->first();
    }

    public function selectDate(string $date): void
    {
        if (array_key_exists($date, $this->availability)) {
            $this->date = $date;
            $this->startsAt = null;
        }
    }

    public function selectSlot(string $slot): void
    {
        $this->startsAt = collect($this->availability[$this->date] ?? [])
            ->contains(fn (CarbonImmutable $start) => $start->toIso8601String() === $slot) ? $slot : null;
        $this->resetErrorBag('slot');
    }

    public function save(AppointmentBooker $booker): void
    {
        if ($this->startsAt === null) {
            return;
        }

        if (! $this->appointment->canBeRescheduledByCustomer()) {
            $this->addError('slot', 'Esta cita ya no se puede cambiar en línea. Comunícate directamente con el negocio.');

            return;
        }

        try {
            $booker->reschedule($this->appointment, CarbonImmutable::parse($this->startsAt), CancelledBy::Customer);
        } catch (SlotUnavailableException $exception) {
            $this->startsAt = null;
            unset($this->availability);
            $this->addError('slot', $exception->getMessage());

            return;
        }

        session()->flash('status', 'Listo, cambiamos tu cita. El negocio ya fue avisado.');

        $this->redirect($this->appointment->manageUrl());
    }

    /**
     * Free times in the booking window, without the appointment's current time.
     *
     * @return array<string, list<CarbonImmutable>>
     */
    #[Computed]
    public function availability(): array
    {
        return app(AvailabilityService::class)->slotsToReschedule($this->appointment);
    }

    #[Computed]
    public function selectedSlot(): ?CarbonImmutable
    {
        return $this->startsAt ? CarbonImmutable::parse($this->startsAt)->setTimezone($this->appointment->business->timezone) : null;
    }

    public function render(): View
    {
        return view('livewire.reschedule-appointment')->title('Cambiar horario');
    }
}
