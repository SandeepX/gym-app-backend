<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification Sending Mode
    |--------------------------------------------------------------------------
    |
    | Set to true to send notifications synchronously (notifyNow),
    | or false to send them via queue (notify).
    |
    */

    'sync' => env('SEND_NOTIFICATIONS_SYNC', false),

];
