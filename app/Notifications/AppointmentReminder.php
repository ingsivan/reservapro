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

class AppointmentReminder extends Notification implements ShouldQueue
{
    use Queueable, ReachesCustomers;

    public function __construct(public Appointment $appointment) {}

    public function toMail(object $notifiable): MailMessage
    {
        $appointment = $this->appointment;
        $business = $appointment->business;

        $message = (new MailMessage)
            ->subject("Recordatorio: tu cita en {$business->name}")
            ->greeting("¡Hola, {$appointment->customer_name}!")
            ->line('Te recordamos tu próxima cita:')
            ->line("**Servicio:** {$appointment->service->name}")
            ->when($appointment->staffMember, fn (MailMessage $message, StaffMember $staffMember) => $message->line("**Te atiende:** {$staffMember->name}"))
            ->line("**Fecha:** {$appointment->whenLabel()}");

        if ($business->address) {
            $message->line("**Dirección:** {$business->address}");
        }

        return $message->action('Ver, cambiar o cancelar mi cita', $appointment->manageUrl());
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $appointment = $this->appointment;

        return WhatsAppMessage::template('recordatorio_cita', [
            $appointment->customer_name,
            $appointment->business->name,
            $appointment->serviceLabel(),
            $appointment->whenLabel(),
            $appointment->manageUrl(),
        ]);
    }
}
