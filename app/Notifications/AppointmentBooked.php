<?php

namespace App\Notifications;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\StaffMember;
use App\Notifications\Concerns\ReachesCustomers;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentBooked extends Notification implements ShouldQueue
{
    use Queueable, ReachesCustomers;

    public function __construct(public Appointment $appointment) {}

    public function toMail(object $notifiable): MailMessage
    {
        $appointment = $this->appointment;
        $isPending = $appointment->status === AppointmentStatus::Pending;

        return (new MailMessage)
            ->subject($isPending
                ? "Recibimos tu solicitud en {$appointment->business->name}"
                : "Tu cita en {$appointment->business->name} está confirmada")
            ->greeting("¡Hola, {$appointment->customer_name}!")
            ->line($isPending
                ? 'Recibimos tu solicitud de cita. El negocio la revisará y te escribiremos en cuanto la confirme.'
                : 'Tu cita quedó confirmada. Estos son los detalles:')
            ->line("**Servicio:** {$appointment->service->name}")
            ->when($appointment->staffMember, fn (MailMessage $message, StaffMember $staffMember) => $message->line("**Te atiende:** {$staffMember->name}"))
            ->line("**Fecha:** {$appointment->whenLabel()}")
            ->line("**Precio:** {$appointment->formattedPrice()}")
            ->line("**Código:** {$appointment->code}")
            ->action('Ver, cambiar o cancelar mi cita', $appointment->manageUrl())
            ->line("Si no puedes asistir, cambia el horario o cancela con al menos {$appointment->business->cancellation_notice_hours} horas de anticipación.");
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $appointment = $this->appointment;

        return WhatsAppMessage::template($appointment->status === AppointmentStatus::Pending ? 'cita_solicitada' : 'cita_confirmada', [
            $appointment->customer_name,
            $appointment->business->name,
            $appointment->serviceLabel(),
            $appointment->whenLabel(),
            $appointment->manageUrl(),
        ]);
    }
}
