<?php

namespace App\Console\Commands;

use App\Http\Controllers\StudentReportController;
use App\Models\ClassRoom;
use App\Models\HafalanRecordSurah;
use App\Models\Student;
use App\Services\HafalanProgressService;
use App\Support\AcademicYear;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Penyesuaian baris rapor Program Reguler Kelas 11 & 12 (keputusan sekolah, Oktober 2026): murid yang
 * Tidak Tuntas karena baris, padahal Capaian Akhir sudah sampai/melewati target surah:ayat guru, dibuat
 * Tuntas dengan MENAMBAH baris (isian Baris manual) setoran pertama bulan pertama triwulan yang ayatnya
 * baru, sebanyak kekurangannya. Surah, ayat & target tidak diubah. Kelas Program Tahfizh tidak disentuh.
 * Bawaan hanya menampilkan; --simpan mencadangkan baris lama ke storage/app/backups/penyesuaian-baris
 * lalu menyimpan; --pulihkan=FILE mengembalikan baris lama.
 */
class PenyesuaianBarisCommand extends Command
{
    protected $signature = 'tad:penyesuaian-baris
        {--tahun= : Tahun ajaran, mis. 2026/2027 (bawaan: tahun ajaran aktif)}
        {--term= : Periode rapor 1-4 (bawaan: periode rapor aktif)}
        {--kelas= : Hanya kelas ini, mis. "XI F2"}
        {--murid=* : Hanya murid ini (nama, boleh sebagian; bisa diulang)}
        {--simpan : Simpan penyesuaian (cadangan baris lama dibuat otomatis)}
        {--force : Simpan/pulihkan tanpa pertanyaan konfirmasi}
        {--pulihkan= : Kembalikan baris lama dari file cadangan (nama file dari --simpan)}';

    protected $description = 'Program Reguler 11/12: tuntaskan murid yang posisinya sudah sampai target dengan menambah baris setoran pertama triwulan.';

    public function handle(StudentReportController $reports, HafalanProgressService $progress): int
    {
        if ($this->option('pulihkan')) {
            return $this->restore((string) $this->option('pulihkan'));
        }

        $year = AcademicYear::isValid($this->option('tahun')) ? $this->option('tahun') : AcademicYear::active();
        $term = isset(StudentReportController::REPORT_PERIODS[(int) $this->option('term')]) ? (int) $this->option('term') : StudentReportController::activePeriod();
        $period = StudentReportController::resolveTanseTerm($year, StudentReportController::semesterOfPeriod($term), $term);
        $classDate = StudentReportController::classDateFor($year, $term);
        $names = array_filter(array_map('trim', (array) $this->option('murid')));
        $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');

        $classes = ClassRoom::query()->with('program')->orderBy('name')->get()
            ->filter(fn (ClassRoom $c) => $c->isGradeElevenOrTwelve() && ! $c->isTahfizhProgram())
            ->when($this->option('kelas'), fn ($cs) => $cs->filter(fn ($c) => strcasecmp(trim($c->name), trim($this->option('kelas'))) === 0));

        $plans = [];
        $rows = [];
        $skipped = [];
        foreach ($classes as $class) {
            $students = Student::query()->inClassOn($class->id, $classDate)->where('status', 'active')->orderBy('name')->get()
                ->each(fn (Student $s) => $s->setRelation('classRoom', $class))
                ->filter(fn (Student $s) => $names === [] || collect($names)->contains(fn ($n) => stripos($s->name, $n) !== false));

            foreach ($students as $student) {
                ['sheet' => $sheet, 'locked_at' => $lockedAt] = $reports->sheetFor($student, $year, $term);
                $t = $sheet['tahfizh'] ?? [];
                if (($t['layout'] ?? null) !== 'reguler' || ($t['completed'] ?? true) || empty($t['lines'])) {
                    continue;
                }
                $position = ($t['target']['surah'] ?? '-').' '.($t['target']['ayat'] ?? '-').' / '.($t['capaian']['surah'] ?? '-').' '.($t['capaian']['ayat'] ?? '-');
                if ($lockedAt) {
                    $skipped[] = [$class->name, $student->name, $position, 'rapor sudah dikunci'];

                    continue;
                }
                if (! ($t['target_reached'] ?? false)) {
                    $skipped[] = [$class->name, $student->name, $position, 'capaian akhir belum sampai target surah'];

                    continue;
                }

                // Setoran pertama triwulan yang seluruh ayatnya baru (baris manual dihitung penuh), utamakan bulan pertama.
                $details = collect($progress->passedLineDetails($progress->records($student), $period['start'], now()->min($period['end'])))
                    ->filter(fn ($d) => $d['kind'] === 'new' && $d['record']->line_id);
                $firstMonth = $period['start']->format('Y-m');
                $pick = $details->first(fn ($d) => Carbon::parse($d['record']->submitted_at)->format('Y-m') === $firstMonth) ?? $details->first();
                if (! $pick) {
                    $skipped[] = [$class->name, $student->name, $position, 'tidak ada setoran ayat baru di triwulan ini'];

                    continue;
                }

                $achieved = $progress->passedLines($progress->records($student), $period['start'], now()->min($period['end']));
                $shortfall = ceil(round(((float) $t['lines']['target'] - $achieved) * 10, 6)) / 10;
                $newBaris = round($pick['baris'] + $shortfall, 1);
                $r = $pick['record'];
                $plans[] = ['line_id' => (int) $r->line_id, 'student' => $student->name, 'old' => HafalanRecordSurah::query()->whereKey($r->line_id)->value('baris'), 'new' => $newBaris];
                $rows[] = [
                    $class->name,
                    $student->name,
                    $position,
                    $fmt($achieved).' / '.$t['lines']['target'],
                    $fmt($shortfall),
                    Carbon::parse($r->submitted_at)->format('d/m/Y').' '.($progress->surahs()->get((int) $r->surah_number)?->name_latin ?? $r->surah_number)." {$r->ayah_start}-{$r->ayah_end}",
                    $fmt($pick['baris']).' -> '.$fmt($newBaris),
                ];
            }
        }

        $this->info('Penyesuaian baris Program Reguler Kelas 11 & 12 · '.StudentReportController::REPORT_PERIODS[$term]." {$year}");
        if ($rows !== []) {
            $this->table(['Kelas', 'Murid', 'Target / Capaian akhir', 'Baris sekarang', 'Kurang', 'Setoran yang ditambah barisnya', 'Baris setoran'], $rows);
        }
        if ($skipped !== []) {
            $this->line('Tidak diubah (tetap Tidak Tuntas):');
            $this->table(['Kelas', 'Murid', 'Target / Capaian akhir', 'Alasan'], $skipped);
        }
        if ($plans === []) {
            $this->info('Tidak ada murid yang perlu disesuaikan.');

            return self::SUCCESS;
        }
        $this->line(count($plans).' murid akan menjadi Tuntas. Surah, ayat & target tidak berubah; hanya isian Baris setoran di atas.');

        if (! $this->option('simpan')) {
            $this->warn('Mode tampil saja: belum ada yang disimpan. Tambahkan --simpan untuk menyimpan.');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm('Simpan penyesuaian baris untuk '.count($plans).' murid?')) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        $backup = $this->backupDirectory().'/penyesuaian_baris_'.now()->format('Y-m-d_His').'.json';
        File::put($backup, json_encode($plans, JSON_PRETTY_PRINT));
        DB::transaction(function () use ($plans) {
            foreach ($plans as $plan) {
                HafalanRecordSurah::query()->whereKey($plan['line_id'])->first()?->update(['baris' => $plan['new']]);
            }
        });
        $this->info('Tersimpan untuk '.count($plans).' murid. Cadangan: '.$backup);
        $this->line('Untuk membatalkan: php artisan tad:penyesuaian-baris --pulihkan='.basename($backup));

        return self::SUCCESS;
    }

    private function backupDirectory(): string
    {
        $directory = storage_path('app/backups/penyesuaian-baris');
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    /** Kembalikan isian Baris setoran ke nilai sebelum penyesuaian. */
    private function restore(string $filename): int
    {
        $path = $this->backupDirectory().'/'.basename($filename);
        $plans = File::exists($path) ? json_decode(File::get($path), true) : null;
        if (! is_array($plans)) {
            $this->error('File cadangan tidak ditemukan/rusak: '.$path);

            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm('Kembalikan baris '.count($plans).' setoran ke nilai sebelum penyesuaian?')) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($plans) {
            foreach ($plans as $plan) {
                HafalanRecordSurah::query()->whereKey($plan['line_id'])->first()?->update(['baris' => $plan['old']]);
            }
        });
        $this->info('Dipulihkan: '.count($plans).' setoran.');

        return self::SUCCESS;
    }
}
