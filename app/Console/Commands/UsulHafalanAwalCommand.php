<?php

namespace App\Console\Commands;

use App\Models\ClassRoom;
use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\StudentPriorHafalan;
use App\Services\AcademicCalendarService;
use App\Services\HafalanProgressService;
use App\Support\AcademicYear;
use App\Support\AyahCoverage;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Usulan "Hafalan Sebelum Aplikasi" (StudentPriorHafalan) untuk murid Kelas 11 & 12 yang setoran
 * pertamanya di triwulan dimulai di TENGAH juz (mis. An-Nahl 25): bagian juz itu sebelum titik awal
 * (menurut urutan juz murid; deteksi dari setoran tidak dipakai karena bisa terpengaruh setoran
 * "memutar") yang belum tercatat diusulkan sebagai hafalan lama. --juz-sebelumnya juga mengusulkan
 * juz-juz sebelum juz itu dalam urutan hafalan murid. Bawaan hanya menampilkan; --simpan menyimpan.
 */
class UsulHafalanAwalCommand extends Command
{
    protected $signature = 'tad:usul-hafalan-awal
        {--tahun= : Tahun ajaran, mis. 2026/2027 (bawaan: tahun ajaran berjalan)}
        {--term= : Triwulan acuan 1-4 (bawaan: triwulan berjalan)}
        {--kelas= : Hanya kelas ini, mis. "XI F1"}
        {--murid=* : Hanya murid ini (nama, boleh sebagian; bisa diulang)}
        {--juz-sebelumnya : Usulkan juga juz-juz sebelum juz awal (dalam urutan hafalan murid) sebagai hafal penuh}
        {--simpan : Simpan usulan sebagai Hafalan Sebelum Aplikasi}
        {--force : Simpan tanpa pertanyaan konfirmasi}';

    protected $description = 'Usulan hafalan sebelum aplikasi untuk murid Kelas 11 & 12 yang mulai di tengah juz.';

    public function handle(HafalanProgressService $progress, AcademicCalendarService $calendar): int
    {
        $year = AcademicYear::isValid($this->option('tahun')) ? $this->option('tahun') : AcademicYear::forDate(now());
        $term = in_array((int) $this->option('term'), [1, 2, 3, 4], true) ? (int) $this->option('term') : AcademicYear::termOf(now());
        [$start, $end] = AcademicYear::termRange($year, $term);
        $cutoff = now()->min($end->copy()->endOfDay());
        $months = $calendar->termMonths($start);
        $names = array_filter(array_map('trim', (array) $this->option('murid')));

        $surahs = $progress->surahs();
        $proposals = [];
        $rows = [];

        $classes = ClassRoom::query()->with('program')->orderBy('name')->get()
            ->filter(fn (ClassRoom $c) => $c->isGradeElevenOrTwelve())
            ->when($this->option('kelas'), fn ($cs) => $cs->filter(fn ($c) => strcasecmp(trim($c->name), trim($this->option('kelas'))) === 0));

        foreach ($classes as $class) {
            $students = Student::query()->inClassOn($class->id, $cutoff)->where('status', 'active')->orderBy('name')->get()
                ->each(fn (Student $s) => $s->setRelation('classRoom', $class))
                ->filter(fn (Student $s) => $names === [] || collect($names)->contains(fn ($n) => stripos($s->name, $n) !== false));

            foreach ($students as $student) {
                if (! HafalanProgressService::juz30FromNaba($student)) {
                    continue;
                }
                $records = $progress->records($student);
                $first = $records->first(fn ($r) => ! $r->is_prior && $r->status === 'passed'
                    && Carbon::parse($r->submitted_at)->betweenIncluded($start->copy()->startOfDay(), $end->copy()->endOfDay()));
                if (! $first) {
                    continue;
                }

                $ranges = $this->proposalFor($student, (int) $first->surah_number, (int) $first->ayah_start, $progress->coverage($records, $start));
                if ($ranges === []) {
                    continue;
                }

                $target = (int) $progress->termBreakdown($student, $this->termTargets($student, $start, $end), $months, $cutoff, $records)['evaluation']['target_lines'];
                $before = $progress->passedLines($records, $start, $cutoff);
                $after = $progress->passedLines($this->withPrior($records, $ranges), $start, $cutoff);
                $proposals[$student->id] = $ranges;

                $status = fn (float $lines) => $target > 0 ? ($lines >= $target ? 'Tuntas' : 'Belum') : '-';
                $rows[] = [
                    $class->name,
                    $student->name,
                    ($surahs->get((int) $first->surah_number)?->name_latin ?? $first->surah_number).' '.$first->ayah_start,
                    $this->describe($ranges, $surahs),
                    $this->fmt($before).' -> '.$this->fmt($after).' / '.$target,
                    $status($before) === $status($after) ? $status($after) : $status($before).' -> '.$status($after),
                ];
            }
        }

        $this->info("Usulan hafalan sebelum aplikasi · acuan Triwulan {$term} {$year}");
        if ($rows === []) {
            $this->info('Tidak ada murid yang mulai di tengah juz tanpa catatan hafalan lama.');

            return self::SUCCESS;
        }
        $this->table(['Kelas', 'Murid', 'Setoran pertama', 'Usulan hafalan lama', 'Capaian (sekarang -> sesudah) / target', 'Status'], $rows);
        $this->line(count($rows).' murid. Konfirmasi ke guru halaqoh sebelum menyimpan; yang keliru bisa dihapus di halaman Urutan murid.');

        if (! $this->option('simpan')) {
            $this->warn('Mode tampil saja: belum ada yang disimpan. Tambahkan --simpan untuk menyimpan (bisa dibatasi --kelas / --murid).');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm('Simpan usulan di atas sebagai Hafalan Sebelum Aplikasi?')) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        $surahIds = $surahs->map->id;
        DB::transaction(function () use ($proposals, $surahIds) {
            foreach ($proposals as $studentId => $ranges) {
                foreach ($ranges as [$surah, $from, $to]) {
                    StudentPriorHafalan::firstOrCreate(['student_id' => $studentId, 'surah_id' => $surahIds[$surah], 'ayah_start' => $from, 'ayah_end' => $to]);
                }
            }
        });
        $this->info('Tersimpan untuk '.count($proposals).' murid.');

        return self::SUCCESS;
    }

    /**
     * Rentang yang belum tercakup di juz titik awal sebelum titik itu (+ juz sebelumnya bila diminta).
     *
     * @return array<int, array{0: int, 1: int, 2: int}> [surah, ayat awal, ayat akhir]
     */
    private function proposalFor(Student $student, int $surah, int $ayah, array $covered): array
    {
        $juz = HafalanOrder::juzOf($surah, $ayah);
        // Urutan juz: koreksi guru > aturan Kelas 11 & 12 > default (bukan deteksi setoran).
        $order = fn (int $j) => ($student->juz_orders[$j] ?? $student->juz_orders[(string) $j] ?? null)
            ?? ($j === 30 ? HafalanOrder::ASC : HafalanOrder::defaultJuzOrder($j));

        $pieces = [];
        foreach (HafalanOrder::juzPieces($juz, $order($juz)) as [$s, $from, $to]) {
            if ($s === $surah && $ayah >= $from && $ayah <= $to) {
                if ($ayah > $from) {
                    $pieces[] = [$s, $from, $ayah - 1];
                }
                break;
            }
            $pieces[] = [$s, $from, $to];
        }

        if ($this->option('juz-sebelumnya')) {
            $sequence = HafalanOrder::juzSequence($student->hafalan_direction);
            foreach (array_slice($sequence, 0, array_search($juz, $sequence, true)) as $earlier) {
                $pieces = array_merge($pieces, HafalanOrder::juzPieces($earlier, $order($earlier)));
            }
        }

        $ranges = [];
        foreach ($pieces as [$s, $from, $to]) {
            foreach (AyahCoverage::uncovered($covered[$s] ?? [], $from, $to) as [$a, $b]) {
                $ranges[] = [$s, $a, $b];
            }
        }

        return $ranges;
    }

    private function withPrior(Collection $records, array $ranges): Collection
    {
        $prior = collect($ranges)->map(fn ($r) => (new HafalanRecordSurah)->forceFill([
            'surah_number' => $r[0], 'ayah_start' => $r[1], 'ayah_end' => $r[2], 'status' => 'passed',
            'baris' => null, 'submitted_at' => HafalanProgressService::PRIOR_DATE, 'is_prior' => true,
        ]));

        return $prior->concat($records)->values();
    }

    private function termTargets(Student $student, $start, $end): Collection
    {
        return HafalanTarget::with('surah')->where('student_id', $student->id)
            ->whereBetween('target_date', [$start->toDateString(), $end->copy()->endOfDay()])
            ->get()->filter(fn ($t) => $t->surah);
    }

    private function describe(array $ranges, Collection $surahs): string
    {
        // Rentang berurutan dalam satu surah digabung untuk tampilan; juz penuh disebut "Juz N".
        $bySurah = collect($ranges)->groupBy(0)->map(fn ($items, $s) => ($surahs->get((int) $s)?->name_latin ?? $s).' '
            .$items->map(fn ($r) => "{$r[1]}-{$r[2]}")->implode(','));

        return $bySurah->count() > 4
            ? $bySurah->take(3)->implode('; ').'; … (+'.($bySurah->count() - 3).' surah)'
            : $bySurah->implode('; ');
    }

    private function fmt(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1), '0'), '.');
    }
}
