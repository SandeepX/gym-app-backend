<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionExpiringNotification extends BaseNotification
{
    public function __construct(
        private Subscription $subscription,
        private int $daysRemaining
    ) {}

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("⚠️ Membership Expiring in {$this->daysRemaining} Days")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your **{$this->subscription->plan->name}** membership is expiring in **{$this->daysRemaining} days**.")
            ->line("Expiry Date: **{$this->subscription->end_date->format('d M Y')}**")
            ->action('Renew Now', url('/'))
            ->line('Renew now to keep your gym access uninterrupted.')
            ->salutation('Gym App Team');
    }

    public function toSms(mixed $notifiable): string
    {
        return "Hi {$notifiable->name}, your {$this->subscription->plan->name} expires in {$this->daysRemaining} days on {$this->subscription->end_date->format('d M Y')}. Renew now!";
    }
}
