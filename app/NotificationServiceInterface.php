<?php

namespace App;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;

interface NotificationServiceInterface
{
    public function sendWelcome(User $user): void;

    public function sendSubscriptionExpiring(Subscription $subscription, int $daysRemaining): void;

    public function sendPaymentReceived(Payment $payment): void;
}
