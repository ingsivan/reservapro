<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\StaffMember;
use App\Models\User;
use App\Notifications\Concerns\ReachesCustomers;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the customer when the business moves the appointment, or to the
 * business owner when the customer moves it.
 */
class AppointmentRescheduled extends Notification implements ShouldQueue
{
    use Queueable, ReachesCustomers;

    public function __construct(public Appointment $appointment, public string $previousWhen) {}

    public function toMail(object $notifiable): MailMessage
    {
        $appointment = $this->appointment;

        if ($notifiable instanceof User) {
            return (new MailMessage)
                ->subject("Cita reprogramada: {$appointment->customer_name}")
                ->greeting('Una cita cambió de horario')
                ->line("{$appointment->customer_name} movió su cita de {$appointment->service->name}.")
                ->line("**Antes:** {$this->previousWhen}")
                ->line("**Ahora:** {$appointment->whenLabel()}")
                ->when($appointment->staffMember, fn (MailMessage $message, StaffMember $staffMember) => $message->line("**Con:** {$staffMember->name}"))
                ->action('Ver la agenda', route('admin.agenda', ['date' => $appointment->localStartsAt()->toDateString()]));
        }

        return (new MailMessage)
            ->subject("Tu cita en {$appointment->business->name} cambió de horario")
            ->greeting("Hola, {$appointment->customer_name}")
            ->line("{$appointment->business->name} movió tu cita de {$appointment->service->name}.")
            ->line("**Antes:** {$this->previousWhen}")
            ->line("**Ahora:** {$appointment->whenLabel()}")
            ->when($appointment->staffMember, fn (MailMessage $message, StaffMember $staffMember) => $message->line("**Te atiende:** {$staffMember->name}"))
            ->action('Ver mi cita', $appointment->manageUrl())
            ->line('Si el nuevo horario no te funciona, desde ese enlace puedes elegir otro o cancelar.');
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $appointment = $this->appointment;

        return WhatsAppMessage::template('cita_reprogramada', [
            $appointment->customer_name,
            $appointment->business->name,
            $appointment->serviceLabel(),
            $appointment->whenLabel(),
            $appointment->manageUrl(),
        ]);
    }
}
