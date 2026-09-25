<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'tad:backup-database
        {--prune : Hapus backup lama (server & Google Drive) setelah backup berhasil}
        {--with-files : Ikut backup file unggahan (storage/app/public) sebagai .zip}
        {--no-drive : Jangan kirim ke Google Drive}';

    protected $aliases = ['ims:backup-database'];

    protected $description = 'Backup database TAD (.sql.gz), opsional file unggahan (.zip), lalu salin ke Google Drive bila terhubung.';

    public function handle(BackupManager $backups): int
    {
        $this->info('Memulai backup...');

        try {
            $result = $backups->backup((bool) $this->option('with-files'), ! $this->option('no-drive'));
        } catch (Throwable $e) {
            $this->error('Backup gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        foreach ($result['files'] as $path) {
            $this->line('File: '.$path.' ('.number_format(File::size($path) / 1048576, 2).' MB)');
        }

        if ($result['drive'] !== []) {
            $this->info('Tersalin ke Google Drive: '.implode(', ', $result['drive']));
        } elseif ($result['drive_error']) {
            $this->warn('Gagal dikirim ke Google Drive: '.$result['drive_error']);
        }

        if ($this->option('prune')) {
            $deleted = $backups->prune();
            $this->info("Backup lama dihapus: {$deleted['local']} di server, {$deleted['drive']} di Google Drive.");
        }

        $this->info('Backup berhasil dibuat.');

        return self::SUCCESS;
    }
}
