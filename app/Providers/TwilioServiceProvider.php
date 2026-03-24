<?php

namespace App\Providers;

use App\Channels\TwilioSmsChannel;
use Illuminate\Support\ServiceProvider;
use Twilio\Rest\Client;

class TwilioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TwilioSmsChannel::class, function () {
            return new TwilioSmsChannel(
                new Client(
                    config('services.twilio.sid'),
                    config('services.twilio.token')
                ),
                config('services.twilio.from')
            );
        });
    }
}
