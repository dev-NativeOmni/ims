<?php

namespace App\Console\Commands;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Services\AcademicCalendarService;
use App\Services\HafalanProgressService;
use App\Support\AcademicYear;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Rekap capaian baris Tahfizh seluruh murid Kelas 11 & 12 (non-Ummi) per triwulan: target, total baris
 * semua setoran lulus (cara hitung lama), capaian ayat baru (cara hitung rapor sekarang), ulangan,
 * setoran ganda, dan perubahan status Tuntas. Hanya membaca. Rincian satu murid: tad:rincian-baris.
 */
class RekapBarisCommand extends Command
{
    protected $signature = 'tad:rekap-baris
        {--tahun= : Tahun ajaran, mis. 2026/2027 (bawaan: tahun ajaran berjalan)}
        {--term= : Triwulan 1-4 (bawaan: triwulan berjalan)}
        {--kelas= : Hanya kelas ini (nama kelas, mis. "XI F1")}
        {--berubah : Hanya murid yang statusnya berubah atau punya ulangan/ganda}
        {--belum : Hanya murid yang belum tuntas (capaian ayat baru < target)}
        {--posisi : Hanya murid yang Capaian Akhir (surah:ayat) sudah sama/melewati target guru tapi baris belum tuntas}';

    protected $description = 'Rekap capaian baris Tahfizh Kelas 11 & 12 per triwulan (ayat baru vs semua setoran).';

    public function handle(HafalanProgressService $progress, AcademicCalendarService $calendar): int
    {
        $year = AcademicYear::isValid($this->option('tahun')) ? $this->option('tahun') : AcademicYear::forDate(now());
        $term = in_array((int) $this->option('term'), [1, 2, 3, 4], true) ? (int) $this->option('term') : AcademicYear::termOf(now());
        [$start, $end] = AcademicYear::termRange($year, $term);
        $cutoff = now()->min($end->copy()->endOfDay());
        $months = $calendar->termMonths($start);

        $classes = ClassRoom::query()->with('program')->orderBy('name')->get()
            ->filter(fn (ClassRoom $c) => $c->isGradeElevenOrTwelve())
            ->when($this->option('kelas'), fn ($cs) => $cs->filter(fn ($c) => strcasecmp(trim($c->name), trim($this->option('kelas'))) === 0));
        if ($classes->isEmpty()) {
            $this->error('Tidak ada kelas 11/12 yang cocok.');

            return self::FAILURE;
        }

        $this->info("Rekap capaian baris Triwulan {$term} {$year} ({$start->format('d/m/Y')} - {$end->format('d/m/Y')})");
        $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
        $rows = [];
        $positionRows = [];
        $count = ['murid' => 0, 'berubah' => 0, 'ulang' => 0, 'ganda' => 0, 'belum' => 0];

        foreach ($classes as $class) {
            $students = Student::query()->inClassOn($class->id, $cutoff)->where('status', 'active')->orderBy('name')->get()
                ->each(fn (Student $s) => $s->setRelation('classRoom', $class));

            foreach ($students as $student) {
                if (! HafalanProgressService::juz30FromNaba($student)) {
                    continue; // murid Ummi di kelas 11/12: hitungan buku Ummi, bukan baris
                }
                $records = $progress->records($student);
                $targets = HafalanTarget::with('surah')->where('student_id', $student->id)
                    ->whereBetween('target_date', [$start->toDateString(), $end->copy()->endOfDay()])
                    ->get()->filter(fn ($t) => $t->surah);
                $breakdown = $progress->termBreakdown($student, $targets, $months, $cutoff, $records);
                $target = (int) $breakdown['evaluation']['target_lines'];

                $details = collect($progress->passedLineDetails($records, $start, $cutoff));
                $all = (float) $details->sum('baris');
                $new = (float) $details->sum('new_lines');
                $duplicate = (float) $details->where('kind', 'duplicate')->sum('baris');
                $repeat = $all - $new - $duplicate;

                $wasTuntas = $target > 0 && $all >= $target;
                $isTuntas = $target > 0 && $new >= $target;
                $changed = $wasTuntas !== $isTuntas;

                $count['murid']++;
                $count['belum'] += $target > 0 && ! $isTuntas ? 1 : 0;
                $count['berubah'] += $changed ? 1 : 0;
                $count['ulang'] += $repeat > 0.05 ? 1 : 0;
                $count['ganda'] += $duplicate > 0.05 ? 1 : 0;

                if ($this->option('berubah') && ! $changed && $repeat <= 0.05 && $duplicate <= 0.05) {
                    continue;
                }
                if ($this->option('belum') && ($target === 0 || $isTuntas)) {
                    continue;
                }
                if ($this->option('posisi')) {
                    $position = $this->positionRow($progress, $student, $records, $breakdown, $end);
                    if ($target === 0 || $isTuntas || ! $position || ! $position['reached']) {
                        continue;
                    }
                    $positionRows[] = [
                        $class->name,
                        $student->name,
                        $student->tahfizh_level ?? '-',
                        $position['target'],
                        $position['capaian'],
                        $position['path'] ? 'ya' : 'belum',
                        $fmt($new).' / '.$target,
                        $fmt($target - $new),
                        $fmt($repeat),
                    ];

                    continue;
                }

                $status = fn (bool $t) => $target === 0 ? '-' : ($t ? 'Tuntas' : 'Belum');
                $rows[] = [
                    $class->name,
                    $student->name,
                    $student->tahfizh_level ?? '-',
                    $target,
                    $fmt($all),
                    $fmt($new),
                    $fmt($repeat),
                    $fmt($duplicate),
                    $changed ? $status($wasTuntas).' -> '.$status($isTuntas) : $status($isTuntas),
                ];
            }
        }

        if ($this->option('posisi')) {
            $this->table(['Kelas', 'Murid', 'Level', 'Target surah', 'Capaian akhir', 'Jalur lengkap', 'Baris (ayat baru / target)', 'Kurang', 'Ulangan'], $positionRows);
            $this->line(count($positionRows).' murid: Capaian Akhir sudah sama/melewati target surah:ayat guru, tapi baris ayat baru belum mencapai target baris.');
            $this->line('"Jalur lengkap" = semua ayat dari titik awal triwulan sampai target sudah lulus disetor.');

            return self::SUCCESS;
        }

        $this->table(['Kelas', 'Murid', 'Level', 'Target', 'Semua setoran', 'Ayat baru (rapor)', 'Ulangan', 'Ganda', 'Status'], $rows);
        $this->line("Murid diperiksa: {$count['murid']} · belum tuntas: {$count['belum']} · status berubah: {$count['berubah']} · ada ulangan: {$count['ulang']} · ada setoran ganda: {$count['ganda']}");

        return self::SUCCESS;
    }

    /**
     * Target surah:ayat guru di triwulan vs Capaian Akhir (setoran terakhir sampai akhir triwulan, sama
     * dengan rapor), dan apakah seluruh jalur dari titik awal sampai target sudah lulus disetor.
     *
     * @return array{target: string, capaian: string, reached: bool, path: bool}|null
     */
    private function positionRow(HafalanProgressService $progress, Student $student, $records, array $breakdown, $end): ?array
    {
        $target = $breakdown['target'];
        if (! $target?->surah) {
            return null;
        }
        $orders = $progress->juzOrders($student, $records);
        $rank = fn (int $surah, int $ayah) => HafalanOrder::rank($surah, $ayah, $student->hafalan_direction, $orders);

        $capaian = $records->reject(fn ($r) => $r->is_prior)
            ->filter(fn ($r) => Carbon::parse($r->submitted_at)->lte($end->copy()->endOfDay()))
            ->sort(function ($a, $b) use ($rank) {
                $byDate = Carbon::parse($b->submitted_at)->timestamp <=> Carbon::parse($a->submitted_at)->timestamp;

                return $byDate !== 0 ? $byDate : $rank((int) $b->surah_number, (int) $b->ayah_end) <=> $rank((int) $a->surah_number, (int) $a->ayah_end);
            })
            ->first();
        if (! $capaian) {
            return null;
        }

        $surahs = $progress->surahs();
        $targetSurah = (int) $target->surah->number;

        return [
            'target' => ($target->surah->name_latin ?? $targetSurah).' '.$target->ayah,
            'capaian' => ($surahs->get((int) $capaian->surah_number)?->name_latin ?? $capaian->surah_number).' '.$capaian->ayah_end,
            'reached' => $rank((int) $capaian->surah_number, (int) $capaian->ayah_end) >= $rank($targetSurah, (int) $target->ayah),
            'path' => (bool) ($breakdown['position']['position_reached'] ?? false),
        ];
    }
}
