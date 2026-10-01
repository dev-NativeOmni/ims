<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\StudentReport;
use App\Support\AcademicYear;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Hapus data rapor tahun ajaran sebelum AcademicYear::FIRST (mis. 2025/2026): catatan rapor
 * (student_reports) dan tanggal BLP tahun itu; tahun ajaran aktif yang lama diganti FIRST.
 * Data setoran/presensi/dll. tidak disentuh (tidak bertahun ajaran).
 */
class DeleteOldAcademicYears extends Command
{
    protected $signature = 'tad:hapus-tahun-ajaran-lama
        {--dry-run : Tampilkan yang akan dihapus tanpa menghapus}
        {--force : Jalankan tanpa pertanyaan konfirmasi}';

    protected $description = 'Hapus catatan rapor & tanggal BLP tahun ajaran sebelum '.AcademicYear::FIRST.'.';

    public function handle(): int
    {
        $oldReports = StudentReport::query()->get(['id', 'academic_year', 'teacher_notes', 'locked_at'])
            ->reject(fn ($report) => AcademicYear::isValid($report->academic_year));
        $oldBlpKeys = Setting::query()->where('key', 'like', 'report_blp_dates_%')->pluck('key')
            ->reject(fn ($key) => AcademicYear::isValid(str_replace('-', '/', substr($key, strlen('report_blp_dates_')))));
        $activeYear = (string) Setting::query()->where('key', 'academic_year')->value('value');
        $resetActive = $activeYear !== '' && ! AcademicYear::isValid($activeYear);

        $rows = $oldReports->groupBy('academic_year')->map(fn ($reports, $year) => [
            "Catatan rapor {$year}",
            $reports->count().' baris ('.$reports->filter(fn ($r) => filled($r->teacher_notes))->count().' berisi catatan wali kelas, '
                .$reports->whereNotNull('locked_at')->count().' terkunci)',
        ])->values()->all();
        foreach ($oldBlpKeys as $key) {
            $rows[] = ['Tanggal BLP', $key];
        }
        if ($resetActive) {
            $rows[] = ['Tahun ajaran aktif', "{$activeYear} -> ".AcademicYear::FIRST];
        }

        if ($rows === []) {
            $this->info('Tidak ada data tahun ajaran sebelum '.AcademicYear::FIRST.'.');

            return self::SUCCESS;
        }

        $this->table(['Data', 'Akan dihapus/diubah'], $rows);

        if ($this->option('dry-run')) {
            $this->warn('Mode uji coba: tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Hapus data di atas secara permanen?')) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        $deleted = StudentReport::query()->whereIn('id', $oldReports->pluck('id'))->delete();
        foreach ($oldBlpKeys as $key) {
            Setting::query()->where('key', $key)->delete();
            Cache::forget("setting:{$key}");
        }
        if ($resetActive) {
            Setting::set('academic_year', AcademicYear::FIRST);
        }

        $this->info("{$deleted} catatan rapor dan {$oldBlpKeys->count()} pengaturan tanggal BLP lama dihapus.");

        return self::SUCCESS;
    }
}
