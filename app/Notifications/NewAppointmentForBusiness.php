<?php

namespace App\Notifications;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAppointmentForBusiness extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appointment = $this->appointment;
        $message = (new MailMessage)
            ->subject("Nueva cita: {$appointment->service->name} con {$appointment->customer_name}")
            ->greeting('Tienes una nueva reservación')
            ->line("**Cliente:** {$appointment->customer_name} ({$appointment->customer_email})")
            ->line("**Servicio:** {$appointment->service->name}")
            ->line("**Fecha:** {$appointment->whenLabel()}");

        if ($appointment->status === AppointmentStatus::Pending) {
            $message->line('La cita está pendiente: confírmala desde tu agenda para avisar al cliente.');
        }

        return $message->action('Abrir la agenda', route('admin.agenda', ['date' => $appointment->localStartsAt()->toDateString()]));
    }
}
