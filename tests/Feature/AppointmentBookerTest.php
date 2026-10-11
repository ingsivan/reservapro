<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\CancelledBy;
use App\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Notifications\AppointmentBooked;
use App\Notifications\AppointmentCancelled;
use App\Notifications\AppointmentConfirmed;
use App\Notifications\NewAppointmentForBusiness;
use App\Services\AppointmentBooker;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppointmentBookerTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    private CarbonImmutable $tomorrowAtTen;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00', 'America/Mexico_City'));

        $business = Business::factory()->openDaily('09:00', '13:00')->create();
        $this->service = Service::factory()->for($business)->minutes(60)->create(['price_cents' => 25000]);
        $this->tomorrowAtTen = CarbonImmutable::parse('2026-10-13 10:00', 'America/Mexico_City');
    }

    #[Test]
    public function it_books_a_free_slot_and_emails_both_sides(): void
    {
        $appointment = $this->book();

        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertSame('2026-10-13 16:00:00', $appointment->getRawOriginal('starts_at'));
        $this->assertSame('2026-10-13 17:00:00', $appointment->getRawOriginal('ends_at'));
        $this->assertSame(25000, $appointment->price_cents);
        $this->assertMatchesRegularExpression('/^[A-Z2-9XYZWV]{8}$/', $appointment->code);

        Notification::assertSentOnDemand(AppointmentBooked::class, fn ($notification, $channels, AnonymousNotifiable $notifiable) => array_key_exists('ana@example.com', $notifiable->routes['mail']));
        Notification::assertSentTo($this->service->business->owner, NewAppointmentForBusiness::class);
    }

    #[Test]
    public function the_same_slot_cannot_be_booked_twice(): void
    {
        $this->book();

        $this->expectException(SlotUnavailableException::class);

        $this->book('otra@example.com');
    }

    #[Test]
    public function an_overlapping_slot_cannot_be_booked(): void
    {
        $this->book();

        try {
            $this->book('otra@example.com', $this->tomorrowAtTen->addMinutes(30));
            $this->fail('An overlapping booking was accepted.');
        } catch (SlotUnavailableException) {
            $this->assertSame(1, Appointment::count());
        }
    }

    #[Test]
    public function a_slot_freed_by_a_cancellation_can_be_booked_again(): void
    {
        app(AppointmentBooker::class)->cancel($this->book(), CancelledBy::Customer);

        $this->assertSame(AppointmentStatus::Confirmed, $this->book('otra@example.com')->status);
    }

    #[Test]
    public function inactive_services_cannot_be_booked(): void
    {
        $this->service->update(['is_active' => false]);

        $this->expectException(SlotUnavailableException::class);

        $this->book();
    }

    #[Test]
    public function times_outside_opening_hours_cannot_be_booked(): void
    {
        $this->expectException(SlotUnavailableException::class);

        $this->book(startsAt: CarbonImmutable::parse('2026-10-13 20:00', 'America/Mexico_City'));
    }

    #[Test]
    public function bookings_wait_for_approval_when_the_business_requires_it(): void
    {
        $this->service->business->update(['requires_confirmation' => true]);

        $appointment = $this->book();

        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
        $this->assertNull($appointment->confirmed_at);
    }

    #[Test]
    public function confirming_a_pending_appointment_emails_the_customer(): void
    {
        $this->service->business->update(['requires_confirmation' => true]);
        $appointment = $this->book();

        app(AppointmentBooker::class)->confirm($appointment);

        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);
        Notification::assertSentOnDemand(AppointmentConfirmed::class);
    }

    #[Test]
    public function a_cancellation_by_the_business_emails_the_customer(): void
    {
        $appointment = $this->book();

        app(AppointmentBooker::class)->cancel($appointment, CancelledBy::Business);

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->status);
        $this->assertSame(CancelledBy::Business, $appointment->cancelled_by);
        Notification::assertSentOnDemand(AppointmentCancelled::class);
        Notification::assertNotSentTo($this->service->business->owner, AppointmentCancelled::class);
    }

    #[Test]
    public function a_cancellation_by_the_customer_emails_the_business(): void
    {
        app(AppointmentBooker::class)->cancel($this->book(), CancelledBy::Customer);

        Notification::assertSentTo($this->service->business->owner, AppointmentCancelled::class);
    }

    #[Test]
    public function the_confirmation_email_shows_local_time_and_a_signed_cancel_link(): void
    {
        $appointment = $this->book();

        $mail = (new AppointmentBooked($appointment))->toMail(new AnonymousNotifiable);

        $this->assertStringContainsString('martes 13 de octubre de 2026, 10:00 a 11:00', implode("\n", $mail->introLines));
        $this->assertStringContainsString('signature=', $mail->actionUrl);
    }

    private function book(string $email = 'ana@example.com', ?CarbonImmutable $startsAt = null): Appointment
    {
        return app(AppointmentBooker::class)->book($this->service->fresh(), $startsAt ?? $this->tomorrowAtTen, [
            'customer_name' => 'Ana',
            'customer_email' => $email,
        ]);
    }
}
