<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tad:prune-api-tokens --days=7')
    ->dailyAt('02:00');

Schedule::command('notifications:generate')
    ->dailyAt('06:00')
    ->withoutOverlapping();

// Status target hafalan otomatis: Selesai bila sudah tercapai, Terlewat bila deadline lewat
// dan belum tercapai (saat setoran disimpan juga langsung dievaluasi per murid).
Schedule::command('tad:sync-completed-targets')
    ->dailyAt('01:00')
    ->withoutOverlapping();

// Backup database harian pukul 03:00 (Senin-Sabtu), plus file unggahan setiap Ahad; disalin ke
// Google Drive bila terhubung dan backup lama dihapus (lihat config/database_backup.php).
Schedule::command('tad:backup-database --prune')
    ->dailyAt('03:00')
    ->days([1, 2, 3, 4, 5, 6])
    ->withoutOverlapping();

Schedule::command('tad:backup-database --prune --with-files')
    ->sundays()
    ->at('03:00')
    ->withoutOverlapping();
