<?php

namespace App\Livewire;

use App\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Services\AppointmentBooker;
use App\Services\AvailabilityService;
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

    public ?int $serviceId = null;

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
        $this->startsAt = null;
        $this->date = $this->firstAvailableDate();
        $this->resetErrorBag();
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
        $this->resetErrorBag('slot');
    }

    /**
     * Go back to choosing a time without losing the contact details.
     */
    public function changeSlot(): void
    {
        $this->startsAt = null;
    }

    public function book(AppointmentBooker $booker): void
    {
        $this->validate();

        if ($this->selectedService === null || $this->startsAt === null) {
            $this->startsAt = null;

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
            );
        } catch (SlotUnavailableException $exception) {
            $this->startsAt = null;
            unset($this->availability);
            $this->addError('slot', $exception->getMessage());

            return;
        }

        RateLimiter::hit($limiterKey, 3600);

        $this->bookedCode = $appointment->code;
    }

    public function startOver(): void
    {
        $this->reset('serviceId', 'date', 'startsAt', 'notes', 'bookedCode');
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

    #[Computed]
    public function selectedSlot(): ?CarbonImmutable
    {
        return $this->startsAt ? CarbonImmutable::parse($this->startsAt)->setTimezone($this->business->timezone) : null;
    }

    #[Computed]
    public function bookedAppointment(): ?Appointment
    {
        return $this->bookedCode
            ? $this->business->appointments()->with('service', 'business')->where('code', $this->bookedCode)->first()
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
