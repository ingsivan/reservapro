<?php

namespace Tests\Feature;

use App\Livewire\BookingWizard;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\StaffMember;
use App\Notifications\AppointmentBooked;
use App\Notifications\AppointmentCancelled;
use App\Notifications\AppointmentReminder;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\NewAppointmentForBusiness;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'America/Mexico_City';

    private Business $business;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00', self::TZ));
        $this->business = Business::factory()->openDaily('09:00', '13:00')->create(['name' => 'Barbería El Clásico']);
        $this->service = Service::factory()->for($this->business)->minutes(60)->create(['name' => 'Corte']);
    }

    #[Test]
    public function customers_who_ask_for_it_get_the_confirmation_by_whatsapp_too(): void
    {
        Notification::fake();

        $this->bookingWizard()
            ->set('customerPhone', '55 1234 5678')
            ->set('whatsappReminders', true)
            ->call('book')
            ->assertHasNoErrors()
            ->assertSee('También te escribiremos por WhatsApp al +525512345678');

        $this->assertTrue(Appointment::sole()->whatsapp_opt_in);
        Notification::assertSentOnDemand(AppointmentBooked::class, function (AppointmentBooked $notification, array $channels, AnonymousNotifiable $notifiable) {
            return $channels === ['mail', WhatsAppChannel::class] && $notifiable->routes['whatsapp'] === '+525512345678';
        });
        Notification::assertSentTo($this->business->owner, NewAppointmentForBusiness::class, fn ($notification, array $channels) => $channels === ['mail']);
    }

    #[Test]
    public function whatsapp_needs_a_phone_number_that_can_be_read(): void
    {
        Notification::fake();

        $this->bookingWizard()
            ->set('customerPhone', '1234')
            ->set('whatsappReminders', true)
            ->call('book')
            ->assertHasErrors('customerPhone');

        $this->assertSame(0, Appointment::count());
    }

    #[Test]
    public function without_whatsapp_only_email_is_sent(): void
    {
        Notification::fake();

        $this->bookingWizard()->set('customerPhone', '55 1234 5678')->call('book');

        Notification::assertSentOnDemand(AppointmentBooked::class, fn ($notification, array $channels) => $channels === ['mail']);
    }

    #[Test]
    public function reminders_reach_customers_who_only_left_their_whatsapp(): void
    {
        Notification::fake();
        $appointment = Appointment::factory()->for($this->service)
            ->at(CarbonImmutable::parse('2026-10-12 15:00', self::TZ)->utc())
            ->create(['customer_email' => null, 'customer_phone' => '55 1234 5678', 'whatsapp_opt_in' => true, 'created_at' => now()->subDay()]);
        Appointment::factory()->for($this->service)
            ->at(CarbonImmutable::parse('2026-10-12 17:00', self::TZ)->utc())
            ->create(['customer_email' => null, 'customer_phone' => '55 1234 5678', 'created_at' => now()->subDay()]);

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        Notification::assertSentOnDemandTimes(AppointmentReminder::class, 1);
        Notification::assertSentOnDemand(AppointmentReminder::class, fn ($notification, array $channels) => $channels === [WhatsAppChannel::class]);
        $this->assertNotNull($appointment->fresh()->reminder_sent_at);
    }

    #[Test]
    public function without_credentials_the_message_is_written_to_the_log(): void
    {
        Http::fake();
        Log::spy();
        $appointment = $this->appointmentWithWhatsApp();

        $appointment->notifyCustomer(new AppointmentReminder($appointment));

        Http::assertNothingSent();
        Log::shouldHaveReceived('info')->withArgs(fn (string $message) => str_starts_with($message, 'WhatsApp para +525512345678 (recordatorio_cita): Hola Ana López, te recordamos tu cita en Barbería El Clásico: Corte con Diego Ramírez,'));
    }

    #[Test]
    public function with_credentials_it_sends_the_template_to_the_cloud_api(): void
    {
        config(['services.whatsapp.token' => 'test-token', 'services.whatsapp.phone_number_id' => '123456']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);
        $appointment = $this->appointmentWithWhatsApp();

        $appointment->notifyCustomer(new AppointmentCancelled($appointment));

        Http::assertSent(function (Request $request) use ($appointment) {
            $template = $request['template'];

            return $request->url() === 'https://graph.facebook.com/v21.0/123456/messages'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['to'] === '525512345678'
                && $template['name'] === 'cita_cancelada'
                && $template['language']['code'] === 'es_MX'
                && array_column($template['components'][0]['parameters'], 'text') === [
                    'Ana López', 'Barbería El Clásico', 'Corte con Diego Ramírez', $appointment->whenLabel(), route('booking', $this->business),
                ];
        });
    }

    private function bookingWizard(): Testable
    {
        return Livewire::test(BookingWizard::class, ['business' => $this->business])
            ->call('selectService', $this->service->id)
            ->call('selectDate', '2026-10-13')
            ->call('selectSlot', CarbonImmutable::parse('2026-10-13 10:00', self::TZ)->toIso8601String())
            ->set('customerName', 'Ana López')
            ->set('customerEmail', 'ana@example.com');
    }

    private function appointmentWithWhatsApp(): Appointment
    {
        $diego = StaffMember::factory()->for($this->business)->create(['name' => 'Diego Ramírez']);

        return Appointment::factory()->for($this->service)
            ->at(CarbonImmutable::parse('2026-10-13 10:00', self::TZ)->utc())
            ->create(['customer_name' => 'Ana López', 'customer_email' => null, 'customer_phone' => '55 1234 5678', 'whatsapp_opt_in' => true, 'staff_member_id' => $diego->id]);
    }
}
