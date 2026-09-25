<?php

namespace App\Services\Backup;

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

/**
 * Backup & restore TAD: database (mysqldump -> .sql.gz) dan file unggahan (storage/app/public ->
 * .zip), disimpan di server dan disalin ke Google Drive bila terhubung. Status backup terakhir
 * dicatat di settings supaya terlihat di halaman Backup & Restore.
 */
class BackupManager
{
    public const EXTENSIONS = ['sql', 'gz', 'zip'];

    public function __construct(private readonly GoogleDriveBackup $drive) {}

    public function directory(): string
    {
        $directory = (string) config('database_backup.path');
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    /**
     * Buat backup database (+ file unggahan bila $withFiles), lalu salin ke Google Drive.
     *
     * @return array{files: array<int, string>, drive: array<int, string>, drive_error: ?string}
     */
    public function backup(bool $withFiles = false, bool $toDrive = true, string $label = ''): array
    {
        $stamp = now()->format('Y-m-d_His').($label !== '' ? '_'.$label : '');
        $result = ['files' => [], 'drive' => [], 'drive_error' => null];

        try {
            $result['files'][] = $this->dumpDatabase($stamp);
            if ($withFiles) {
                $result['files'][] = $this->zipFiles($stamp);
            }

            if ($toDrive && $this->drive->isConnected()) {
                try {
                    foreach ($result['files'] as $path) {
                        $result['drive'][] = $this->drive->upload($path)['name'];
                    }
                } catch (Throwable $e) {
                    report($e);
                    $result['drive_error'] = $e->getMessage();
                }
            }

            $this->recordStatus(true, $result['drive_error'] ? 'Backup tersimpan di server, tetapi gagal dikirim ke Google Drive: '.$result['drive_error'] : null, $result);
        } catch (Throwable $e) {
            report($e);
            $this->recordStatus(false, $e->getMessage(), $result);
            throw $e;
        }

        return $result;
    }

    /**
     * Pulihkan database dari file .sql / .sql.gz. Sebelumnya dibuat backup pengaman
     * ("sebelum-restore") supaya pemulihan bisa dibatalkan.
     *
     * @return array{safety_backup: string}
     */
    public function restoreDatabase(string $path): array
    {
        $this->assertInsideDirectory($path);
        if (! preg_match('/\.sql(\.gz)?$/i', $path)) {
            throw new RuntimeException('File restore database harus .sql atau .sql.gz.');
        }

        $safety = $this->dumpDatabase(now()->format('Y-m-d_His').'_sebelum-restore');

        $sqlPath = $path;
        $temporary = null;
        if (str_ends_with(strtolower($path), '.gz')) {
            $temporary = $sqlPath = $this->directory().DIRECTORY_SEPARATOR.'.restore-'.uniqid().'.sql';
            $this->gunzip($path, $sqlPath);
        }

        try {
            $connection = config('database.connections.mysql');
            $process = new Process([
                (string) config('database_backup.mysql_path', 'mysql'),
                ...$this->connectionArguments($connection),
                (string) $connection['database'],
            ], null, $this->passwordEnvironment($connection));
            $process->setTimeout((int) config('database_backup.timeout', 300) * 4);
            $input = fopen($sqlPath, 'rb');
            $process->setInput($input);
            $process->run();
            fclose($input);

            if (! $process->isSuccessful()) {
                throw new RuntimeException('Restore database gagal: '.trim($process->getErrorOutput() ?: $process->getOutput()));
            }
        } finally {
            if ($temporary) {
                File::delete($temporary);
            }
        }

        // Pengaturan tersimpan di cache; skema disesuaikan bila backup lebih lama dari kode.
        Cache::flush();
        Artisan::call('migrate', ['--force' => true]);

        return ['safety_backup' => basename($safety)];
    }

    /**
     * Pulihkan file unggahan dari .zip (menimpa file dengan nama sama, file lain dibiarkan).
     */
    public function restoreFiles(string $path): int
    {
        $this->assertInsideDirectory($path);
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File .zip tidak bisa dibuka.');
        }

        $target = (string) config('database_backup.files_path');
        File::ensureDirectoryExists($target);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            // Tolak path yang keluar dari folder tujuan (zip slip).
            if (str_contains($name, '..') || str_starts_with($name, '/') || str_starts_with($name, '\\')) {
                $zip->close();
                throw new RuntimeException("Isi .zip tidak aman: {$name}");
            }
        }
        $zip->extractTo($target);
        $count = $zip->numFiles;
        $zip->close();

        return $count;
    }

    /**
     * Hapus backup lokal lebih tua dari retention (backup pengaman ikut aturan yang sama),
     * dan backup Drive lebih tua dari drive retention.
     *
     * @return array{local: int, drive: int}
     */
    public function prune(): array
    {
        $deleted = ['local' => 0, 'drive' => 0];
        $days = (int) config('database_backup.retention_days', 14);

        if ($days > 0) {
            $cutoff = now()->subDays($days)->timestamp;
            foreach ($this->localFiles() as $file) {
                if ($file['modified'] < $cutoff) {
                    File::delete($file['path']);
                    $deleted['local']++;
                }
            }
        }

        if ($this->drive->isConnected()) {
            try {
                $deleted['drive'] = $this->drive->prune((int) config('database_backup.drive_retention_days', 60));
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $deleted;
    }

    /**
     * @return array<int, array{filename: string, path: string, type: string, size_bytes: int, modified: int}>
     */
    public function localFiles(): array
    {
        return collect(File::files($this->directory()))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), self::EXTENSIONS, true) && ! str_starts_with($file->getFilename(), '.'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(fn ($file) => [
                'filename' => $file->getFilename(),
                'path' => $file->getPathname(),
                'type' => self::typeOf($file->getFilename()),
                'size_bytes' => $file->getSize(),
                'modified' => $file->getMTime(),
            ])
            ->values()
            ->all();
    }

    public function localPath(string $filename): string
    {
        return $this->directory().DIRECTORY_SEPARATOR.basename($filename);
    }

    /** 'database' | 'files' | null */
    public static function typeOf(string $filename): ?string
    {
        return match (true) {
            (bool) preg_match('/\.sql(\.gz)?$/i', $filename) => 'database',
            (bool) preg_match('/\.zip$/i', $filename) => 'files',
            default => null,
        };
    }

    /**
     * @return array{at: ?string, ok: ?bool, message: ?string, files: array, drive: array}
     */
    public function lastStatus(): array
    {
        $status = json_decode((string) Setting::get('backup_last_status'), true);

        return is_array($status) ? $status + ['files' => [], 'drive' => []] : ['at' => null, 'ok' => null, 'message' => null, 'files' => [], 'drive' => []];
    }

    private function recordStatus(bool $ok, ?string $message, array $result): void
    {
        Setting::set('backup_last_status', json_encode([
            'at' => now()->toDateTimeString(),
            'ok' => $ok,
            'message' => $message,
            'files' => array_map('basename', $result['files']),
            'drive' => $result['drive'],
        ]));
    }

    private function dumpDatabase(string $stamp): string
    {
        if (config('database.default') !== 'mysql') {
            throw new RuntimeException('Backup hanya mendukung koneksi mysql. Koneksi aktif: '.config('database.default'));
        }

        $connection = config('database.connections.mysql');
        $database = (string) ($connection['database'] ?? '');
        if ($database === '') {
            throw new RuntimeException('Nama database tidak ditemukan di konfigurasi.');
        }

        $sqlPath = $this->directory().DIRECTORY_SEPARATOR.$stamp.'_'.$this->safeFilename($database).'.sql';
        $process = new Process([
            (string) config('database_backup.mysqldump_path', 'mysqldump'),
            ...$this->connectionArguments($connection),
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--no-tablespaces',
            // Sesi login & cache tidak ikut: restore tidak memutus login yang sedang aktif.
            ...array_map(fn ($table) => '--ignore-table='.$database.'.'.$table, ['sessions', 'cache', 'cache_locks']),
            '--databases',
            $database,
            '--result-file='.$sqlPath,
        ], null, $this->passwordEnvironment($connection));
        $process->setTimeout((int) config('database_backup.timeout', 300));
        $process->run();

        if (! $process->isSuccessful() || ! File::exists($sqlPath) || File::size($sqlPath) < 1) {
            File::delete($sqlPath);
            throw new RuntimeException('Backup database gagal: '.trim($process->getErrorOutput() ?: $process->getOutput() ?: 'file kosong.'));
        }

        $gzPath = $sqlPath.'.gz';
        $this->gzip($sqlPath, $gzPath);
        File::delete($sqlPath);

        return $gzPath;
    }

    private function zipFiles(string $stamp): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP zip tidak tersedia; backup file unggahan dilewati.');
        }

        $source = (string) config('database_backup.files_path');
        $zipPath = $this->directory().DIRECTORY_SEPARATOR.$stamp.'_file-unggahan.zip';
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat arsip file unggahan.');
        }

        if (File::isDirectory($source)) {
            foreach (File::allFiles($source) as $file) {
                $zip->addFile($file->getPathname(), str_replace('\\', '/', $file->getRelativePathname()));
            }
        }
        // Arsip kosong tetap dibuat supaya jelas backup file berjalan.
        if ($zip->numFiles === 0) {
            $zip->addFromString('.kosong', 'Tidak ada file unggahan saat backup.');
        }
        $zip->close();

        return $zipPath;
    }

    private function gzip(string $source, string $destination): void
    {
        $in = fopen($source, 'rb');
        $out = gzopen($destination, 'wb6');
        while (! feof($in)) {
            gzwrite($out, (string) fread($in, 1024 * 1024));
        }
        fclose($in);
        gzclose($out);
    }

    private function gunzip(string $source, string $destination): void
    {
        $in = gzopen($source, 'rb');
        if (! $in) {
            throw new RuntimeException('File .gz tidak bisa dibuka.');
        }
        $out = fopen($destination, 'wb');
        while (! gzeof($in)) {
            fwrite($out, (string) gzread($in, 1024 * 1024));
        }
        gzclose($in);
        fclose($out);
    }

    /** Host/port/user; password lewat env MYSQL_PWD supaya tidak terlihat di daftar proses. */
    private function connectionArguments(array $connection): array
    {
        $arguments = [
            '--host='.($connection['host'] ?? '127.0.0.1'),
            '--port='.($connection['port'] ?? '3306'),
        ];
        if (filled($connection['username'] ?? null)) {
            $arguments[] = '--user='.$connection['username'];
        }

        return $arguments;
    }

    private function passwordEnvironment(array $connection): array
    {
        return filled($connection['password'] ?? null) ? ['MYSQL_PWD' => (string) $connection['password']] : [];
    }

    private function assertInsideDirectory(string $path): void
    {
        $real = realpath($path);
        if ($real === false || ! str_starts_with($real, realpath($this->directory()).DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('File backup tidak ditemukan di folder backup.');
        }
    }

    private function safeFilename(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_\-]/', '_', $value) ?: 'database';
    }
}
