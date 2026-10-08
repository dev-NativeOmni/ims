<?php

namespace App\Console\Commands;

use App\Http\Controllers\ReportController;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Services\AcademicCalendarService;
use App\Services\HafalanProgressService;
use App\Support\AcademicYear;
use App\Support\AyahCoverage;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Rincian capaian baris satu murid dalam satu triwulan -- angka yang sama dengan kolom
 * "BARIS Capaian / Target" rapor (HafalanProgressService::termBreakdown), dipecah per setoran:
 * ayat baru, ulangan ayat yang sudah pernah lulus, dan setoran ganda identik. Hanya membaca.
 */
class RincianBarisCommand extends Command
{
    protected $signature = 'tad:rincian-baris
        {murid : Nama murid (sebagian juga boleh) atau ID}
        {--tahun= : Tahun ajaran, mis. 2026/2027 (bawaan: tahun ajaran berjalan)}
        {--term= : Triwulan 1-4 (bawaan: triwulan berjalan)}';

    protected $description = 'Rincian capaian baris Tahfizh satu murid per triwulan (sama dengan rapor).';

    public function handle(HafalanProgressService $progress, AcademicCalendarService $calendar): int
    {
        $query = $this->argument('murid');
        $students = Student::query()->with('classRoom.program')
            ->when(ctype_digit((string) $query), fn ($q) => $q->whereKey((int) $query), fn ($q) => $q->where('name', 'like', "%{$query}%"))
            ->get();
        if ($students->count() !== 1) {
            $this->error($students->isEmpty() ? 'Murid tidak ditemukan.' : 'Lebih dari satu murid cocok: '.$students->pluck('name')->implode(', ').'. Pakai nama lengkap atau ID.');

            return self::FAILURE;
        }
        $student = $students->first();

        $year = AcademicYear::isValid($this->option('tahun')) ? $this->option('tahun') : AcademicYear::forDate(now());
        $term = in_array((int) $this->option('term'), [1, 2, 3, 4], true) ? (int) $this->option('term') : AcademicYear::termOf(now());
        [$start, $end] = AcademicYear::termRange($year, $term);

        // Kelas murid pada triwulan itu (riwayat kelas), sama dengan rapor.
        if ($classAtTerm = $student->classRoomOn($end->copy()->min(now()))) {
            $student->setRelation('classRoom', $classAtTerm->loadMissing('program'));
        }

        $records = $progress->records($student);
        $targets = HafalanTarget::with('surah')->where('student_id', $student->id)
            ->whereBetween('target_date', [$start->toDateString(), $end->copy()->endOfDay()])
            ->get()->filter(fn ($t) => $t->surah);
        $breakdown = $progress->termBreakdown($student, $targets, $calendar->termMonths($start), now()->min($end->copy()->endOfDay()), $records);

        $this->info("{$student->name} · {$student->classRoom?->name} · {$student->classRoom?->program?->name} · level {$student->tahfizh_level}");
        $this->info("Triwulan {$term} {$year} ({$start->format('d/m/Y')} - {$end->format('d/m/Y')})");
        $this->line('Rapor: capaian '.$breakdown['evaluation']['achieved_lines'].' / target '.$breakdown['evaluation']['target_lines'].' baris');

        // Cakupan ayat lulus sebelum triwulan; diperbarui per setoran untuk menandai ulangan.
        $covered = $progress->coverage($records, $start);
        $surahs = $progress->surahs();
        $seen = [];
        $rows = [];
        $sum = ['baru' => 0.0, 'ulang' => 0.0, 'ganda' => 0.0];

        $termRecords = $records->filter(fn ($r) => $r->status === 'passed'
            && Carbon::parse($r->submitted_at)->betweenIncluded($start->copy()->startOfDay(), $end->copy()->endOfDay()));
        foreach ($termRecords as $r) {
            $surahNumber = (int) $r->surah_number;
            $from = (int) $r->ayah_start;
            $to = (int) $r->ayah_end;
            $totalAyah = (int) ($surahs->get($surahNumber)?->total_ayah ?? 0);
            $calc = ReportController::calculateLines($surahNumber, $from, $to, $totalAyah);
            $baris = $r->baris !== null ? (float) $r->baris : $calc;

            $key = Carbon::parse($r->submitted_at)->toDateString()."|{$surahNumber}|{$from}|{$to}";
            if (isset($seen[$key])) {
                $kind = 'GANDA (sama persis di tanggal ini)';
                $sum['ganda'] += $baris;
            } else {
                $seen[$key] = true;
                $newAyat = 0;
                foreach (AyahCoverage::uncovered($covered[$surahNumber] ?? [], $from, $to) as [$a, $b]) {
                    $newAyat += $b - $a + 1;
                }
                $allAyat = $to - $from + 1;
                if ($newAyat === $allAyat) {
                    $kind = 'baru';
                    $sum['baru'] += $baris;
                } elseif ($newAyat === 0) {
                    $kind = 'ULANG (ayat sudah pernah lulus)';
                    $sum['ulang'] += $baris;
                } else {
                    $kind = "sebagian ulang ({$newAyat} dari {$allAyat} ayat baru)";
                    $sum['baru'] += $baris * $newAyat / $allAyat;
                    $sum['ulang'] += $baris * ($allAyat - $newAyat) / $allAyat;
                }
                $covered[$surahNumber] = AyahCoverage::merge(array_merge($covered[$surahNumber] ?? [], [[$from, $to]]));
            }

            $rows[] = [
                Carbon::parse($r->submitted_at)->format('d/m/Y'),
                $surahs->get($surahNumber)?->name_latin ?? $surahNumber,
                "{$from}-{$to}",
                rtrim(rtrim(number_format($baris, 1), '0'), '.').(abs($baris - $calc) > 0.01 ? ' (manual, kalkulator '.rtrim(rtrim(number_format($calc, 1), '0'), '.').')' : ''),
                $kind,
            ];
        }

        $this->table(['Tanggal', 'Surah', 'Ayat', 'Baris', 'Keterangan'], $rows);
        $fmt = fn ($v) => rtrim(rtrim(number_format($v, 1), '0'), '.');
        $this->line("Asal baris triwulan ini: ayat baru {$fmt($sum['baru'])} · ulangan {$fmt($sum['ulang'])} · ganda {$fmt($sum['ganda'])}");

        return self::SUCCESS;
    }
}
