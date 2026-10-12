<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\StaffMember;
use App\Notifications\Concerns\ReachesCustomers;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentConfirmed extends Notification implements ShouldQueue
{
    use Queueable, ReachesCustomers;

    public function __construct(public Appointment $appointment) {}

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

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $appointment = $this->appointment;

        return WhatsAppMessage::template('cita_confirmada', [
            $appointment->customer_name,
            $appointment->business->name,
            $appointment->serviceLabel(),
            $appointment->whenLabel(),
            $appointment->manageUrl(),
        ]);
    }
}
