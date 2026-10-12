<?php

namespace App\Livewire;

use App\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\StaffMember;
use App\Services\AppointmentBooker;
use App\Services\AvailabilityService;
use App\Support\TimeSlots;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Public booking page: service → day and time → contact details → done.
 */
class BookingWizard extends Component
{
    /** Bookings allowed per visitor (IP) per hour. */
    public const BOOKINGS_PER_HOUR = 5;

    #[Locked]
    public Business $business;

    /** Visible step: 1 service, 2 day and time, 3 contact details. */
    public int $step = 1;

    public ?int $serviceId = null;

    /** Who the customer wants to book with (null: whoever is free). */
    public ?int $staffMemberId = null;

    public ?string $date = null;

    /** Selected start time as an ISO 8601 string with the business's offset. */
    public ?string $startsAt = null;

    #[Validate('required|string|max:80')]
    public string $customerName = '';

    #[Validate('required|email|max:255')]
    public string $customerEmail = '';

    #[Validate('nullable|string|max:40')]
    public string $customerPhone = '';

    #[Validate('nullable|string|max:500')]
    public string $notes = '';

    #[Locked]
    public ?string $bookedCode = null;

    public function mount(Business $business): void
    {
        $this->business = $business;

        if ($user = auth()->user()) {
            $this->customerName = $user->name;
            $this->customerEmail = $user->email;
        }
    }

    public function selectService(int $serviceId): void
    {
        $this->serviceId = $this->services->firstWhere('id', $serviceId)?->id;
        unset($this->selectedService, $this->staffOptions);
        $this->staffMemberId = $this->staffOptions->firstWhere('id', $this->staffMemberId)?->id;
        $this->startsAt = null;
        $this->date = $this->firstAvailableDate();
        $this->step = $this->serviceId ? 2 : 1;
        $this->resetErrorBag();
    }

    /**
     * Book with a specific person, or with whoever is free (null), keeping
     * the selected day when it still has free times.
     */
    public function selectStaff(?int $staffMemberId): void
    {
        $this->staffMemberId = $this->staffOptions->firstWhere('id', $staffMemberId)?->id;
        $this->startsAt = null;
        unset($this->selectedStaffMember, $this->availability);

        if (empty($this->availability[$this->date] ?? [])) {
            $this->date = $this->firstAvailableDate();
        }

        $this->resetErrorBag('slot');
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
        $this->startsAt = collect($this->timeSlots)->contains(fn (CarbonImmutable $start) => $start->toIso8601String() === $slot)
            ? $slot
            : null;
        $this->step = $this->startsAt ? 3 : 2;
        $this->resetErrorBag('slot');
    }

    /**
     * Jump back (or forward) to a step whose previous choices are already made.
     */
    public function goToStep(int $step): void
    {
        $this->step = match (true) {
            $step === 3 && $this->selectedSlot !== null => 3,
            $step >= 2 && $this->selectedService !== null => 2,
            default => 1,
        };
    }

    /**
     * Go back to choosing a time without losing the contact details.
     */
    public function changeSlot(): void
    {
        $this->startsAt = null;
        $this->step = 2;
    }

    public function book(AppointmentBooker $booker): void
    {
        $this->validate();

        if ($this->selectedService === null || $this->startsAt === null) {
            $this->startsAt = null;
            $this->step = $this->selectedService ? 2 : 1;

            return;
        }

        $limiterKey = 'booking:'.request()->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, self::BOOKINGS_PER_HOUR)) {
            $this->addError('customerEmail', 'Hiciste demasiadas reservas seguidas. Inténtalo más tarde.');

            return;
        }

        try {
            $appointment = $booker->book(
                $this->selectedService,
                CarbonImmutable::parse($this->startsAt),
                [
                    'customer_name' => $this->customerName,
                    'customer_email' => $this->customerEmail,
                    'customer_phone' => $this->customerPhone ?: null,
                    'notes' => $this->notes ?: null,
                ],
                auth()->user(),
                staffMember: $this->selectedStaffMember,
            );
        } catch (SlotUnavailableException $exception) {
            $this->startsAt = null;
            $this->step = 2;
            unset($this->availability);
            $this->addError('slot', $exception->getMessage());

            return;
        }

        RateLimiter::hit($limiterKey, 3600);

        $this->bookedCode = $appointment->code;
    }

    public function startOver(): void
    {
        $this->reset('step', 'serviceId', 'staffMemberId', 'date', 'startsAt', 'notes', 'bookedCode');
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
     * People the customer can choose for the selected service. Empty when
     * the business works without a team.
     *
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
     * Free slots for every day in the booking window, keyed by date.
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
            staffMember: $this->selectedStaffMember,
        );
    }

    /**
     * @return list<CarbonImmutable>
     */
    #[Computed]
    public function timeSlots(): array
    {
        return $this->availability[$this->date] ?? [];
    }

    /**
     * Free times of the selected day split into morning, afternoon and evening.
     *
     * @return list<array{label: string, icon: string, slots: list<CarbonImmutable>}>
     */
    #[Computed]
    public function slotGroups(): array
    {
        return TimeSlots::groupByPartOfDay($this->timeSlots);
    }

    #[Computed]
    public function selectedSlot(): ?CarbonImmutable
    {
        return $this->startsAt ? CarbonImmutable::parse($this->startsAt)->setTimezone($this->business->timezone) : null;
    }

    #[Computed]
    public function bookedAppointment(): ?Appointment
    {
        return $this->bookedCode
            ? $this->business->appointments()->with('service', 'business', 'staffMember')->where('code', $this->bookedCode)->first()
            : null;
    }

    public function render(): View
    {
        return view('livewire.booking-wizard')->title($this->business->name);
    }

    private function firstAvailableDate(): ?string
    {
        unset($this->availability);

        return collect($this->availability)->filter()->keys()->first();
    }
}
