<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentReceivedNotification extends BaseNotification
{
    public function __construct(
        private Payment $payment
    ) {}

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('✅ Payment Received - '.$this->payment->invoice_number)
            ->greeting("Hello {$notifiable->name}!")
            ->line('We have received your payment. Here are the details:')
            ->line("Invoice: **{$this->payment->invoice_number}**")
            ->line("Amount:  **{$this->payment->amount} EGP**")
            ->line("Method:  **{$this->payment->payment_method->label()}**")
            ->line("Date:    **{$this->payment->paid_at->format('d M Y H:i')}**")
            ->line('Thank you for your payment!')
            ->salutation('Gym App Team');
    }

    public function toSms(mixed $notifiable): string
    {
        return "Hi {$notifiable->name}, payment of {$this->payment->amount} EGP received. Invoice: {$this->payment->invoice_number}. Thank you!";
    }
}
