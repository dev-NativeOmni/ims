<?php

namespace App\Console\Commands;

use App\Http\Controllers\StudentReportController;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Services\AcademicCalendarService;
use App\Services\HafalanProgressService;
use App\Support\AcademicYear;
use App\Support\AyahCoverage;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Setoran susulan Program Reguler Kelas 11 & 12 (keputusan sekolah, Oktober 2026): murid yang sudah
 * menyetor sampai target surah:ayat guru tetapi setorannya belum dicatat guru. Ayat yang belum tercatat
 * antara Capaian Akhir rapor dan target (urutan hafalan murid, ayat yang sudah lulus/hafalan lama
 * dilewati) dicatat sebagai SATU sesi setoran lulus di pertemuan aktif terakhir triwulan, dengan
 * catatan "Setoran susulan ... dicatat admin". Bila baris masih kurang, lanjutkan dengan
 * tad:penyesuaian-baris. Kelas Program Tahfizh & rapor terkunci tidak disentuh. Bawaan hanya
 * menampilkan; --simpan menyimpan (daftar sesi dicadangkan); --pulihkan=FILE menghapus sesi susulan itu.
 */
class SetoranSusulanCommand extends Command
{
    protected $signature = 'tad:setoran-susulan
        {--tahun= : Tahun ajaran, mis. 2026/2027 (bawaan: tahun ajaran aktif)}
        {--term= : Periode rapor 1-4 (bawaan: periode rapor aktif)}
        {--kelas= : Hanya kelas ini, mis. "XII F2"}
        {--murid=* : Hanya murid ini (nama, boleh sebagian; bisa diulang)}
        {--simpan : Simpan setoran susulan}
        {--force : Simpan/pulihkan tanpa pertanyaan konfirmasi}
        {--pulihkan= : Hapus setoran susulan yang dibuat (nama file cadangan dari --simpan)}';

    protected $description = 'Program Reguler 11/12: catat setoran susulan dari Capaian Akhir sampai target surah:ayat guru.';

    public function handle(StudentReportController $reports, HafalanProgressService $progress, AcademicCalendarService $calendar): int
    {
        if ($this->option('pulihkan')) {
            return $this->restore((string) $this->option('pulihkan'));
        }

        $year = AcademicYear::isValid($this->option('tahun')) ? $this->option('tahun') : AcademicYear::active();
        $term = isset(StudentReportController::REPORT_PERIODS[(int) $this->option('term')]) ? (int) $this->option('term') : StudentReportController::activePeriod();
        [$start, $end] = AcademicYear::termRange($year, $term);
        $classDate = StudentReportController::classDateFor($year, $term);
        $names = array_filter(array_map('trim', (array) $this->option('murid')));
        $surahs = $progress->surahs();
        $label = StudentReportController::REPORT_PERIODS[$term]." {$year}";

        $classes = ClassRoom::query()->with('program')->orderBy('name')->get()
            ->filter(fn (ClassRoom $c) => $c->isGradeElevenOrTwelve() && ! $c->isTahfizhProgram())
            ->when($this->option('kelas'), fn ($cs) => $cs->filter(fn ($c) => strcasecmp(trim($c->name), trim($this->option('kelas'))) === 0));

        $plans = [];
        $rows = [];
        foreach ($classes as $class) {
            $students = Student::query()->inClassOn($class->id, $classDate)->where('status', 'active')->orderBy('name')->get()
                ->each(fn (Student $s) => $s->setRelation('classRoom', $class))
                ->filter(fn (Student $s) => $names === [] || collect($names)->contains(fn ($n) => stripos($s->name, $n) !== false));

            foreach ($students as $student) {
                ['sheet' => $sheet, 'locked_at' => $lockedAt] = $reports->sheetFor($student, $year, $term);
                $t = $sheet['tahfizh'] ?? [];
                if ($lockedAt || ($t['layout'] ?? null) !== 'reguler' || ($t['completed'] ?? true) || ($t['target_reached'] ?? true)) {
                    continue;
                }

                $records = $progress->records($student);
                $targets = HafalanTarget::with('surah')->where('student_id', $student->id)
                    ->whereBetween('target_date', [$start->toDateString(), $end->copy()->endOfDay()])->get()->filter(fn ($x) => $x->surah);
                $target = $progress->termBreakdown($student, $targets, $calendar->termMonths($start), now()->min($end->copy()->endOfDay()), $records)['target'];
                if (! $target) {
                    continue;
                }

                $ranges = $this->missingRanges($student, $records, (int) $target->surah->number, (int) $target->ayah, $end);
                if ($ranges === []) {
                    continue;
                }
                $date = ($calendar->lastMeetingDate($class, $start, $end) ?? $end)->toDateString();
                $plans[] = ['student_id' => $student->id, 'teacher_id' => $student->teacher_id, 'date' => $date, 'ranges' => $ranges];
                $rows[] = [
                    $class->name,
                    $student->name,
                    ($t['target']['surah'] ?? '-').' '.($t['target']['ayat'] ?? '-').' / '.($t['capaian']['surah'] ?? '-').' '.($t['capaian']['ayat'] ?? '-'),
                    Carbon::parse($date)->format('d/m/Y'),
                    collect($ranges)->map(fn ($r) => ($surahs->get($r[0])?->name_latin ?? $r[0])." {$r[1]}-{$r[2]}")->implode('; '),
                ];
            }
        }

        $this->info("Setoran susulan Program Reguler Kelas 11 & 12 · {$label}");
        if ($plans === []) {
            $this->info('Tidak ada murid yang perlu setoran susulan.');

            return self::SUCCESS;
        }
        $this->table(['Kelas', 'Murid', 'Target / Capaian akhir', 'Tanggal susulan', 'Ayat yang dicatat susulan'], $rows);
        $this->line(count($plans).' murid. Setelah disimpan, jalankan tad:penyesuaian-baris bila baris masih kurang dari target.');

        if (! $this->option('simpan')) {
            $this->warn('Mode tampil saja: belum ada yang disimpan. Tambahkan --simpan untuk menyimpan.');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm('Catat setoran susulan untuk '.count($plans).' murid?')) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        $created = [];
        $surahIds = $surahs->map->id;
        DB::transaction(function () use ($plans, $surahIds, $label, &$created) {
            foreach ($plans as $plan) {
                $header = HafalanRecord::create([
                    'student_id' => $plan['student_id'],
                    'teacher_id' => $plan['teacher_id'],
                    'submitted_at' => $plan['date'],
                    'notes' => "Setoran susulan {$label}: sudah disetor, belum tercatat guru (dicatat admin ".now()->format('d/m/Y').').',
                ]);
                foreach ($plan['ranges'] as $i => [$surah, $from, $to]) {
                    $header->surahs()->create([
                        'surah_id' => $surahIds[$surah], 'ayah_start' => $from, 'ayah_end' => $to,
                        'status' => 'passed', 'submission_type' => 'new', 'sort_order' => $i,
                    ]);
                }
                $created[] = $header->id;
            }
        });

        $backup = $this->backupDirectory().'/setoran_susulan_'.now()->format('Y-m-d_His').'.json';
        File::put($backup, json_encode(['hafalan_record_ids' => $created, 'plans' => $plans], JSON_PRETTY_PRINT));
        $this->info('Tercatat untuk '.count($created).' murid. Daftar sesi: '.$backup);
        $this->line('Untuk membatalkan: php artisan tad:setoran-susulan --pulihkan='.basename($backup));

        return self::SUCCESS;
    }

    /**
     * Ayat yang belum tercatat lulus (setoran/hafalan lama sampai akhir triwulan) dari sesudah Capaian Akhir
     * sampai target, menurut urutan hafalan murid, digabung per surah: [[surah, ayat awal, ayat akhir], ...].
     */
    private function missingRanges(Student $student, $records, int $targetSurah, int $targetAyah, Carbon $end): array
    {
        $progress = app(HafalanProgressService::class);
        $orders = $progress->juzOrders($student, $records);
        $direction = $student->hafalan_direction;
        $rank = fn (int $s, int $a) => HafalanOrder::rank($s, $a, $direction, $orders);

        $passed = $records->filter(fn ($r) => ! $r->is_prior && $r->status === 'passed' && Carbon::parse($r->submitted_at)->lte($end->copy()->endOfDay()));
        $capRank = $passed->map(fn ($r) => $rank((int) $r->surah_number, (int) $r->ayah_end))->max() ?? 0;
        $targetRank = $rank($targetSurah, $targetAyah);
        if ($targetRank <= $capRank) {
            return [];
        }
        $covered = $progress->coverage($records, null, $end);

        $ayat = [];
        $fromJuz = HafalanOrder::juzPosition(HafalanOrder::juzOf(...$this->rankPosition($passed, $rank)), $direction);
        foreach (array_slice(HafalanOrder::juzSequence($direction), $fromJuz) as $juz) {
            foreach (HafalanOrder::juzPieces($juz, $orders[$juz] ?? HafalanOrder::defaultJuzOrder($juz)) as [$surah, $from, $to]) {
                for ($a = $from; $a <= $to; $a++) {
                    $r = $rank($surah, $a);
                    if ($r > $capRank && $r <= $targetRank && AyahCoverage::uncovered($covered[$surah] ?? [], $a, $a) !== []) {
                        $ayat[$r] = [$surah, $a];
                    }
                }
            }
            if (HafalanOrder::juzPosition($juz, $direction) >= HafalanOrder::juzPosition(HafalanOrder::juzOf($targetSurah, $targetAyah), $direction)) {
                break;
            }
        }
        ksort($ayat);

        $ranges = [];
        foreach ($ayat as [$surah, $a]) {
            $last = count($ranges) - 1;
            if ($last >= 0 && $ranges[$last][0] === $surah && $ranges[$last][2] === $a - 1) {
                $ranges[$last][2] = $a;
            } else {
                $ranges[] = [$surah, $a, $a];
            }
        }

        return $ranges;
    }

    /** Posisi [surah, ayat] setoran lulus terjauh (Capaian Akhir), atau titik awal Juz 30 bila belum ada. */
    private function rankPosition($passed, callable $rank): array
    {
        $furthest = $passed->sortByDesc(fn ($r) => $rank((int) $r->surah_number, (int) $r->ayah_end))->first();

        return $furthest ? [(int) $furthest->surah_number, (int) $furthest->ayah_end] : [78, 1];
    }

    private function backupDirectory(): string
    {
        $directory = storage_path('app/backups/setoran-susulan');
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    /** Hapus sesi setoran susulan yang dibuat oleh --simpan. */
    private function restore(string $filename): int
    {
        $path = $this->backupDirectory().'/'.basename($filename);
        $data = File::exists($path) ? json_decode(File::get($path), true) : null;
        if (! is_array($data) || ! isset($data['hafalan_record_ids'])) {
            $this->error('File cadangan tidak ditemukan/rusak: '.$path);

            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm('Hapus '.count($data['hafalan_record_ids']).' sesi setoran susulan?')) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($data) {
            HafalanRecord::query()->whereIn('id', $data['hafalan_record_ids'])->get()->each(function (HafalanRecord $record) {
                $record->surahs()->delete();
                $record->delete();
            });
        });
        $this->info('Dihapus: '.count($data['hafalan_record_ids']).' sesi setoran susulan.');

        return self::SUCCESS;
    }
}
