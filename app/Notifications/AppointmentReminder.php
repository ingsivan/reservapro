<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminder extends Notification implements ShouldQueue
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
        $business = $appointment->business;

        $message = (new MailMessage)
            ->subject("Recordatorio: tu cita en {$business->name}")
            ->greeting("¡Hola, {$appointment->customer_name}!")
            ->line('Te recordamos tu próxima cita:')
            ->line("**Servicio:** {$appointment->service->name}")
            ->line("**Fecha:** {$appointment->whenLabel()}");

        if ($business->address) {
            $message->line("**Dirección:** {$business->address}");
        }

        return $message->action('Ver o cancelar mi cita', $appointment->manageUrl());
    }
}
