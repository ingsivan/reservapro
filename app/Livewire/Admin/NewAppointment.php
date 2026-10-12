<?php

namespace App\Livewire\Admin;

use App\Exceptions\SlotUnavailableException;
use App\Livewire\Concerns\ManagesBusiness;
use App\Livewire\Concerns\ShowsNotices;
use App\Models\Service;
use App\Models\StaffMember;
use App\Services\AppointmentBooker;
use App\Services\AvailabilityService;
use App\Support\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Side panel in the agenda to book on behalf of a customer: a phone call or
 * someone who walks in. The appointment is confirmed right away and the
 * email is optional.
 */
class NewAppointment extends Component
{
    use ManagesBusiness, ShowsNotices;

    public bool $open = false;

    #[Validate('required|integer', message: 'Elige un servicio.')]
    public ?int $serviceId = null;

    /** Who attends (null: whoever is free). */
    public ?int $staffMemberId = null;

    public ?string $date = null;

    /** Selected start time as an ISO 8601 string with the business's offset. */
    #[Validate('required|string', message: 'Elige un horario.')]
    public ?string $startsAt = null;

    #[Validate('required|string|max:80')]
    public string $customerName = '';

    #[Validate('nullable|string|max:40')]
    public string $customerPhone = '';

    /** Also send the confirmation, reminder and changes by WhatsApp. */
    public bool $whatsappReminders = false;

    #[Validate('nullable|email|max:255')]
    public string $customerEmail = '';

    #[Validate('nullable|string|max:500')]
    public string $notes = '';

    /**
     * Open the panel, on the given day when it has free times.
     */
    #[On('new-appointment')]
    public function start(?string $date = null): void
    {
        $this->reset('serviceId', 'staffMemberId', 'date', 'startsAt', 'customerName', 'customerPhone', 'whatsappReminders', 'customerEmail', 'notes');
        $this->resetValidation();
        $this->open = true;
        $this->selectService($this->services->first()?->id ?? 0, $date);
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function selectService(int $serviceId, ?string $preferredDate = null): void
    {
        $this->serviceId = $this->services->firstWhere('id', $serviceId)?->id;
        $this->startsAt = null;
        unset($this->selectedService, $this->staffOptions);
        $this->staffMemberId = $this->staffOptions->firstWhere('id', $this->staffMemberId)?->id;
        unset($this->selectedStaffMember, $this->availability);

        $preferredDate ??= $this->date;
        $this->date = ! empty($this->availability[$preferredDate] ?? [])
            ? $preferredDate
            : collect($this->availability)->filter()->keys()->first();
    }

    public function selectStaff(?int $staffMemberId): void
    {
        $this->staffMemberId = $this->staffOptions->firstWhere('id', $staffMemberId)?->id;
        $this->startsAt = null;
        unset($this->selectedStaffMember, $this->availability);

        if (empty($this->availability[$this->date] ?? [])) {
            $this->date = collect($this->availability)->filter()->keys()->first();
        }
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
        $this->resetErrorBag('startsAt');
    }

    public function save(AppointmentBooker $booker): void
    {
        $this->validate();

        if ($this->whatsappReminders && PhoneNumber::toE164($this->customerPhone) === null) {
            $this->addError('customerPhone', 'Escribe el celular a 10 dígitos para enviar los avisos por WhatsApp.');

            return;
        }
        $service = $this->selectedService;

        if ($service === null) {
            $this->addError('serviceId', 'Elige un servicio.');

            return;
        }

        try {
            $appointment = $booker->book($service, CarbonImmutable::parse($this->startsAt), [
                'customer_name' => $this->customerName,
                'customer_email' => $this->customerEmail ?: null,
                'customer_phone' => $this->customerPhone ?: null,
                'whatsapp_opt_in' => $this->whatsappReminders,
                'notes' => $this->notes ?: null,
            ], byBusiness: true, staffMember: $this->selectedStaffMember);
        } catch (SlotUnavailableException $exception) {
            $this->startsAt = null;
            unset($this->availability);
            $this->addError('startsAt', $exception->getMessage());

            return;
        }

        $this->open = false;
        $this->dispatch('appointment-created', date: $appointment->localStartsAt()->toDateString());
        $with = $appointment->staffMember ? " con {$appointment->staffMember->firstName()}" : '';
        $this->notify($appointment->canBeNotified()
            ? "Cita creada{$with}. Le enviamos la confirmación al cliente."
            : "Cita creada{$with}.");
    }

    /**
     * @return Collection<int, Service>
     */
    #[Computed]
    public function services(): Collection
    {
        return $this->business->services()->active()->orderBy('name')->get();
    }

    #[Computed]
    public function selectedService(): ?Service
    {
        return $this->services->firstWhere('id', $this->serviceId)?->setRelation('business', $this->business);
    }

    /**
     * @return Collection<int, StaffMember>
     */
    #[Computed]
    public function staffOptions(): Collection
    {
        if ($this->selectedService === null || ! $this->business->hasTeam()) {
            return new Collection;
        }

        return app(AvailabilityService::class)->staffFor($this->selectedService);
    }

    #[Computed]
    public function selectedStaffMember(): ?StaffMember
    {
        return $this->staffOptions->firstWhere('id', $this->staffMemberId);
    }

    /**
     * Free times for the selected service in the booking window, keyed by date.
     *
     * @return array<string, list<CarbonImmutable>>
     */
    #[Computed]
    public function availability(): array
    {
        if ($this->selectedService === null) {
            return [];
        }

        $today = $this->business->now()->startOfDay();

        return app(AvailabilityService::class)->slotsBetween(
            $this->selectedService,
            $today,
            $today->addDays($this->business->booking_window_days),
            forBusiness: true,
            staffMember: $this->selectedStaffMember,
        );
    }

    #[Computed]
    public function selectedSlot(): ?CarbonImmutable
    {
        return $this->startsAt ? CarbonImmutable::parse($this->startsAt)->setTimezone($this->business->timezone) : null;
    }

    public function render(): View
    {
        return view('livewire.admin.new-appointment');
    }
}
