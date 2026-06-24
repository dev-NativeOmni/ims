<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('hafizplus:prune-api-tokens --days=7')
    ->dailyAt('02:00');

Schedule::command('notifications:generate')
    ->dailyAt('06:00')
    ->withoutOverlapping();

// Sinkronisasi otomatis target hafalan: tandai 'completed' jika sudah ada
// setoran hafalan lulus yang mencakup seluruh range ayat target.
Schedule::command('hafizplus:sync-completed-targets')
    ->dailyAt('01:00')
    ->withoutOverlapping();