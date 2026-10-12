<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Notifications\AppointmentReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('appointments:send-reminders')]
#[Description('Remind customers (by email and WhatsApp) of confirmed appointments that start within the next 24 hours')]
class SendAppointmentReminders extends Command
{
    public function handle(): int
    {
        $sent = 0;

        Appointment::query()
            ->with('business', 'service', 'staffMember')
            ->where('status', AppointmentStatus::Confirmed)
            ->whereNull('reminder_sent_at')
            ->where(fn ($query) => $query->whereNotNull('customer_email')->orWhere('whatsapp_opt_in', true))
            ->whereBetween('starts_at', [now(), now()->addDay()])
            // Customers who just booked already have the confirmation email.
            ->where('created_at', '<=', now()->subHours(2))
            ->lazyById()
            ->each(function (Appointment $appointment) use (&$sent) {
                if (! $appointment->canBeNotified()) {
                    return;
                }

                $appointment->notifyCustomer(new AppointmentReminder($appointment));

                $appointment->update(['reminder_sent_at' => now()]);
                $sent++;
            });

        $this->info("Recordatorios enviados: {$sent}");

        return self::SUCCESS;
    }
}
