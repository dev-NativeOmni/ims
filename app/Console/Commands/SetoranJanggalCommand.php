<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\HafalanRecord;
use App\Models\HafalanRecordSurah;
use App\Models\Student;
use App\Services\HafalanProgressService;
use App\Support\AcademicYear;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Setoran janggal per triwulan: setoran lulus yang ayatnya DIULANG oleh beberapa setoran sesudahnya
 * (mis. 28/07 Al-Baqarah 253-269, lalu 6 pekan menyetor 253-256, 257-259, ... lagi). Setoran sesudahnya
 * jadi ulangan (0 baris), jadi bila setoran pertama itu salah tanggal/ayat, capaian murid tertahan.
 * Dugaan: salah tanggal (diinput sesudah setoran pengulangnya), salah ayat (jauh lebih panjang dari
 * setoran biasa murid itu), atau murid memang mengulang (catat sebagai Muraja'ah). Hanya membaca;
 * pembetulan lewat Edit Setoran setelah dikonfirmasi guru.
 */
class SetoranJanggalCommand extends Command
{
    protected $signature = 'tad:setoran-janggal
        {--tahun= : Tahun ajaran, mis. 2026/2027 (bawaan: tahun ajaran berjalan)}
        {--term= : Triwulan 1-4 (bawaan: triwulan berjalan)}
        {--kelas= : Hanya kelas ini, mis. "XII F1"}
        {--murid=* : Hanya murid ini (nama, boleh sebagian; bisa diulang)}
        {--min-ulang=2 : Minimal jumlah setoran sesudahnya yang mengulang ayat setoran itu}';

    protected $description = 'Cari setoran yang ayatnya diulang beberapa setoran sesudahnya (kemungkinan salah tanggal/ayat).';

    public function handle(HafalanProgressService $progress): int
    {
        $year = AcademicYear::isValid($this->option('tahun')) ? $this->option('tahun') : AcademicYear::forDate(now());
        $term = in_array((int) $this->option('term'), [1, 2, 3, 4], true) ? (int) $this->option('term') : AcademicYear::termOf(now());
        [$start, $end] = AcademicYear::termRange($year, $term);
        $cutoff = now()->min($end->copy()->endOfDay());
        $minRepeats = max(1, (int) $this->option('min-ulang'));
        $names = array_filter(array_map('trim', (array) $this->option('murid')));

        $students = Student::query()->with('classRoom')->where('status', 'active')
            ->whereHas('hafalanRecords', fn ($q) => $q->whereBetween('submitted_at', [$start->toDateString(), $cutoff->toDateString()]))
            ->orderBy('name')->get()
            ->when($this->option('kelas'), fn ($s) => $s->filter(fn ($st) => strcasecmp(trim((string) $st->classRoom?->name), trim($this->option('kelas'))) === 0))
            ->when($names !== [], fn ($s) => $s->filter(fn ($st) => collect($names)->contains(fn ($n) => stripos($st->name, $n) !== false)));

        $surahs = $progress->surahs();
        $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
        $rows = [];

        foreach ($students as $student) {
            $details = collect($progress->passedLineDetails($progress->records($student), $start, $cutoff))
                ->reject(fn ($d) => $d['kind'] === 'duplicate')->values();
            $ayatCount = fn ($d) => (int) $d['record']->ayah_end - (int) $d['record']->ayah_start + 1;
            $median = $details->map($ayatCount)->median() ?: 1;
            $lines = HafalanRecordSurah::query()->whereIn('id', $details->pluck('record.line_id')->filter())->get()->keyBy('id');

            foreach ($details as $d) {
                $r = $d['record'];
                $date = Carbon::parse($r->submitted_at)->toDateString();
                // Setoran sesudahnya (beda tanggal) yang seluruh ayatnya ada di dalam setoran ini & tidak dihitung baru.
                $followers = $details->filter(fn ($f) => Carbon::parse($f['record']->submitted_at)->toDateString() > $date
                    && (int) $f['record']->surah_number === (int) $r->surah_number
                    && (int) $f['record']->ayah_start >= (int) $r->ayah_start && (int) $f['record']->ayah_end <= (int) $r->ayah_end
                    && $f['kind'] !== 'new');
                if ($followers->count() < $minRepeats) {
                    continue;
                }

                $line = $lines->get((int) $r->line_id);
                $followerLines = $followers->map(fn ($f) => $lines->get((int) $f['record']->line_id))->filter();
                $suspects = [];
                if ($line?->created_at && $followerLines->contains(fn ($l) => $l->created_at && $l->created_at->lt($line->created_at))) {
                    $suspects[] = 'salah tanggal? diinput sesudah setoran pengulangnya';
                }
                if ($ayatCount($d) >= 2 * $median && $ayatCount($d) >= 6) {
                    $suspects[] = 'salah ayat? '.$ayatCount($d).' ayat, biasanya '.$fmt($median);
                }

                $rows[] = [
                    $student->classRoom?->name ?? '-',
                    $student->name,
                    Carbon::parse($r->submitted_at)->format('d/m/Y'),
                    ($surahs->get((int) $r->surah_number)?->name_latin ?? $r->surah_number)." {$r->ayah_start}-{$r->ayah_end}",
                    $fmt($d['new_lines']),
                    $followers->count().'x: '.$followers->take(4)->map(fn ($f) => Carbon::parse($f['record']->submitted_at)->format('d/m')." ({$f['record']->ayah_start}-{$f['record']->ayah_end})")->implode(', ')
                        .($followers->count() > 4 ? ', ...' : ''),
                    $fmt($followers->sum('baris')),
                    ($line?->created_at?->format('d/m H:i') ?? '-').' · '.AuditLog::createdVia((new HafalanRecord)->getMorphClass(), $line?->hafalan_record_id),
                    $suspects === [] ? 'murid mengulang? catat sebagai Muraja\'ah' : implode('; ', $suspects),
                ];
            }
        }

        $this->info("Setoran janggal Triwulan {$term} {$year} ({$start->format('d/m/Y')} - {$end->format('d/m/Y')})");
        if ($rows === []) {
            $this->info('Tidak ada setoran yang diulang '.$minRepeats.'x atau lebih oleh setoran sesudahnya.');

            return self::SUCCESS;
        }
        $this->table(['Kelas', 'Murid', 'Tanggal', 'Setoran', 'Baris dihitung', 'Diulang oleh setoran', 'Baris tertahan', 'Diinput', 'Dugaan'], $rows);
        $this->line(count($rows).' setoran dari '.collect($rows)->pluck(1)->unique()->count().' murid. "Baris tertahan" = baris setoran pengulang yang tidak dihitung karena ayatnya sudah lulus di setoran ini.');
        $this->line('Konfirmasi ke guru halaqoh: bila tanggal/ayat setoran ini keliru, betulkan lewat Edit Setoran; bila murid memang mengulang, setoran pengulang sebaiknya dicatat di Muraja\'ah.');

        return self::SUCCESS;
    }
}
