<?php

namespace App\Console\Commands;

use App\Models\HafalanTarget;
use App\Models\Student;
use App\Services\AcademicCalendarService;
use App\Services\HafalanProgressService;
use App\Support\AcademicYear;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Rincian capaian baris satu murid dalam satu triwulan -- angka yang sama dengan kolom
 * "BARIS Capaian / Target" rapor (HafalanProgressService::passedLineDetails), dipecah per setoran:
 * ayat baru (dihitung), ulangan ayat yang sudah pernah lulus & setoran ganda (tidak dihitung). Hanya membaca.
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

        $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
        $labels = [
            'new' => 'baru',
            'partial' => 'sebagian ulang',
            'repeat' => 'ULANG (ayat sudah pernah lulus)',
            'duplicate' => 'GANDA (sama persis di tanggal ini)',
        ];
        $surahs = $progress->surahs();
        $details = $progress->passedLineDetails($records, $start, now()->min($end->copy()->endOfDay()));

        $this->table(['Tanggal', 'Surah', 'Ayat', 'Baris setoran', 'Dihitung capaian', 'Keterangan'], array_map(fn ($d) => [
            Carbon::parse($d['record']->submitted_at)->format('d/m/Y'),
            $surahs->get((int) $d['record']->surah_number)?->name_latin ?? $d['record']->surah_number,
            "{$d['record']->ayah_start}-{$d['record']->ayah_end}",
            $fmt($d['baris']).(abs($d['baris'] - $d['calculated']) > 0.01 ? " (manual, kalkulator {$fmt($d['calculated'])})" : ''),
            $fmt($d['new_lines']),
            $labels[$d['kind']].($d['kind'] === 'partial' ? " ({$d['new_ayat']} ayat baru)" : ''),
        ], $details));

        $byKind = collect($details)->groupBy('kind');
        $this->line('Total baris semua setoran lulus: '.$fmt(collect($details)->sum('baris'))
            .' · dihitung capaian (ayat baru): '.$fmt(collect($details)->sum('new_lines'))
            .' · ulangan: '.$fmt($byKind->get('repeat', collect())->sum('baris') + $byKind->get('partial', collect())->sum(fn ($d) => $d['baris'] - $d['new_lines']))
            .' · ganda: '.$fmt($byKind->get('duplicate', collect())->sum('baris')));

        return self::SUCCESS;
    }
}
