<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\StaffMember;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentConfirmed extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->success()
            ->subject("Tu cita en {$appointment->business->name} está confirmada")
            ->greeting("¡Hola, {$appointment->customer_name}!")
            ->line("{$appointment->business->name} confirmó tu cita.")
            ->line("**Servicio:** {$appointment->service->name}")
            ->when($appointment->staffMember, fn (MailMessage $message, StaffMember $staffMember) => $message->line("**Te atiende:** {$staffMember->name}"))
            ->line("**Fecha:** {$appointment->whenLabel()}")
            ->action('Ver mi cita', $appointment->manageUrl());
    }
}
