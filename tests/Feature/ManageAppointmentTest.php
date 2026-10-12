<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Notifications\AppointmentCancelled;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ManageAppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    #[Test]
    public function the_signed_link_shows_the_appointment(): void
    {
        $appointment = Appointment::factory()->create(['customer_name' => 'Ana López']);

        $this->get($appointment->manageUrl())
            ->assertOk()
            ->assertSee('Ana López')
            ->assertSee($appointment->code)
            ->assertSee('Cancelar mi cita');
    }

    #[Test]
    public function the_appointment_can_be_added_to_a_calendar(): void
    {
        $appointment = Appointment::factory()
            ->at(CarbonImmutable::parse('2026-10-13 16:00:00', 'UTC'))
            ->create();

        $response = $this->get($appointment->calendarFileUrl())->assertOk();

        $this->assertStringStartsWith('text/calendar', $response->headers->get('Content-Type'));
        $this->assertStringContainsString("cita-{$appointment->code}.ics", $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString("DTSTART:20261013T160000Z\r\n", $response->getContent());
        $this->assertStringContainsString("UID:{$appointment->code}@reservapro", $response->getContent());

        $this->assertStringContainsString('dates=20261013T160000Z', $appointment->googleCalendarUrl());
        $this->get(route('appointments.calendar', $appointment))->assertForbidden();
    }

    #[Test]
    public function the_link_does_not_work_without_a_valid_signature(): void
    {
        $appointment = Appointment::factory()->create();

        $this->get(route('appointments.manage', $appointment))->assertForbidden();
        $this->get($appointment->manageUrl().'x')->assertForbidden();
        $this->post(route('appointments.cancel', $appointment))->assertForbidden();
    }

    #[Test]
    public function the_customer_can_cancel_and_the_business_is_told(): void
    {
        $appointment = Appointment::factory()->create();

        $this->post(URL::signedRoute('appointments.cancel', ['appointment' => $appointment->code]))
            ->assertRedirect($appointment->manageUrl())
            ->assertSessionHas('status');

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
        Notification::assertSentTo($appointment->business->owner, AppointmentCancelled::class);
    }

    #[Test]
    public function the_customer_cannot_cancel_inside_the_notice_period(): void
    {
        $appointment = Appointment::factory()
            ->at(CarbonImmutable::now()->addHour())
            ->create();

        $this->get($appointment->manageUrl())->assertDontSee('Cancelar mi cita');

        $this->post(URL::signedRoute('appointments.cancel', ['appointment' => $appointment->code]))
            ->assertSessionHas('error');

        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);
        Notification::assertNothingSent();
    }
}
