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
