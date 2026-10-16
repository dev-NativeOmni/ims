<?php

namespace App\Console\Commands;

use App\Models\Surah;
use App\Models\UmmiProsemRow;
use App\Services\UmmiProsemService;
use App\Support\AcademicYear;
use App\Support\SurahNameMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Impor Prosem Ummi dari Google Sheet / CSV dengan susunan tab "Prosem tahfidz sem N": kolom No, Materi
 * (nomor Jilid), Hlm. Peraga, Hlm. Buku, Hafalan, Drill, TM, lalu 6 bulan x pekan 1-5 berisi jumlah TM.
 * Sumber: path CSV, atau URL Google Sheet + --tab (diambil sebagai CSV). Bawaan hanya menampilkan;
 * --simpan mengganti Prosem semester itu & menyinkronkan target Ummi otomatis.
 */
class ProsemImportCommand extends Command
{
    protected $signature = 'tad:prosem-import
        {sumber : Path file CSV atau URL Google Sheet}
        {--tab=Prosem tahfidz sem 1 : Nama tab bila sumber URL Google Sheet}
        {--tahun= : Tahun ajaran, mis. 2026/2027 (bawaan: tahun ajaran aktif)}
        {--semester=1 : Semester 1 (Jul-Des) atau 2 (Jan-Jun)}
        {--simpan : Simpan (mengganti Prosem semester itu)}';

    protected $description = 'Impor Program Semester (Prosem) metode Ummi dari Google Sheet/CSV.';

    public function handle(UmmiProsemService $prosem): int
    {
        $year = AcademicYear::isValid($this->option('tahun')) ? $this->option('tahun') : AcademicYear::active();
        $semester = (int) $this->option('semester') === 2 ? 2 : 1;
        $csv = $this->readSource((string) $this->argument('sumber'));
        if ($csv === null) {
            return self::FAILURE;
        }

        $matcher = new SurahNameMatcher;
        $surahIds = Surah::query()->pluck('id', 'number');
        $months = UmmiProsemService::SEMESTER_MONTHS[$semester];
        $rows = [];
        $warnings = [];

        foreach ($csv as $line) {
            if (! ctype_digit(trim((string) ($line[0] ?? '')))) {
                continue; // judul & baris jumlah
            }
            $no = (int) $line[0];
            $weeks = [];
            foreach ($months as $m => $month) {
                foreach (range(1, 5) as $week) {
                    $value = (int) trim((string) ($line[7 + $m * 5 + $week - 1] ?? ''));
                    if ($value > 0) {
                        $weeks[(string) $month][(string) $week] = $value;
                    }
                }
            }
            [$peragaStart, $peragaEnd] = $this->range($line[2] ?? '');
            [$bukuStart, $bukuEnd] = $this->range($line[3] ?? '');
            $surahRange = function (string $text, string $label) use ($matcher, $surahIds, $no, &$warnings) {
                $text = trim($text);
                if ($text === '') {
                    return [null, null];
                }
                $parts = array_map('trim', preg_split('/\s+-\s+|\s*–\s*/u', $text));
                $ids = array_map(fn ($p) => ($n = $matcher->match($p)) ? $surahIds[$n] : null, $parts);
                if (in_array(null, $ids, true)) {
                    $warnings[] = "Baris {$no}: {$label} \"{$text}\" tidak dikenali, isi manual di halaman Prosem.";
                }

                return [$ids[0], end($ids) ?: $ids[0]];
            };
            [$hafalanStart, $hafalanEnd] = $surahRange((string) ($line[4] ?? ''), 'Hafalan');
            [$drillStart, $drillEnd] = $surahRange((string) ($line[5] ?? ''), 'Drill');
            foreach (['Peraga' => [$peragaStart, $peragaEnd], 'Buku' => [$bukuStart, $bukuEnd]] as $label => [$a, $b]) {
                if ($a && $b && $b < $a) {
                    $warnings[] = "Baris {$no}: Hlm. {$label} {$a}-{$b} (akhir < awal), tidak dibagi per TM; mohon cek.";
                }
            }
            $tm = (int) collect($weeks)->flatten()->sum();
            if ((int) ($line[6] ?? 0) !== $tm) {
                $warnings[] = "Baris {$no}: kolom TM ".((int) ($line[6] ?? 0))." berbeda dengan jumlah sebaran pekan {$tm}; dipakai {$tm}.";
            }

            $jilid = trim((string) ($line[1] ?? ''));
            $rows[] = [
                'academic_year' => $year, 'semester' => $semester, 'position' => $no,
                'jilid' => $jilid !== '' ? (ctype_digit($jilid) ? "Jilid {$jilid}" : $jilid) : null,
                'peraga_start' => $peragaStart, 'peraga_end' => $peragaEnd, 'buku_start' => $bukuStart, 'buku_end' => $bukuEnd,
                'hafalan_start_surah_id' => $hafalanStart, 'hafalan_end_surah_id' => $hafalanEnd,
                'drill_start_surah_id' => $drillStart, 'drill_end_surah_id' => $drillEnd,
                'weeks' => $weeks, 'notes' => trim((string) ($line[7 + count($months) * 5] ?? '')) ?: null,
            ];
        }

        if ($rows === []) {
            $this->error('Tidak ada baris Prosem yang terbaca. Pastikan susunan kolom sama dengan tab Prosem.');

            return self::FAILURE;
        }

        $names = Surah::query()->pluck('name_latin', 'id');
        $this->info("Prosem Ummi {$year} semester {$semester}: ".count($rows).' baris, '.collect($rows)->sum(fn ($r) => collect($r['weeks'])->flatten()->sum()).' TM');
        $this->table(['No', 'Jilid', 'Peraga', 'Buku', 'Hafalan', 'Drill', 'TM'], array_map(fn ($r) => [
            $r['position'], $r['jilid'] ?? '-',
            $r['peraga_start'] ? "{$r['peraga_start']}-{$r['peraga_end']}" : '-',
            $r['buku_start'] ? "{$r['buku_start']}-{$r['buku_end']}" : '-',
            $r['hafalan_start_surah_id'] ? ($names[$r['hafalan_start_surah_id']] ?? '?').' – '.($names[$r['hafalan_end_surah_id']] ?? '?') : '-',
            $r['drill_start_surah_id'] ? ($names[$r['drill_start_surah_id']] ?? '?').' – '.($names[$r['drill_end_surah_id']] ?? '?') : '-',
            collect($r['weeks'])->flatten()->sum(),
        ], $rows));
        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        if (! $this->option('simpan')) {
            $this->warn('Mode tampil saja: belum disimpan. Tambahkan --simpan untuk menyimpan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows, $year, $semester) {
            UmmiProsemRow::query()->where('academic_year', $year)->where('semester', $semester)->delete();
            foreach ($rows as $row) {
                UmmiProsemRow::create($row);
            }
        });
        $synced = app(UmmiProsemService::class)->syncTargets($year, $semester);
        $this->info("Tersimpan. Target Ummi otomatis dibuat/diubah: {$synced}.");

        return self::SUCCESS;
    }

    /** @return array{0: ?int, 1: ?int} */
    private function range(string $text): array
    {
        if (! preg_match('/^\s*(\d+)\s*(?:-\s*(\d+))?\s*$/', $text, $m)) {
            return [null, null];
        }

        return [(int) $m[1], (int) ($m[2] ?? $m[1])];
    }

    /** @return array<int, array<int, string>>|null */
    private function readSource(string $source): ?array
    {
        if (preg_match('#docs\.google\.com/spreadsheets/d/([\w-]+)#', $source, $m)) {
            $url = "https://docs.google.com/spreadsheets/d/{$m[1]}/gviz/tq?tqx=out:csv&sheet=".rawurlencode((string) $this->option('tab'));
            $response = Http::timeout(30)->get($url);
            if (! $response->successful()) {
                $this->error('Gagal mengambil Google Sheet (pastikan dibagikan "siapa saja yang memiliki link"): HTTP '.$response->status());

                return null;
            }
            $text = $response->body();
        } elseif (is_file($source)) {
            $text = (string) file_get_contents($source);
        } else {
            $this->error("Sumber tidak ditemukan: {$source}");

            return null;
        }

        $lines = [];
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $text);
        rewind($handle);
        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            $lines[] = $row;
        }
        fclose($handle);

        return $lines;
    }
}
