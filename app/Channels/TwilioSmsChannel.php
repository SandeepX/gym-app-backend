<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Twilio\Rest\Client;

class TwilioSmsChannel
{
    public function __construct(
        private Client $twilio,
        private string $from
    ) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $to = $notifiable->routeNotificationFor('sms');

        if (! $to) {
            return;
        }

        $message = $notification->toSms($notifiable);

        $this->twilio->messages->create($to, [
            'from' => $this->from,
            'body' => $message,
        ]);
    }
}
