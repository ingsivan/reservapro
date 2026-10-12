<?php

namespace App\Notifications\Channels;

use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends notifications through the WhatsApp Cloud API. Without credentials
 * (local and the demo) the message is written to the log instead, the same
 * way emails are with MAIL_MAILER=log.
 */
class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $to = $notifiable->routeNotificationFor('whatsapp', $notification);

        if (! $to || ! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        /** @var WhatsAppMessage $message */
        $message = $notification->toWhatsApp($notifiable);
        $config = config('services.whatsapp');

        if (empty($config['token']) || empty($config['phone_number_id'])) {
            Log::info("WhatsApp para {$to} ({$message->template}): {$message->text()}");

            return;
        }

        Http::withToken($config['token'])
            ->acceptJson()
            ->post("https://graph.facebook.com/{$config['api_version']}/{$config['phone_number_id']}/messages", $message->toPayload($to, $config['language']))
            ->throw();
    }
}
