<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\BookingWizard;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingWizardTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private Service $service;

    private string $slot = '2026-10-13T10:00:00-06:00';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00', 'America/Mexico_City'));

        $this->business = Business::factory()->openDaily('09:00', '13:00')->create(['name' => 'Barbería Test']);
        $this->service = Service::factory()->for($this->business)->minutes(60)->create(['name' => 'Corte']);
    }

    #[Test]
    public function the_public_page_lists_active_services_only(): void
    {
        Service::factory()->for($this->business)->inactive()->create(['name' => 'Servicio oculto']);

        $this->get(route('booking', $this->business))
            ->assertOk()
            ->assertSee('Barbería Test')
            ->assertSee('Corte')
            ->assertDontSee('Servicio oculto');
    }

    #[Test]
    public function an_unknown_business_returns_404(): void
    {
        $this->get('/reservar/no-existe')->assertNotFound();
    }

    #[Test]
    public function a_customer_books_in_three_steps(): void
    {
        Livewire::test(BookingWizard::class, ['business' => $this->business])
            ->call('selectService', $this->service->id)
            ->assertSet('date', '2026-10-12')
            ->call('selectDate', '2026-10-13')
            ->assertSee('10:00')
            ->call('selectSlot', $this->slot)
            ->assertSet('startsAt', $this->slot)
            ->set('customerName', 'Ana López')
            ->set('customerEmail', 'ana@example.com')
            ->set('customerPhone', '555 123 4567')
            ->call('book')
            ->assertHasNoErrors()
            ->assertSee('¡Cita confirmada!');

        $appointment = Appointment::sole();
        $this->assertSame('Ana López', $appointment->customer_name);
        $this->assertSame('2026-10-13 16:00:00', $appointment->getRawOriginal('starts_at'));
        $this->assertNull($appointment->user_id);
    }

    #[Test]
    public function it_requires_contact_details(): void
    {
        Livewire::test(BookingWizard::class, ['business' => $this->business])
            ->call('selectService', $this->service->id)
            ->call('selectDate', '2026-10-13')
            ->call('selectSlot', $this->slot)
            ->set('customerEmail', 'no-es-correo')
            ->call('book')
            ->assertHasErrors(['customerName' => 'required', 'customerEmail' => 'email']);

        $this->assertSame(0, Appointment::count());
    }

    #[Test]
    public function it_ignores_slots_that_are_not_offered(): void
    {
        Livewire::test(BookingWizard::class, ['business' => $this->business])
            ->call('selectService', $this->service->id)
            ->call('selectDate', '2026-10-13')
            ->call('selectSlot', '2026-10-13T03:00:00-06:00')
            ->assertSet('startsAt', null);
    }

    #[Test]
    public function it_ignores_services_of_other_businesses(): void
    {
        $foreign = Service::factory()->create();

        Livewire::test(BookingWizard::class, ['business' => $this->business])
            ->call('selectService', $foreign->id)
            ->assertSet('serviceId', null);
    }

    #[Test]
    public function a_slot_taken_while_the_customer_was_typing_shows_an_error(): void
    {
        $component = Livewire::test(BookingWizard::class, ['business' => $this->business])
            ->call('selectService', $this->service->id)
            ->call('selectDate', '2026-10-13')
            ->call('selectSlot', $this->slot)
            ->set('customerName', 'Ana')
            ->set('customerEmail', 'ana@example.com');

        Appointment::factory()->for($this->service)->for($this->business)
            ->at(CarbonImmutable::parse($this->slot)->utc())
            ->create();

        $component->call('book')
            ->assertHasErrors('slot')
            ->assertSet('startsAt', null)
            ->assertSee('Ese horario ya no está disponible');

        $this->assertSame(1, Appointment::count());
    }

    #[Test]
    public function signed_in_customers_get_their_details_filled_and_the_booking_linked(): void
    {
        $client = User::factory()->create(['role' => Role::Client, 'name' => 'Cliente Registrado']);

        Livewire::actingAs($client)
            ->test(BookingWizard::class, ['business' => $this->business])
            ->assertSet('customerName', 'Cliente Registrado')
            ->assertSet('customerEmail', $client->email)
            ->call('selectService', $this->service->id)
            ->call('selectDate', '2026-10-13')
            ->call('selectSlot', $this->slot)
            ->call('book')
            ->assertHasNoErrors();

        $this->assertSame($client->id, Appointment::sole()->user_id);
    }

    #[Test]
    public function each_visitor_has_a_booking_limit(): void
    {
        $component = Livewire::test(BookingWizard::class, ['business' => $this->business])
            ->set('customerName', 'Ana')
            ->set('customerEmail', 'ana@example.com')
            ->call('selectService', $this->service->id)
            ->call('selectDate', '2026-10-13');

        foreach (['09:00', '10:00', '11:00', '12:00', '09:30'] as $index => $time) {
            $date = $index === 4 ? '2026-10-14' : '2026-10-13';
            $component->call('startOver')
                ->call('selectService', $this->service->id)
                ->call('selectDate', $date)
                ->call('selectSlot', "{$date}T{$time}:00-06:00")
                ->call('book')
                ->assertHasNoErrors();
        }

        $component->call('startOver')
            ->call('selectService', $this->service->id)
            ->call('selectDate', '2026-10-15')
            ->call('selectSlot', '2026-10-15T09:00:00-06:00')
            ->call('book')
            ->assertHasErrors('customerEmail');

        $this->assertSame(BookingWizard::BOOKINGS_PER_HOUR, Appointment::count());
    }
}
