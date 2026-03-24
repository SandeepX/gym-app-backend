<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\SubscriptionExpiringNotification;
use App\Notifications\WelcomeNotification;
use App\NotificationServiceInterface;
use Illuminate\Support\Facades\Log;

class NotificationService implements NotificationServiceInterface
{
    public function sendWelcome(User $user): void
    {
        $this->send($user, new WelcomeNotification);
    }

    public function sendSubscriptionExpiring(Subscription $subscription, int $daysRemaining): void
    {
        $this->send(
            $subscription->member->user,
            new SubscriptionExpiringNotification($subscription, $daysRemaining)
        );
    }

    public function sendPaymentReceived(Payment $payment): void
    {
        $this->send(
            $payment->member->user,
            new PaymentReceivedNotification($payment)
        );
    }

    private function send(User $user, $notification): void
    {
        $sync = filter_var(config('notifications.sync'), FILTER_VALIDATE_BOOLEAN);

        try {
            $sync
                ? $user->notifyNow($notification)
                : $user->notify($notification);
        } catch (\Throwable $e) {
            Log::error('Notification failed', [
                'user' => $user->id,
                'notification' => get_class($notification),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
