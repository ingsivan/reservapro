<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Livewire\Admin\Agenda;
use App\Livewire\Admin\BusinessSettings;
use App\Livewire\Admin\ScheduleManager;
use App\Livewire\Admin\Services;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\User;
use App\Notifications\AppointmentCancelled;
use App\Notifications\AppointmentConfirmed;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00', 'America/Mexico_City'));

        $this->business = Business::factory()->openDaily()->create();
        $this->owner = $this->business->owner;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function panelRoutes(): array
    {
        return [
            'agenda' => ['admin.agenda'],
            'servicios' => ['admin.services'],
            'horario' => ['admin.schedule'],
            'ajustes' => ['admin.settings'],
        ];
    }

    #[Test]
    #[DataProvider('panelRoutes')]
    public function owners_can_open_every_panel_page(string $route): void
    {
        $this->actingAs($this->owner)->get(route($route))->assertOk()->assertSee($this->business->name);
    }

    #[Test]
    #[DataProvider('panelRoutes')]
    public function customers_and_guests_cannot_open_the_panel(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => Role::Client]))->get(route($route))->assertForbidden();
    }

    #[Test]
    public function the_agenda_shows_the_week_and_the_selected_day(): void
    {
        $service = Service::factory()->for($this->business)->create(['name' => 'Masaje relajante']);
        Appointment::factory()->for($service)->for($this->business)
            ->at(CarbonImmutable::parse('2026-10-13 10:00', 'America/Mexico_City')->utc())
            ->create(['customer_name' => 'Ana López', 'price_cents' => 50000]);

        Livewire::actingAs($this->owner)
            ->test(Agenda::class)
            ->assertSet('date', '2026-10-12')
            ->assertSee('Ana López')
            ->assertSee('$500.00')
            ->call('selectDate', '2026-10-13')
            ->assertSee('Masaje relajante')
            ->call('nextWeek')
            ->assertSet('date', '2026-10-20')
            ->assertDontSee('Ana López');
    }

    #[Test]
    public function the_owner_opens_an_appointment_and_filters_by_status(): void
    {
        $pending = Appointment::factory()->pending()->for($this->serviceWithBusiness())->for($this->business)
            ->at(CarbonImmutable::parse('2026-10-12 10:00', 'America/Mexico_City')->utc())
            ->create(['customer_name' => 'Ana López', 'notes' => 'Llego cinco minutos tarde']);
        Appointment::factory()->for($this->serviceWithBusiness())->for($this->business)
            ->at(CarbonImmutable::parse('2026-10-12 12:00', 'America/Mexico_City')->utc())
            ->create(['customer_name' => 'Bruno Díaz']);

        Livewire::actingAs($this->owner)
            ->test(Agenda::class)
            ->call('viewAppointment', $pending->id)
            ->assertSee('Llego cinco minutos tarde')
            ->assertSee($pending->code)
            ->call('closeAppointment')
            ->assertSet('openAppointmentId', null)
            ->call('filterByStatus', 'pending')
            ->assertSee('Ana López')
            ->assertDontSee('Bruno Díaz')
            ->call('filterByStatus', 'no-existe')
            ->assertSet('statusFilter', '')
            ->assertSee('Bruno Díaz');
    }

    #[Test]
    public function the_owner_cannot_open_appointments_of_other_businesses(): void
    {
        $foreign = Appointment::factory()->create();

        Livewire::actingAs($this->owner)->test(Agenda::class)->call('viewAppointment', $foreign->id)->assertNotFound();
    }

    #[Test]
    public function the_owner_confirms_and_cancels_appointments(): void
    {
        $pending = Appointment::factory()->pending()->for($this->serviceWithBusiness())->for($this->business)->create();
        $confirmed = Appointment::factory()->for($this->serviceWithBusiness())->for($this->business)
            ->at(now()->addDays(3))->create();

        Livewire::actingAs($this->owner)
            ->test(Agenda::class)
            ->call('confirm', $pending->id)
            ->call('cancel', $confirmed->id);

        $this->assertSame(AppointmentStatus::Confirmed, $pending->fresh()->status);
        $this->assertSame(AppointmentStatus::Cancelled, $confirmed->fresh()->status);
        Notification::assertSentOnDemand(AppointmentConfirmed::class);
        Notification::assertSentOnDemand(AppointmentCancelled::class);
    }

    #[Test]
    public function past_appointments_can_be_marked_as_attended_or_missed(): void
    {
        $service = $this->serviceWithBusiness();
        $attended = Appointment::factory()->for($service)->for($this->business)->at(now()->subHours(3))->create();
        $missed = Appointment::factory()->for($service)->for($this->business)->at(now()->subHours(5))->create();
        $future = Appointment::factory()->for($service)->for($this->business)->at(now()->addDay())->create();

        Livewire::actingAs($this->owner)
            ->test(Agenda::class)
            ->call('markCompleted', $attended->id)
            ->call('markNoShow', $missed->id)
            ->call('markCompleted', $future->id);

        $this->assertSame(AppointmentStatus::Completed, $attended->fresh()->status);
        $this->assertSame(AppointmentStatus::NoShow, $missed->fresh()->status);
        $this->assertSame(AppointmentStatus::Confirmed, $future->fresh()->status);
    }

    #[Test]
    public function owners_cannot_touch_another_business_appointments(): void
    {
        $foreign = Appointment::factory()->create();

        Livewire::actingAs($this->owner)->test(Agenda::class)->call('cancel', $foreign->id)->assertNotFound();

        $this->assertSame(AppointmentStatus::Confirmed, $foreign->fresh()->status);
    }

    #[Test]
    public function the_owner_creates_and_edits_services(): void
    {
        $component = Livewire::actingAs($this->owner)
            ->test(Services::class)
            ->call('create')
            ->set('name', 'Corte clásico')
            ->set('durationMinutes', 45)
            ->set('price', '180.50')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Corte clásico');

        $service = $this->business->services()->sole();
        $this->assertSame(45, $service->duration_minutes);
        $this->assertSame(18050, $service->price_cents);

        $component->call('edit', $service->id)
            ->assertSet('price', '180.50')
            ->set('name', 'Corte moderno')
            ->call('save');

        $this->assertSame('Corte moderno', $service->fresh()->name);
    }

    #[Test]
    public function service_fields_are_validated(): void
    {
        Livewire::actingAs($this->owner)
            ->test(Services::class)
            ->call('create')
            ->set('durationMinutes', 2)
            ->set('price', '-5')
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'durationMinutes' => 'min', 'price' => 'min']);
    }

    #[Test]
    public function services_with_appointments_are_hidden_instead_of_deleted(): void
    {
        $used = $this->serviceWithBusiness();
        Appointment::factory()->for($used)->for($this->business)->create();
        $unused = $this->serviceWithBusiness();

        Livewire::actingAs($this->owner)
            ->test(Services::class)
            ->call('delete', $used->id)
            ->call('delete', $unused->id);

        $this->assertFalse($used->fresh()->is_active);
        $this->assertNull($unused->fresh());
    }

    #[Test]
    public function owners_cannot_edit_another_business_services(): void
    {
        $foreign = Service::factory()->create();

        Livewire::actingAs($this->owner)->test(Services::class)->call('delete', $foreign->id)->assertNotFound();

        $this->assertNotNull($foreign->fresh());
    }

    #[Test]
    public function the_owner_adds_and_removes_opening_hours(): void
    {
        $component = Livewire::actingAs($this->owner)
            ->test(ScheduleManager::class)
            ->set('newDay', 1)
            ->set('newOpensAt', '18:00')
            ->set('newClosesAt', '21:00')
            ->call('addWindow')
            ->assertHasNoErrors();

        $window = $this->business->schedules()->where('opens_at', '18:00')->sole();

        $component->call('removeWindow', $window->id);

        $this->assertNull($window->fresh());
    }

    #[Test]
    public function overlapping_opening_hours_are_rejected(): void
    {
        Livewire::actingAs($this->owner)
            ->test(ScheduleManager::class)
            ->set('newDay', 1)
            ->set('newOpensAt', '16:00')
            ->set('newClosesAt', '19:00')
            ->call('addWindow')
            ->assertHasErrors('newOpensAt');

        $this->assertSame(1, $this->business->schedules()->where('day_of_week', 1)->count());
    }

    #[Test]
    public function the_owner_blocks_and_unblocks_days(): void
    {
        $component = Livewire::actingAs($this->owner)
            ->test(ScheduleManager::class)
            ->set('blockedDate', '2026-10-20')
            ->set('blockedReason', 'Vacaciones')
            ->call('blockDate')
            ->assertHasNoErrors()
            ->assertSee('Vacaciones');

        $component->set('blockedDate', '2026-10-20')->call('blockDate')->assertHasErrors('blockedDate');
        $component->set('blockedDate', '2026-10-01')->call('blockDate')->assertHasErrors('blockedDate');

        $component->call('unblockDate', $this->business->blockedDates()->sole()->id);

        $this->assertSame(0, $this->business->blockedDates()->count());
    }

    #[Test]
    public function the_owner_updates_the_business_settings(): void
    {
        Livewire::actingAs($this->owner)
            ->test(BusinessSettings::class)
            ->set('name', 'Spa Serenidad')
            ->set('slug', 'spa-serenidad')
            ->set('timezone', 'America/Bogota')
            ->set('slotIntervalMinutes', 15)
            ->set('requiresConfirmation', true)
            ->call('save')
            ->assertHasNoErrors();

        $business = $this->business->fresh();
        $this->assertSame('spa-serenidad', $business->slug);
        $this->assertSame('America/Bogota', $business->timezone);
        $this->assertSame(15, $business->slot_interval_minutes);
        $this->assertTrue($business->requires_confirmation);
    }

    #[Test]
    public function settings_reject_taken_slugs_and_bad_values(): void
    {
        Business::factory()->create(['slug' => 'ocupado']);

        Livewire::actingAs($this->owner)
            ->test(BusinessSettings::class)
            ->set('slug', 'ocupado')
            ->set('timezone', 'Marte/Olympus')
            ->set('slotIntervalMinutes', 7)
            ->call('save')
            ->assertHasErrors(['slug', 'timezone', 'slotIntervalMinutes']);

        $this->actingAs($this->owner);
        Livewire::test(BusinessSettings::class)->set('slug', 'Con Espacios')->call('save')->assertHasErrors('slug');
    }

    private function serviceWithBusiness(): Service
    {
        return Service::factory()->for($this->business)->create();
    }
}
