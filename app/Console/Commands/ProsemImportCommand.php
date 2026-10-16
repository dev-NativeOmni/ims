<?php

namespace App\Console\Commands;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\UmmiProsemRow;
use App\Services\UmmiProsemService;
use App\Support\AcademicYear;
use App\Support\SurahNameMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Impor Prosem Ummi satu halaqoh dari Google Sheet / Excel / CSV dengan susunan tab Prosem: kolom No,
 * Materi (nomor Jilid, atau "Dril"/"Ujian"), Hlm. Peraga, Hlm. Buku, Hafalan, Drill, TM, lalu 6 bulan x
 * pekan 1-5 berisi jumlah TM. Sumber: URL Google Sheet/Drive (diunduh sebagai Excel, tab yang namanya
 * memuat --tab), atau path .xlsx/.csv. Cakupan: --kelas & --musyrif (kosong = umum). Bawaan hanya
 * menampilkan; --simpan mengganti Prosem cakupan itu & menyinkronkan target Ummi otomatis.
 */
class ProsemImportCommand extends Command
{
    protected $signature = 'tad:prosem-import
        {sumber : URL Google Sheet/Drive, atau path file .xlsx/.csv}
        {--tab=prosem : Nama tab (cukup sebagian, mis. "prosem")}
        {--kelas= : Kelas yang memakai Prosem ini, mis. "X E2"}
        {--musyrif= : Nama musyrif halaqoh (boleh sebagian), mis. "Aiji"}
        {--tahun= : Tahun ajaran, mis. 2026/2027 (bawaan: tahun ajaran aktif)}
        {--semester=1 : Semester 1 (Jul-Des) atau 2 (Jan-Jun)}
        {--simpan : Simpan (mengganti Prosem semester itu)}';

    protected $description = 'Impor Program Semester (Prosem) metode Ummi dari Google Sheet/CSV.';

    public function handle(UmmiProsemService $prosem): int
    {
        $year = AcademicYear::isValid($this->option('tahun')) ? $this->option('tahun') : AcademicYear::active();
        $semester = (int) $this->option('semester') === 2 ? 2 : 1;
        $scope = $this->scope();
        if ($scope === null) {
            return self::FAILURE;
        }
        [$class, $teacher] = $scope;
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
                if ($text === '' || self::isActivity($text)) {
                    return [null, null];
                }
                $range = $matcher->matchRange($text);
                if (! $range) {
                    $warnings[] = "Baris {$no}: {$label} \"{$text}\" tidak dikenali, isi manual di halaman Prosem.";

                    return [null, null];
                }

                return [$surahIds[$range[0]], $surahIds[$range[1]]];
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

            // Materi: nomor Jilid, atau kegiatan ("Dril", "Ujian") yang dicatat sebagai keterangan.
            $jilid = trim((string) ($line[1] ?? ''));
            $activity = collect([$jilid, trim((string) ($line[4] ?? ''))])->first(fn ($t) => self::isActivity($t));
            if ($tm === 0 && ! ctype_digit($jilid) && $hafalanStart === null && $drillStart === null && ! $activity) {
                continue; // baris kosong di akhir tabel
            }
            $notes = trim((string) ($line[7 + count($months) * 5] ?? ''));
            $rows[] = [
                'academic_year' => $year, 'semester' => $semester, 'class_room_id' => $class?->id, 'teacher_id' => $teacher?->id, 'position' => count($rows) + 1,
                'jilid' => ctype_digit($jilid) ? "Jilid {$jilid}" : null,
                'peraga_start' => $peragaStart, 'peraga_end' => $peragaEnd, 'buku_start' => $bukuStart, 'buku_end' => $bukuEnd,
                'hafalan_start_surah_id' => $hafalanStart, 'hafalan_end_surah_id' => $hafalanEnd,
                'drill_start_surah_id' => $drillStart, 'drill_end_surah_id' => $drillEnd,
                'weeks' => $weeks, 'notes' => trim(implode(' · ', array_filter([$activity ? self::activityLabel($activity) : null, $notes]))) ?: null,
            ];
        }

        if ($rows === []) {
            $this->error('Tidak ada baris Prosem yang terbaca. Pastikan susunan kolom sama dengan tab Prosem.');

            return self::FAILURE;
        }

        $names = Surah::query()->pluck('name_latin', 'id');
        $this->info('Prosem Ummi '.($class ? $class->name : 'umum').($teacher ? ' · halaqoh '.$teacher->user?->name : '')." {$year} semester {$semester}: ".count($rows).' baris, '.collect($rows)->sum(fn ($r) => collect($r['weeks'])->flatten()->sum()).' TM');
        $this->table(['No', 'Jilid', 'Peraga', 'Buku', 'Hafalan', 'Drill', 'TM'], array_map(fn ($r) => [
            $r['position'], $r['jilid'] ?? ($r['notes'] ?? '-'),
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

        DB::transaction(function () use ($rows, $year, $semester, $class, $teacher) {
            UmmiProsemRow::query()->where('academic_year', $year)->where('semester', $semester)
                ->where('class_room_id', $class?->id)->where('teacher_id', $teacher?->id)->delete();
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

    /** Teks "Dril"/"Drill"/"Ujian" = kegiatan, bukan surah/Jilid. */
    private static function isActivity(string $text): bool
    {
        return (bool) preg_match('/^(dril+|ujian|evaluasi|murojaah|muraja\'?ah)\b/i', trim($text));
    }

    private static function activityLabel(string $text): string
    {
        return stripos($text, 'dril') === 0 ? 'Drill' : ucfirst(strtolower(trim($text)));
    }

    /**
     * Kelas & musyrif dari --kelas / --musyrif (keduanya boleh kosong = Prosem umum).
     *
     * @return array{0: ?ClassRoom, 1: ?TeacherProfile}|null
     */
    private function scope(): ?array
    {
        $class = null;
        if ($name = trim((string) $this->option('kelas'))) {
            $class = ClassRoom::query()->get()->first(fn ($c) => strcasecmp(trim($c->name), $name) === 0);
            if (! $class) {
                $this->error("Kelas \"{$name}\" tidak ditemukan.");

                return null;
            }
        }
        $teacher = null;
        if ($name = trim((string) $this->option('musyrif'))) {
            $matches = TeacherProfile::query()->with('user')->get()->filter(fn ($t) => stripos((string) $t->user?->name, $name) !== false);
            if ($class) {
                $inClass = $matches->filter(fn ($t) => Student::query()->where('class_room_id', $class->id)->where('teacher_id', $t->id)->exists());
                $matches = $inClass->isNotEmpty() ? $inClass : $matches;
            }
            if ($matches->count() !== 1) {
                $this->error($matches->isEmpty() ? "Musyrif \"{$name}\" tidak ditemukan." : "Musyrif \"{$name}\" cocok dengan lebih dari satu guru: ".$matches->map(fn ($t) => $t->user?->name)->implode(', ').'.');

                return null;
            }
            $teacher = $matches->first();
            if (! $class) {
                $this->warn('Musyrif tanpa --kelas: Prosem ini berlaku untuk halaqoh itu di kelas mana pun hanya bila disimpan per kelas; sebaiknya isi --kelas.');
            }
        }

        return [$class, $teacher];
    }

    /** @return array<int, array<int, mixed>>|null baris tabel tab Prosem */
    private function readSource(string $source): ?array
    {
        if (preg_match('#/(?:spreadsheets/d|file/d)/([\w-]+)#', $source, $m)) {
            $response = Http::timeout(60)->get("https://docs.google.com/spreadsheets/d/{$m[1]}/export?format=xlsx");
            if (! $response->successful()) {
                $this->error('Gagal mengambil file (pastikan dibagikan "siapa saja yang memiliki link"): HTTP '.$response->status());

                return null;
            }
            $path = tempnam(sys_get_temp_dir(), 'prosem').'.xlsx';
            file_put_contents($path, $response->body());
        } elseif (is_file($source)) {
            $path = $source;
        } else {
            $this->error("Sumber tidak ditemukan: {$source}");

            return null;
        }

        if (str_ends_with(strtolower($path), '.csv')) {
            $lines = [];
            $handle = fopen($path, 'r');
            while (($row = fgetcsv($handle, escape: '\\')) !== false) {
                $lines[] = $row;
            }
            fclose($handle);

            return $lines;
        }

        $book = IOFactory::load($path);
        $tab = (string) $this->option('tab');
        $sheet = collect($book->getSheetNames())->first(fn ($n) => stripos($n, $tab) !== false);
        if ($sheet === null) {
            $this->error("Tab yang memuat \"{$tab}\" tidak ada. Tab tersedia: ".implode(', ', $book->getSheetNames()));

            return null;
        }
        $this->line("Tab: {$sheet}");

        return $book->getSheetByName($sheet)->toArray(null, true, true, false);
    }
}
