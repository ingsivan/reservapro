<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Livewire\Admin\Agenda;
use App\Livewire\Admin\NewAppointment;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Notifications\AppointmentBooked;
use App\Notifications\NewAppointmentForBusiness;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewAppointmentTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00', 'America/Mexico_City'));

        $this->business = Business::factory()->openDaily('09:00', '13:00')->requiringConfirmation()->create([
            'timezone' => 'America/Mexico_City',
            'min_notice_minutes' => 180,
        ]);
        $this->service = Service::factory()->for($this->business)->minutes(60)->create(['name' => 'Corte']);
    }

    #[Test]
    public function the_owner_books_a_phone_call_without_an_email(): void
    {
        Livewire::actingAs($this->business->owner)
            ->test(NewAppointment::class)
            ->call('start', '2026-10-13')
            ->assertSet('open', true)
            ->assertSet('serviceId', $this->service->id)
            ->assertSet('date', '2026-10-13')
            ->call('selectSlot', '2026-10-13T11:00:00-06:00')
            ->set('customerName', 'Marta Ruiz')
            ->set('customerPhone', '55 1234 5678')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('open', false)
            ->assertDispatched('appointment-created', date: '2026-10-13')
            ->assertDispatched('toast', message: 'Cita creada.');

        $appointment = Appointment::sole();
        $this->assertSame('Marta Ruiz', $appointment->customer_name);
        $this->assertNull($appointment->customer_email);
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertSame('2026-10-13 17:00:00', $appointment->getRawOriginal('starts_at'));
        Notification::assertNothingSent();
    }

    #[Test]
    public function the_customer_gets_the_confirmation_when_there_is_an_email(): void
    {
        Livewire::actingAs($this->business->owner)
            ->test(NewAppointment::class)
            ->call('start', '2026-10-13')
            ->call('selectSlot', '2026-10-13T09:00:00-06:00')
            ->set('customerName', 'Ana')
            ->set('customerEmail', 'ana@example.com')
            ->call('save')
            ->assertDispatched('toast', message: 'Cita creada. Le enviamos la confirmación al cliente.');

        Notification::assertSentOnDemand(AppointmentBooked::class);
        Notification::assertNotSentTo($this->business->owner, NewAppointmentForBusiness::class);
    }

    #[Test]
    public function a_walk_in_can_be_booked_inside_the_minimum_notice(): void
    {
        Livewire::actingAs($this->business->owner)
            ->test(NewAppointment::class)
            ->call('start', '2026-10-12')
            ->assertSet('date', '2026-10-12')
            ->call('selectSlot', '2026-10-12T09:00:00-06:00')
            ->assertSet('startsAt', '2026-10-12T09:00:00-06:00')
            ->set('customerName', 'Luis')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Appointment::count());
    }

    #[Test]
    public function the_name_and_a_time_are_required(): void
    {
        Livewire::actingAs($this->business->owner)
            ->test(NewAppointment::class)
            ->call('start')
            ->set('customerEmail', 'no-es-correo')
            ->call('save')
            ->assertHasErrors(['customerName', 'startsAt', 'customerEmail']);

        $this->assertSame(0, Appointment::count());
    }

    #[Test]
    public function a_time_taken_meanwhile_is_rejected(): void
    {
        $component = Livewire::actingAs($this->business->owner)
            ->test(NewAppointment::class)
            ->call('start', '2026-10-13')
            ->call('selectSlot', '2026-10-13T10:00:00-06:00')
            ->set('customerName', 'Luis');

        Appointment::factory()->for($this->service)->for($this->business)
            ->at(CarbonImmutable::parse('2026-10-13 10:00', 'America/Mexico_City')->utc())->create();

        $component->call('save')->assertHasErrors('startsAt')->assertSet('open', true)->assertSet('startsAt', null);
        $this->assertSame(1, Appointment::count());
    }

    #[Test]
    public function services_of_other_businesses_cannot_be_chosen(): void
    {
        $foreign = Service::factory()->create();

        Livewire::actingAs($this->business->owner)
            ->test(NewAppointment::class)
            ->call('start')
            ->call('selectService', $foreign->id)
            ->assertSet('serviceId', null);
    }

    #[Test]
    public function the_agenda_jumps_to_the_day_of_the_new_appointment(): void
    {
        Livewire::actingAs($this->business->owner)
            ->test(Agenda::class)
            ->assertSee('Nueva cita')
            ->dispatch('appointment-created', date: '2026-10-20')
            ->assertSet('date', '2026-10-20');
    }
}
