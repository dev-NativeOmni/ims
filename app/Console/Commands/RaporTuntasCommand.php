<?php

namespace App\Console\Commands;

use App\Http\Controllers\StudentReportController;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Support\AcademicYear;
use Illuminate\Console\Command;

/**
 * Status Tahfizh rapor (Tuntas / Tidak Tuntas) seluruh murid per periode rapor, sama persis dengan
 * rapor cetak: rapor yang sudah dikunci memakai isi yang dibekukan, selain itu dihitung langsung
 * (StudentReportController::sheetFor). Hanya membaca.
 */
class RaporTuntasCommand extends Command
{
    protected $signature = 'tad:rapor-tuntas
        {--tahun= : Tahun ajaran, mis. 2026/2027 (bawaan: tahun ajaran aktif)}
        {--term= : Periode rapor 1-4 (bawaan: periode rapor aktif)}
        {--kelas= : Hanya kelas ini, mis. "XII F1"}
        {--semua : Tampilkan juga murid yang Tuntas (dengan --tabungan: juga yang posisinya melewati/belum sampai target)}
        {--tabungan : Murid yang baris capaiannya MELEBIHI target tapi Capaian Akhir (surah & ayat) SAMA dengan target}';

    protected $description = 'Daftar murid yang status Tahfizh rapornya Tidak Tuntas (sama dengan rapor cetak).';

    public function handle(StudentReportController $reports): int
    {
        $year = AcademicYear::isValid($this->option('tahun')) ? $this->option('tahun') : AcademicYear::active();
        $term = isset(StudentReportController::REPORT_PERIODS[(int) $this->option('term')]) ? (int) $this->option('term') : StudentReportController::activePeriod();
        $classDate = StudentReportController::classDateFor($year, $term);

        $classes = ClassRoom::query()->with('program')->orderBy('name')->get()
            ->when($this->option('kelas'), fn ($cs) => $cs->filter(fn ($c) => strcasecmp(trim($c->name), trim($this->option('kelas'))) === 0));

        $rows = [];
        $summary = [];
        $savings = [];
        foreach ($classes as $class) {
            $students = Student::query()->inClassOn($class->id, $classDate)->where('status', 'active')->orderBy('name')->get()
                ->each(fn (Student $s) => $s->setRelation('classRoom', $class));

            foreach ($students as $student) {
                ['sheet' => $sheet, 'locked_at' => $lockedAt] = $reports->sheetFor($student, $year, $term);
                $tahfizh = $sheet['tahfizh'] ?? [];
                $completed = $tahfizh['completed'] ?? null;
                if ($this->option('tabungan')) {
                    if ($row = $this->savingsRow($class, $student, $tahfizh)) {
                        $savings[] = $row;
                    }

                    continue;
                }
                $summary[$class->name] ??= ['total' => 0, 'belum' => 0];
                $summary[$class->name]['total']++;
                $summary[$class->name]['belum'] += $completed === false ? 1 : 0;

                if ($completed !== false && ! $this->option('semua')) {
                    continue;
                }
                $ummi = ($tahfizh['layout'] ?? null) === 'ummi';
                $position = fn (array $p) => $ummi
                    ? trim(($p['jilid'] ?? '-').' hal. '.($p['halaman'] ?? '-'))
                    : trim(($p['surah'] ?? '-').' '.($p['ayat'] ?? '-'));
                $rows[] = [
                    $class->name,
                    $student->name,
                    $ummi ? 'Ummi' : 'Baris',
                    $position($tahfizh['target'] ?? []),
                    $position($tahfizh['capaian'] ?? []),
                    isset($tahfizh['lines']) && $tahfizh['lines'] ? $tahfizh['lines']['achieved'].' / '.$tahfizh['lines']['target'] : '-',
                    $completed === null ? '-' : ($completed ? 'Tuntas' : 'Tidak Tuntas'),
                    $tahfizh['final_score'] ?? '-',
                    $lockedAt ? 'ya' : '-',
                ];
            }
        }

        $label = StudentReportController::REPORT_PERIODS[$term];
        if ($this->option('tabungan')) {
            return $this->printSavings($savings, "{$label} {$year}");
        }
        $this->info("Status Tahfizh rapor {$label} {$year}".($this->option('semua') ? '' : ' -- hanya Tidak Tuntas'));
        if ($rows !== []) {
            $this->table(['Kelas', 'Murid', 'Patokan', 'Target', 'Capaian akhir', 'Baris (capaian / target)', 'Status', 'Nilai', 'Terkunci'], $rows);
        }

        $this->table(['Kelas', 'Murid', 'Tidak Tuntas'], collect($summary)->map(fn ($s, $name) => [$name, $s['total'], $s['belum']])->values()->all());
        $total = array_sum(array_column($summary, 'total'));
        $belum = array_sum(array_column($summary, 'belum'));
        $this->line("Jumlah: {$belum} dari {$total} murid Tidak Tuntas. Patokan: Ummi = posisi Jilid/Halaman; Baris = baris ayat baru vs target.");

        return self::SUCCESS;
    }

    /**
     * Calon "tabungan baris": baris ayat baru melebihi target baris, dibanding posisi Capaian Akhir
     * terhadap target surah:ayat guru (sama / melewati / belum sampai). Bawaan hanya yang posisinya sama.
     */
    private function savingsRow(ClassRoom $class, Student $student, array $tahfizh): ?array
    {
        $lines = $tahfizh['lines'] ?? null;
        if (($tahfizh['layout'] ?? null) !== 'reguler' || ! $lines || (float) $lines['achieved'] <= (float) $lines['target']) {
            return null;
        }
        $target = trim(($tahfizh['target']['surah'] ?? '-').' '.($tahfizh['target']['ayat'] ?? '-'));
        $capaian = trim(($tahfizh['capaian']['surah'] ?? '-').' '.($tahfizh['capaian']['ayat'] ?? '-'));
        $position = match (true) {
            $target === '- -' || $target === '-' => 'tanpa target surah',
            $target === $capaian => 'sama',
            (bool) ($tahfizh['target_reached'] ?? false) => 'melewati',
            default => 'belum sampai',
        };
        if ($position !== 'sama' && ! $this->option('semua')) {
            return null;
        }
        $surplus = round((float) $lines['achieved'] - (float) $lines['target'], 1);

        return [$class->name, $student->name, $class->isTahfizhProgram() ? 'Tahfizh' : 'Reguler', $target, $capaian, $position,
            $lines['achieved'].' / '.$lines['target'], rtrim(rtrim(number_format($surplus, 1, '.', ''), '0'), '.')];
    }

    private function printSavings(array $rows, string $period): int
    {
        $this->info("Baris melebihi target · rapor {$period}".($this->option('semua') ? '' : ' -- hanya yang Capaian Akhir sama dengan target surah:ayat'));
        if ($rows === []) {
            $this->info('Tidak ada murid.');

            return self::SUCCESS;
        }
        $this->table(['Kelas', 'Murid', 'Program', 'Target', 'Capaian akhir', 'Posisi', 'Baris (capaian / target)', 'Lebih'], $rows);
        $this->line(count($rows).' murid. "Lebih" = baris ayat baru di atas target baris triwulan.');

        return self::SUCCESS;
    }
}
