<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('notifications:send-daily-summary')->dailyAt('08:00');
Schedule::command('tasks:generate-recurring')->dailyAt('00:30');
