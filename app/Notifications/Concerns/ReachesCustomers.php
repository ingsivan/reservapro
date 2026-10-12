<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\WhatsAppChannel;

/**
 * Email the customer, and send WhatsApp too when they asked for it (their
 * notifiable then has a "whatsapp" route). The business owner only gets email.
 */
trait ReachesCustomers
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return array_values(array_filter([
            $notifiable->routeNotificationFor('mail', $this) ? 'mail' : null,
            $notifiable->routeNotificationFor('whatsapp', $this) ? WhatsAppChannel::class : null,
        ]));
    }
}
