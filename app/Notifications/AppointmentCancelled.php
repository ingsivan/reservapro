<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the customer when the business cancels, or to the business owner
 * when the customer cancels.
 */
class AppointmentCancelled extends Notification implements ShouldQueue
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

        if ($notifiable instanceof User) {
            return (new MailMessage)
                ->subject("Cita cancelada: {$appointment->customer_name}")
                ->greeting('Se liberó un horario')
                ->line("{$appointment->customer_name} canceló su cita de {$appointment->service->name}.")
                ->line("**Fecha:** {$appointment->whenLabel()}")
                ->when($appointment->staffMember, fn (MailMessage $message, StaffMember $staffMember) => $message->line("**Con:** {$staffMember->name}"))
                ->action('Ver la agenda', route('admin.agenda'));
        }

        return (new MailMessage)
            ->subject("Tu cita en {$appointment->business->name} fue cancelada")
            ->greeting("Hola, {$appointment->customer_name}")
            ->line("{$appointment->business->name} tuvo que cancelar tu cita de {$appointment->service->name} del {$appointment->whenLabel()}.")
            ->line('Lamentamos el inconveniente. Puedes elegir otro horario cuando quieras.')
            ->action('Reservar de nuevo', route('booking', $appointment->business));
    }
}
