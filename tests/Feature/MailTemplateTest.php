<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentBooked;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MailTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function appointment_emails_use_the_branded_spanish_template(): void
    {
        $appointment = Appointment::factory()->create(['customer_name' => 'Ana']);

        $html = (string) (new AppointmentBooked($appointment))->toMail(new AnonymousNotifiable)->render();

        $this->assertStringContainsString('¡Hola, Ana!', $html);
        $this->assertStringContainsString('#0d9488', $html);
        $this->assertStringContainsString('Ver, cambiar o cancelar mi cita', $html);
        $this->assertStringContainsString('Si el botón «Ver, cambiar o cancelar mi cita» no funciona', $html);
        $this->assertStringContainsString('El equipo de '.config('app.name'), $html);
        $this->assertStringNotContainsString('All rights reserved', $html);
        $this->assertStringNotContainsString('Regards', $html);
    }

    #[Test]
    public function the_password_reset_email_is_in_spanish(): void
    {
        $user = User::factory()->create();

        $html = (string) (new ResetPassword('token'))->toMail($user)->render();

        $this->assertStringContainsString('Elegir una contraseña nueva', $html);
        $this->assertStringContainsString('Saludos,', $html);
        $this->assertStringNotContainsString('trouble clicking', $html);
    }
}
