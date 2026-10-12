<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\CancelledBy;
use App\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\User;
use App\Notifications\AppointmentBooked;
use App\Notifications\AppointmentCancelled;
use App\Notifications\AppointmentConfirmed;
use App\Notifications\AppointmentRescheduled;
use App\Notifications\NewAppointmentForBusiness;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Creates, confirms, moves and cancels appointments, and sends the matching emails.
 */
class AppointmentBooker
{
    public function __construct(private AvailabilityService $availability) {}

    /**
     * Book a slot without ever creating two appointments at the same time.
     *
     * The business row is locked inside the transaction, so two customers
     * booking the same business are handled one after the other. The second
     * one sees the first appointment and gets a SlotUnavailableException.
     * On SQLite, the connection uses IMMEDIATE transactions for the same effect.
     *
     * With $byBusiness (a phone booking or a walk-in), the appointment is
     * confirmed right away, the minimum notice doesn't apply, the email is
     * optional and the owner isn't emailed about their own booking.
     *
     * @param  array{customer_name: string, customer_email: ?string, customer_phone?: ?string, notes?: ?string}  $customer
     *
     * @throws SlotUnavailableException
     */
    public function book(Service $service, CarbonImmutable $startsAt, array $customer, ?User $user = null, bool $byBusiness = false): Appointment
    {
        $appointment = DB::transaction(function () use ($service, $startsAt, $customer, $user, $byBusiness) {
            $business = Business::whereKey($service->business_id)->lockForUpdate()->firstOrFail();
            $service->setRelation('business', $business);

            if (! $service->is_active || ! $this->availability->isAvailable($service, $startsAt, forBusiness: $byBusiness)) {
                throw new SlotUnavailableException;
            }

            $status = $business->requires_confirmation && ! $byBusiness ? AppointmentStatus::Pending : AppointmentStatus::Confirmed;

            return $business->appointments()->create([
                'service_id' => $service->id,
                'user_id' => $user?->id,
                'customer_name' => $customer['customer_name'],
                'customer_email' => $customer['customer_email'],
                'customer_phone' => $customer['customer_phone'] ?? null,
                'notes' => $customer['notes'] ?? null,
                'starts_at' => $startsAt->utc(),
                'ends_at' => $startsAt->addMinutes($service->duration_minutes)->utc(),
                'status' => $status,
                'price_cents' => $service->price_cents,
                'confirmed_at' => $status === AppointmentStatus::Confirmed ? now() : null,
            ]);
        });

        $appointment->load('business.owner', 'service');

        $appointment->notifyCustomer(new AppointmentBooked($appointment));

        if (! $byBusiness) {
            $appointment->business->owner->notify(new NewAppointmentForBusiness($appointment));
        }

        return $appointment;
    }

    public function confirm(Appointment $appointment): void
    {
        if ($appointment->status !== AppointmentStatus::Pending) {
            return;
        }

        $appointment->update([
            'status' => AppointmentStatus::Confirmed,
            'confirmed_at' => now(),
        ]);

        $appointment->notifyCustomer(new AppointmentConfirmed($appointment));
    }

    /**
     * Move an active appointment to another free time and tell the other side.
     *
     * Uses the same lock as book(), and the appointment's own time counts as
     * free, so it can also move a few minutes within its current slot. The
     * status stays the same, and the reminder is sent again for the new time.
     *
     * @throws SlotUnavailableException
     */
    public function reschedule(Appointment $appointment, CarbonImmutable $startsAt, CancelledBy $changedBy): Appointment
    {
        $previousWhen = $appointment->whenLabel();

        DB::transaction(function () use ($appointment, $startsAt, $changedBy) {
            $business = Business::whereKey($appointment->business_id)->lockForUpdate()->firstOrFail();
            $appointment->refresh()->setRelation('business', $business);
            $service = $appointment->service->setRelation('business', $business);

            if (! $appointment->status->isActive() || ! $this->availability->isAvailable($service, $startsAt, $appointment, $changedBy === CancelledBy::Business)) {
                throw new SlotUnavailableException;
            }

            $appointment->update([
                'starts_at' => $startsAt->utc(),
                'ends_at' => $startsAt->addMinutes($service->duration_minutes)->utc(),
                'reminder_sent_at' => null,
            ]);
        });

        $appointment->load('business.owner', 'service');
        $notification = new AppointmentRescheduled($appointment, $previousWhen);

        if ($changedBy === CancelledBy::Business) {
            $appointment->notifyCustomer($notification);
        } else {
            $appointment->business->owner->notify($notification);
        }

        return $appointment;
    }

    /**
     * Cancel an active appointment and tell the other side.
     */
    public function cancel(Appointment $appointment, CancelledBy $cancelledBy): void
    {
        if (! $appointment->status->isActive()) {
            return;
        }

        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledBy,
        ]);

        if ($cancelledBy === CancelledBy::Business) {
            $appointment->notifyCustomer(new AppointmentCancelled($appointment));
        } else {
            $appointment->business->owner->notify(new AppointmentCancelled($appointment));
        }
    }
}
