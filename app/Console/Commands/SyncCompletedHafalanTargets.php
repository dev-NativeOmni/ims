<?php

namespace App\Console\Commands;

use App\Services\HafalanTargetAutoCompletionService;
use Illuminate\Console\Command;

class SyncCompletedHafalanTargets extends Command
{
    protected $signature = 'tad:sync-completed-targets {--dry-run : Tampilkan jumlah target yang cocok tanpa mengubah database}';

    protected $aliases = ['ims:sync-completed-targets'];

    protected $description = 'Target hafalan aktif: tandai Selesai bila sudah tercapai, Terlewat bila deadline lewat dan belum tercapai.';

    public function handle(HafalanTargetAutoCompletionService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun
            ? 'Mode dry-run aktif. Database tidak akan diubah.'
            : 'Mulai sinkronisasi target hafalan...'
        );

        $counts = $service->syncExistingTargets($dryRun);

        $this->info(($dryRun ? 'Akan diubah: ' : 'Selesai. ')
            ."{$counts['completed']} target menjadi Selesai, {$counts['missed']} target menjadi Terlewat.");

        return self::SUCCESS;
    }
}
