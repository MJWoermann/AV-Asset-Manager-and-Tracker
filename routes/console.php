<?php

use App\Support\EmailIntegrationSettings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (filled(config('attachments.backup_disk'))) {
    Schedule::command('app:backup')
        ->dailyAt('02:15')
        ->withoutOverlapping(180)
        ->onOneServer();
}

Schedule::command('email:poll-inbound')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->when(function (): bool {
        if (! EmailIntegrationSettings::isEnabled()) {
            return false;
        }

        $interval = max(1, (int) EmailIntegrationSettings::get()['poll_interval_minutes']);

        return now()->minute % $interval === 0;
    });

Schedule::command('email:send-digests')
    ->daily()
    ->withoutOverlapping(60)
    ->when(fn (): bool => EmailIntegrationSettings::isEnabled());
