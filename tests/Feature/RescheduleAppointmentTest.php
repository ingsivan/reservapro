<?php

namespace Tests\Feature;

use App\Livewire\RescheduleAppointment;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Notifications\AppointmentRescheduled;
use App\Services\AppointmentBooker;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RescheduleAppointmentTest extends TestCase
{
    use RefreshDatabase;

    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00', 'America/Mexico_City'));

        $business = Business::factory()->openDaily('09:00', '13:00')->create(['timezone' => 'America/Mexico_City', 'cancellation_notice_hours' => 2]);
        $service = Service::factory()->for($business)->minutes(60)->create();
        $this->appointment = app(AppointmentBooker::class)->book($service, CarbonImmutable::parse('2026-10-13 10:00', 'America/Mexico_City'), [
            'customer_name' => 'Ana',
            'customer_email' => 'ana@example.com',
        ]);
    }

    #[Test]
    public function the_appointment_page_links_to_the_signed_reschedule_page(): void
    {
        $this->get($this->appointment->manageUrl())->assertOk()->assertSee($this->appointment->rescheduleUrl(), escape: false);

        $this->get($this->appointment->rescheduleUrl())
            ->assertOk()
            ->assertSee('Cambiar horario')
            ->assertSee('martes 13 de octubre de 2026, 10:00 a 11:00');

        $this->get(route('appointments.reschedule', $this->appointment))->assertForbidden();
    }

    #[Test]
    public function the_customer_moves_the_appointment_and_the_business_is_told(): void
    {
        Livewire::test(RescheduleAppointment::class, ['appointment' => $this->appointment])
            ->assertSet('date', '2026-10-13')
            ->call('selectDate', '2026-10-14')
            ->call('selectSlot', '2026-10-14T12:00:00-06:00')
            ->assertSee('miércoles 14 de octubre, 12:00')
            ->call('save')
            ->assertRedirect($this->appointment->manageUrl());

        $this->assertSame('2026-10-14 18:00:00', $this->appointment->fresh()->getRawOriginal('starts_at'));
        Notification::assertSentTo($this->appointment->business->owner, AppointmentRescheduled::class);
    }

    #[Test]
    public function the_current_time_is_not_offered(): void
    {
        Livewire::test(RescheduleAppointment::class, ['appointment' => $this->appointment])
            ->call('selectSlot', '2026-10-13T10:00:00-06:00')
            ->assertSet('startsAt', null)
            ->call('selectSlot', '2026-10-13T10:30:00-06:00')
            ->assertSet('startsAt', '2026-10-13T10:30:00-06:00');
    }

    #[Test]
    public function a_taken_time_sends_the_customer_back_to_choose(): void
    {
        $component = Livewire::test(RescheduleAppointment::class, ['appointment' => $this->appointment])
            ->call('selectSlot', '2026-10-13T12:00:00-06:00');

        app(AppointmentBooker::class)->book($this->appointment->service, CarbonImmutable::parse('2026-10-13 12:00', 'America/Mexico_City'), [
            'customer_name' => 'Luis',
            'customer_email' => 'luis@example.com',
        ]);

        $component->call('save')->assertHasErrors('slot')->assertSet('startsAt', null)->assertNoRedirect();
        $this->assertSame('2026-10-13 16:00:00', $this->appointment->fresh()->getRawOriginal('starts_at'));
    }

    #[Test]
    public function the_appointment_cannot_be_moved_after_the_notice_period(): void
    {
        $component = Livewire::test(RescheduleAppointment::class, ['appointment' => $this->appointment])
            ->call('selectSlot', '2026-10-13T12:00:00-06:00');

        $this->travelTo(CarbonImmutable::parse('2026-10-13 08:30', 'America/Mexico_City'));

        $component->call('save')->assertHasErrors('slot')->assertNoRedirect();
        $this->assertSame('2026-10-13 16:00:00', $this->appointment->fresh()->getRawOriginal('starts_at'));
        $this->get($this->appointment->manageUrl())->assertDontSee('Cambiar horario');
    }
}
