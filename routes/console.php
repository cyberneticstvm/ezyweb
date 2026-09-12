<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:process')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=168')->dailyAt('02:30')->withoutOverlapping();
Schedule::command('queue:prune-batches --hours=48 --unfinished=72 --cancelled=72')->dailyAt('02:45')->withoutOverlapping();
