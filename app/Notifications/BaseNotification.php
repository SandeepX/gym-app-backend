<?php

namespace App\Notifications;

use App\Channels\TwilioSmsChannel;
use App\GymNotificationInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class BaseNotification extends Notification implements GymNotificationInterface, ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    //    public string $queue  = 'notifications';
    public bool $deleteWhenMissingModels = true;

    public function via(mixed $notifiable): array
    {
        return ['mail', TwilioSmsChannel::class];
    }
}
