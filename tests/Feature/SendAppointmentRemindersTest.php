<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Notifications\AppointmentReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendAppointmentRemindersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reminds_confirmed_appointments_in_the_next_24_hours_once(): void
    {
        Notification::fake();

        $due = Appointment::factory()->at(now()->addHours(20))->create(['created_at' => now()->subDays(3)]);
        Appointment::factory()->at(now()->addDays(3))->create(['created_at' => now()->subDays(3)]);
        Appointment::factory()->cancelled()->at(now()->addHours(10))->create(['created_at' => now()->subDays(3)]);
        Appointment::factory()->pending()->at(now()->addHours(10))->create(['created_at' => now()->subDays(3)]);
        Appointment::factory()->at(now()->addHours(5))->create(['created_at' => now()->subMinutes(10)]);

        $this->artisan('appointments:send-reminders')->expectsOutput('Recordatorios enviados: 1')->assertSuccessful();
        $this->artisan('appointments:send-reminders')->expectsOutput('Recordatorios enviados: 0');

        Notification::assertSentOnDemandTimes(AppointmentReminder::class, 1);
        $this->assertNotNull($due->fresh()->reminder_sent_at);
    }
}
