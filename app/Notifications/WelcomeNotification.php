<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class WelcomeNotification extends BaseNotification
{
    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Welcome to Gym App!')
            ->greeting("Welcome, {$notifiable->name}!")
            ->line('Your account has been created successfully.')
            ->line('You can now access all gym facilities and track your fitness journey.')
            ->action('Get Started', url('/'))
            ->line('We are excited to have you on board!')
            ->salutation('Gym App Team');
    }

    public function toSms(mixed $notifiable): string
    {
        return "Welcome to Gym App, {$notifiable->name}! Your account is ready. We're excited to have you!";
    }
}
