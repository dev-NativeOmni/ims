<?php

namespace App\Http\Controllers;

use App\Services\Backup\BackupManager;
use App\Services\Backup\GoogleDriveBackup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Backup & Restore: backup manual, unduh/hapus, salinan Google Drive, dan pemulihan
 * database / file unggahan. Restore & koneksi Google Drive khusus super admin.
 */
class DatabaseBackupController extends Controller
{
    public const RESTORE_CONFIRMATION = 'PULIHKAN';

    public function __construct(
        private readonly BackupManager $backups,
        private readonly GoogleDriveBackup $drive,
    ) {}

    public function index(Request $request): View
    {
        $driveFiles = [];
        $driveError = null;
        if ($this->drive->isConnected()) {
            try {
                $driveFiles = $this->drive->files();
            } catch (Throwable $e) {
                $driveError = $e->getMessage();
            }
        }

        $local = $this->backups->localFiles();

        return view('database-backups.index', [
            'backups' => $local,
            'latestBackup' => $local[0] ?? null,
            'lastStatus' => $this->backups->lastStatus(),
            'backupPath' => $this->backups->directory(),
            'retentionDays' => (int) config('database_backup.retention_days', 14),
            'driveRetentionDays' => (int) config('database_backup.drive_retention_days', 60),
            'driveConfigured' => $this->drive->isConfigured(),
            'driveConnected' => $this->drive->isConnected(),
            'driveEmail' => $this->drive->accountEmail(),
            'driveFiles' => $driveFiles,
            'driveError' => $driveError,
            'driveRedirectUri' => route('database-backups.drive.callback'),
            'canRestore' => $request->user()->hasRole('super_admin'),
            'confirmation' => self::RESTORE_CONFIRMATION,
            'maxUpload' => ini_get('upload_max_filesize'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        @set_time_limit(900);

        try {
            $result = $this->backups->backup($request->boolean('with_files'));
            $this->backups->prune();
        } catch (Throwable $e) {
            return back()->with('error', 'Backup gagal: '.$e->getMessage());
        }

        $message = 'Backup berhasil dibuat ('.implode(', ', array_map('basename', $result['files'])).').';
        if ($result['drive'] !== []) {
            $message .= ' Tersalin ke Google Drive.';
        }

        return back()
            ->with('success', $message)
            ->with('error', $result['drive_error'] ? 'Gagal mengirim ke Google Drive: '.$result['drive_error'] : null);
    }

    public function download(string $filename): BinaryFileResponse
    {
        $path = $this->backups->localPath($filename);
        abort_unless(File::exists($path) && BackupManager::typeOf($path) !== null, 404);

        return response()->download($path, basename($path));
    }

    public function destroy(string $filename): RedirectResponse
    {
        $path = $this->backups->localPath($filename);
        abort_unless(File::exists($path) && BackupManager::typeOf($path) !== null, 404);

        File::delete($path);

        return back()->with('success', 'File backup berhasil dihapus dari server.');
    }

    /**
     * Pulihkan dari file di server, file di Google Drive, atau file yang diunggah.
     */
    public function restore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'in:local,drive,upload'],
            'filename' => ['required_if:source,local', 'nullable', 'string'],
            'drive_id' => ['required_if:source,drive', 'nullable', 'string'],
            'drive_name' => ['required_if:source,drive', 'nullable', 'string'],
            'backup_file' => ['required_if:source,upload', 'nullable', 'file'],
            'confirmation' => ['required', 'in:'.self::RESTORE_CONFIRMATION],
            'password' => ['required', 'string'],
        ], [
            'confirmation.in' => 'Ketik '.self::RESTORE_CONFIRMATION.' (huruf besar) untuk melanjutkan.',
        ]);

        if (! Hash::check($validated['password'], (string) $request->user()->password)) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        @set_time_limit(1800);

        try {
            $path = $this->resolveRestoreSource($request, $validated);
            $type = BackupManager::typeOf($path);

            if ($type === 'database') {
                $result = $this->backups->restoreDatabase($path);
                $message = 'Database berhasil dipulihkan dari '.basename($path).'. Backup pengaman sebelum restore: '.$result['safety_backup'].'.';
            } elseif ($type === 'files') {
                $count = $this->backups->restoreFiles($path);
                $message = 'File unggahan berhasil dipulihkan dari '.basename($path)." ({$count} file).";
            } else {
                return back()->with('error', 'Jenis file tidak dikenali. Gunakan .sql, .sql.gz, atau .zip hasil backup.');
            }
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Restore gagal: '.$e->getMessage());
        }

        return back()->with('success', $message);
    }

    public function sendToDrive(string $filename): RedirectResponse
    {
        $path = $this->backups->localPath($filename);
        abort_unless(File::exists($path) && BackupManager::typeOf($path) !== null, 404);

        try {
            $this->drive->upload($path);
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal mengirim ke Google Drive: '.$e->getMessage());
        }

        return back()->with('success', basename($path).' tersalin ke Google Drive.');
    }

    public function connectDrive(Request $request): RedirectResponse
    {
        abort_unless($this->drive->isConfigured(), 404);

        $state = Str::random(40);
        $request->session()->put('backup_gdrive_state', $state);

        return redirect()->away($this->drive->authorizationUrl(route('database-backups.drive.callback'), $state));
    }

    public function driveCallback(Request $request): RedirectResponse
    {
        $expected = $request->session()->pull('backup_gdrive_state');
        if (! $expected || ! hash_equals($expected, (string) $request->query('state'))) {
            return redirect()->route('database-backups.index')->with('error', 'Sesi koneksi Google Drive tidak valid. Coba hubungkan lagi.');
        }

        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect()->route('database-backups.index')->with('error', 'Koneksi Google Drive dibatalkan.');
        }

        try {
            $this->drive->connect((string) $request->query('code'), route('database-backups.drive.callback'));
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('database-backups.index')->with('error', 'Gagal menghubungkan Google Drive: '.$e->getMessage());
        }

        return redirect()->route('database-backups.index')->with('success', 'Google Drive terhubung. Backup berikutnya otomatis disalin ke Drive.');
    }

    public function disconnectDrive(): RedirectResponse
    {
        $this->drive->disconnect();

        return back()->with('success', 'Google Drive diputus. Backup tetap tersimpan di server.');
    }

    /**
     * Path lokal file yang akan dipulihkan (file Drive/unggahan disalin ke folder backup dulu).
     */
    private function resolveRestoreSource(Request $request, array $validated): string
    {
        $directory = $this->backups->directory();

        return match ($validated['source']) {
            'local' => $this->backups->localPath($validated['filename']),
            'drive' => tap($directory.DIRECTORY_SEPARATOR.'dari-drive_'.basename($validated['drive_name']), function ($path) use ($validated) {
                $this->drive->download($validated['drive_id'], $path);
            }),
            'upload' => tap($directory.DIRECTORY_SEPARATOR.'unggahan_'.now()->format('Y-m-d_His').'_'.preg_replace('/[^A-Za-z0-9_.\-]/', '_', $request->file('backup_file')->getClientOriginalName()), function ($path) use ($request) {
                $request->file('backup_file')->move(dirname($path), basename($path));
            }),
        };
    }
}
