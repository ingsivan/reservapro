<?php

namespace Tests\Feature;

use App\Livewire\Admin\Agenda;
use App\Livewire\Admin\NewAppointment;
use App\Livewire\Admin\ScheduleManager;
use App\Livewire\Admin\Services;
use App\Livewire\Admin\Team;
use App\Livewire\BookingWizard;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\StaffMember;
use App\Models\User;
use App\Notifications\AppointmentBooked;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'America/Mexico_City';

    private Business $business;

    private User $owner;

    private Service $haircut;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00', self::TZ));
        $this->business = Business::factory()->openDaily('09:00', '12:00')->create();
        $this->owner = $this->business->owner;
        $this->haircut = Service::factory()->for($this->business)->minutes(60)->create(['name' => 'Corte']);
    }

    #[Test]
    public function the_owner_adds_people_and_the_first_one_takes_the_existing_agenda(): void
    {
        $existing = $this->appointment('2026-10-13 10:00');

        Livewire::actingAs($this->owner)
            ->test(Team::class)
            ->assertSee('Por ahora atiendes tú solo')
            ->call('create')
            ->assertSet('serviceIds', [$this->haircut->id])
            ->set('name', 'Diego Ramírez')
            ->set('title', 'Barbero')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'Agregamos a Diego. Las citas que ya tenías quedaron en su agenda.')
            ->call('create')
            ->assertSet('color', 'violet')
            ->set('name', 'Sofía Herrera')
            ->set('serviceIds', [])
            ->call('save')
            ->assertSee('Diego Ramírez')
            ->assertSee('No ofrece ningún servicio');

        $diego = StaffMember::where('name', 'Diego Ramírez')->sole();
        $this->assertTrue($existing->fresh()->staffMember->is($diego));
        $this->assertSame([$this->haircut->id], $diego->services()->pluck('services.id')->all());
        $this->assertSame(0, StaffMember::where('name', 'Sofía Herrera')->sole()->services()->count());
    }

    #[Test]
    public function people_with_appointments_are_deactivated_instead_of_deleted(): void
    {
        $diego = $this->staffMember('Diego Ramírez');
        $sofia = $this->staffMember('Sofía Herrera');
        $this->appointment('2026-10-13 10:00', $diego);

        Livewire::actingAs($this->owner)
            ->test(Team::class)
            ->call('delete', $diego->id)
            ->call('delete', $sofia->id);

        $this->assertFalse($diego->fresh()->is_active);
        $this->assertModelMissing($sofia);
    }

    #[Test]
    public function owners_cannot_touch_another_business_team(): void
    {
        $stranger = StaffMember::factory()->create();

        Livewire::actingAs($this->owner)->test(Team::class)->call('edit', $stranger->id)->assertNotFound();
    }

    #[Test]
    public function new_services_are_offered_by_the_whole_active_team(): void
    {
        $diego = $this->staffMember('Diego Ramírez');
        $away = StaffMember::factory()->for($this->business)->inactive()->create();

        Livewire::actingAs($this->owner)
            ->test(Services::class)
            ->call('create')
            ->set('name', 'Barba')
            ->set('durationMinutes', 30)
            ->set('price', '150')
            ->call('save');

        $beard = Service::where('name', 'Barba')->sole();
        $this->assertTrue($beard->staffMembers()->whereKey($diego->id)->exists());
        $this->assertFalse($beard->staffMembers()->whereKey($away->id)->exists());
    }

    #[Test]
    public function a_person_can_work_their_own_hours(): void
    {
        $diego = $this->staffMember('Diego Ramírez');

        $component = Livewire::actingAs($this->owner)
            ->test(ScheduleManager::class, ['staffMemberId' => $diego->id])
            ->assertSee('Diego trabaja en el horario del negocio')
            ->call('addWindow')
            ->call('useOwnHours');

        $this->assertSame(7, $diego->schedules()->count());

        $component->set('newDay', 1)->set('newOpensAt', '15:00')->set('newClosesAt', '18:00')->call('addWindow');
        $this->assertSame(8, $diego->schedules()->count());
        $this->assertSame(7, $this->business->schedules()->count());

        $component->call('useBusinessHours');
        $this->assertSame(0, $diego->schedules()->count());
    }

    #[Test]
    public function customers_choose_who_attends_them(): void
    {
        $diego = $this->staffMember('Diego Ramírez');
        $this->staffMember('Sofía Herrera');
        $this->appointment('2026-10-13 10:00', $diego);
        $slot = CarbonImmutable::parse('2026-10-13 10:00', self::TZ)->toIso8601String();

        Livewire::test(BookingWizard::class, ['business' => $this->business])
            ->call('selectService', $this->haircut->id)
            ->assertSee('¿Con quién?')
            ->assertSee('Sofía Herrera')
            ->call('selectStaff', $diego->id)
            ->call('selectDate', '2026-10-13')
            ->call('selectSlot', $slot)
            ->assertSet('startsAt', null)
            ->call('selectStaff', null)
            ->call('selectSlot', $slot)
            ->assertSet('startsAt', $slot)
            ->set('customerName', 'Ana López')
            ->set('customerEmail', 'ana@example.com')
            ->call('book')
            ->assertSee('Te atiende')
            ->assertSee('Sofía Herrera');

        $appointment = Appointment::where('customer_name', 'Ana López')->sole();
        $this->assertSame('Sofía Herrera', $appointment->staffMember->name);
        Notification::assertSentTo(new AnonymousNotifiable, AppointmentBooked::class, function (AppointmentBooked $notification) {
            return str_contains(implode("\n", $notification->toMail(new AnonymousNotifiable)->introLines), '**Te atiende:** Sofía Herrera');
        });
    }

    #[Test]
    public function the_agenda_filters_by_person_and_books_with_someone(): void
    {
        $diego = $this->staffMember('Diego Ramírez');
        $sofia = $this->staffMember('Sofía Herrera');
        $this->appointment('2026-10-13 10:00', $diego, ['customer_name' => 'Cliente de Diego']);
        $this->appointment('2026-10-13 10:00', $sofia, ['customer_name' => 'Cliente de Sofía']);

        Livewire::actingAs($this->owner)
            ->test(Agenda::class, ['date' => '2026-10-13'])
            ->assertSee('Cliente de Diego')
            ->assertSee('Cliente de Sofía')
            ->call('filterByStaff', (string) $sofia->id)
            ->assertSet('staffFilter', (string) $sofia->id)
            ->assertDontSee('Cliente de Diego')
            ->call('filterByStaff', '999')
            ->assertSet('staffFilter', '');

        Livewire::actingAs($this->owner)
            ->test(NewAppointment::class)
            ->call('start', '2026-10-13')
            ->call('selectStaff', $sofia->id)
            ->call('selectSlot', CarbonImmutable::parse('2026-10-13 11:00', self::TZ)->toIso8601String())
            ->set('customerName', 'Luis')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'Cita creada con Sofía.');

        $this->assertTrue(Appointment::where('customer_name', 'Luis')->sole()->staffMember->is($sofia));
    }

    private function staffMember(string $name): StaffMember
    {
        return StaffMember::factory()->for($this->business)->offeringAllServices()->create(['name' => $name]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function appointment(string $localStart, ?StaffMember $staffMember = null, array $attributes = []): Appointment
    {
        return Appointment::factory()
            ->for($this->haircut)
            ->at(CarbonImmutable::parse($localStart, self::TZ)->utc())
            ->create(['staff_member_id' => $staffMember?->id, ...$attributes]);
    }
}
