<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\NotificationServiceInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-subscription-notifications')]
#[Description('Send expiring and expired subscription notifications')]
class SendSubscriptionNotifications extends Command
{
    public function __construct(private readonly NotificationServiceInterface $notificationService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->notifyExpiring(days: 7);

        $this->info('✅ All subscription notifications sent.');
    }

    private function notifyExpiring(int $days): void
    {
        Subscription::with(['member.user', 'plan'])
            ->where('status', 'active')
            ->whereDate('end_date', now()->addDays($days)->toDateString())
            ->get()
            ->each(function ($subscription) use ($days) {
                $this->notificationService->sendSubscriptionExpiring($subscription, $days);
                $this->info("Expiring ({$days}d): {$subscription->member->user->name}");
            });
    }
}
