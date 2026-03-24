<?php

namespace App;

use Illuminate\Notifications\Messages\MailMessage;

interface GymNotificationInterface
{
    public function toMail(mixed $notifiable): MailMessage;

    public function toSms(mixed $notifiable): string;
}
