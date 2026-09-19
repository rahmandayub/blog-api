<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process queued webhooks (SendWebhookJob) every minute.
// Required on shared hosting (cPanel) where no queue:work daemon runs:
// the existing `schedule:run` cron picks this up, so no extra cron needed.
// Short max-time keeps each run inside the 1-minute window.
Schedule::command('queue:work database --stop-when-empty --max-time=50 --max-jobs=100 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
