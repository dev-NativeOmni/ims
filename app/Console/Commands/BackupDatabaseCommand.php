<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'ims:backup-database {--prune : Hapus backup lama setelah backup berhasil}';

    protected $description = 'Membuat backup database MySQL IMS dalam format SQL.';

    public function handle(): int
    {
        $defaultConnection = config('database.default');

        if (! in_array($defaultConnection, ['mysql', 'pgsql'], true)) {
            $this->error('Backup ini hanya mendukung koneksi mysql dan pgsql. Koneksi aktif sekarang: '.$defaultConnection);

            return self::FAILURE;
        }

        $connection = config("database.connections.{$defaultConnection}") ?? [];

        $database = (string) ($connection['database'] ?? '');

        if ($database === '' && ! empty($connection['url'])) {
            $parsedUrl = parse_url($connection['url']);
            $database = ltrim($parsedUrl['path'] ?? '', '/');
        }

        if ($database === '') {
            $this->error('Nama database tidak ditemukan di konfigurasi.');

            return self::FAILURE;
        }

        $backupDirectory = (string) config('database_backup.path');

        File::ensureDirectoryExists($backupDirectory);

        $filename = now()->format('Y-m-d_His').'_'.$this->safeFilename($database).'.sql';
        $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.$filename;

        [$command, $envVars] = $this->buildCommand($defaultConnection, $connection, $database, $backupPath);

        $this->info('Memulai backup database...');
        $this->line('Driver: '.$defaultConnection);
        $this->line('Database: '.$database);
        $this->line('Target: '.$backupPath);

        $process = new Process($command, null, $envVars);
        $process->setTimeout((int) config('database_backup.timeout', 300));
        $process->run();

        if (! $process->isSuccessful()) {
            if (File::exists($backupPath)) {
                File::delete($backupPath);
            }

            $this->error('Backup gagal.');
            $this->line($process->getErrorOutput() ?: $process->getOutput());

            return self::FAILURE;
        }

        if (! File::exists($backupPath) || File::size($backupPath) < 1) {
            if (File::exists($backupPath)) {
                File::delete($backupPath);
            }

            $this->error('Backup gagal. File backup kosong atau tidak terbentuk.');

            return self::FAILURE;
        }

        if ($this->option('prune')) {
            $deleted = $this->pruneOldBackups();

            if ($deleted > 0) {
                $this->info("Backup lama dihapus: {$deleted} file.");
            }
        }

        $this->info('Backup berhasil dibuat.');
        $this->line('File: '.$backupPath);
        $this->line('Ukuran: '.$this->formatBytes(File::size($backupPath)));

        return self::SUCCESS;
    }

    private function buildCommand(string $driver, array $connection, string $database, string $backupPath): array
    {
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? ($driver === 'pgsql' ? '5432' : '3306'));
        $username = (string) ($connection['username'] ?? '');
        $password = (string) ($connection['password'] ?? '');

        if (! empty($connection['url'])) {
            $parsedUrl = parse_url($connection['url']);
            $host = $parsedUrl['host'] ?? $host;
            $port = (string) ($parsedUrl['port'] ?? $port);
            $username = $parsedUrl['user'] ?? $username;
            $password = $parsedUrl['pass'] ?? $password;
        }

        $envVars = [];

        if ($driver === 'pgsql') {
            $command = [
                (string) config('database_backup.pg_dump_path', 'pg_dump'),
                '--host='.$host,
                '--port='.$port,
                '--username='.$username,
                '--dbname='.$database,
                '--file='.$backupPath,
                '--no-password',
            ];

            if ($password !== '') {
                $envVars['PGPASSWORD'] = $password;
            }

            return [$command, $envVars];
        }

        $command = [
            (string) config('database_backup.mysqldump_path', 'mysqldump'),
            '--host='.$host,
            '--port='.$port,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--databases',
            $database,
            '--result-file='.$backupPath,
        ];

        if ($username !== '') {
            $command[] = '--user='.$username;
        }

        if ($password !== '') {
            $command[] = '--password='.$password;
        }

        return [$command, $envVars];
    }

    private function pruneOldBackups(): int
    {
        $backupDirectory = (string) config('database_backup.path');
        $retentionDays = (int) config('database_backup.retention_days', 14);

        if ($retentionDays <= 0 || ! File::isDirectory($backupDirectory)) {
            return 0;
        }

        $deleted = 0;
        $cutoffTimestamp = now()->subDays($retentionDays)->timestamp;

        foreach (File::files($backupDirectory) as $file) {
            if ($file->getExtension() !== 'sql') {
                continue;
            }

            if ($file->getMTime() >= $cutoffTimestamp) {
                continue;
            }

            File::delete($file->getPathname());
            $deleted++;
        }

        return $deleted;
    }

    private function safeFilename(string $value): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_\-]/', '_', $value);

        return $clean ?: 'database';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }
}
