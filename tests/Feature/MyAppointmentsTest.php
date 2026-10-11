<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Livewire\MyAppointments;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MyAppointmentsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function customers_see_and_cancel_their_own_appointments(): void
    {
        Notification::fake();
        $client = User::factory()->create(['role' => Role::Client]);
        $mine = Appointment::factory()->for($client)->create();
        Appointment::factory()->create(['customer_name' => 'Otra persona']);

        Livewire::actingAs($client)
            ->test(MyAppointments::class)
            ->assertSee($mine->service->name)
            ->assertDontSee('Otra persona')
            ->call('cancel', $mine->id)
            ->assertSee('Cancelaste tu cita');

        $this->assertSame(AppointmentStatus::Cancelled, $mine->fresh()->status);
    }

    #[Test]
    public function customers_cannot_cancel_appointments_of_others(): void
    {
        $foreign = Appointment::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(MyAppointments::class)
            ->call('cancel', $foreign->id)
            ->assertNotFound();

        $this->assertSame(AppointmentStatus::Confirmed, $foreign->fresh()->status);
    }

    #[Test]
    public function the_page_requires_signing_in(): void
    {
        $this->get(route('my-appointments'))->assertRedirect(route('login'));
    }
}
