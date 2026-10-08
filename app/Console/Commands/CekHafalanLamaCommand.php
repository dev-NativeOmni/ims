<?php

namespace App\Console\Commands;

use App\Models\HafalanRecordSurah;
use App\Models\Student;
use App\Models\StudentPriorHafalan;
use App\Services\HafalanProgressService;
use App\Support\AyahCoverage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Setoran yang tertimpa "Hafalan Sebelum Aplikasi" (StudentPriorHafalan): ayatnya ikut ditandai hafalan
 * lama sehingga dianggap ulangan (0 baris). Yang mencurigakan = setoran yang diinput/diubah SESUDAH tanda
 * hafalan lama dibuat -- biasanya guru baru melengkapi/membetulkan setoran awal triwulan, padahal tanda
 * hafalan lama dibuat dari data lama (mis. tad:usul-hafalan-awal). --perbaiki memotong tanda hafalan lama
 * pada ayat setoran itu sehingga setoran kembali dihitung ayat baru (tabel dicadangkan dulu ke
 * storage/app/backups/hafalan-lama; batalkan dengan --pulihkan). Bawaan hanya menampilkan.
 */
class CekHafalanLamaCommand extends Command
{
    protected $signature = 'tad:cek-hafalan-lama
        {--kelas= : Hanya kelas ini, mis. "XII F3"}
        {--murid=* : Hanya murid ini (nama, boleh sebagian; bisa diulang)}
        {--semua : Tampilkan juga setoran yang diinput sebelum tanda hafalan lama dibuat (ulangan hafalan lama yang wajar)}
        {--perbaiki : Potong tanda hafalan lama pada ayat setoran yang mencurigakan}
        {--force : Perbaiki/pulihkan tanpa pertanyaan konfirmasi}
        {--pulihkan= : Kembalikan tabel hafalan lama dari file cadangan (nama file dari --perbaiki)}';

    protected $description = 'Cari setoran yang tertimpa tanda hafalan sebelum aplikasi (dihitung ulangan).';

    public function handle(HafalanProgressService $progress): int
    {
        if ($this->option('pulihkan')) {
            return $this->restore((string) $this->option('pulihkan'));
        }

        $names = array_filter(array_map('trim', (array) $this->option('murid')));
        $students = Student::query()->with('classRoom')->where('status', 'active')
            ->whereHas('priorHafalans')->orderBy('name')->get()
            ->when($this->option('kelas'), fn ($s) => $s->filter(fn ($st) => strcasecmp(trim((string) $st->classRoom?->name), trim($this->option('kelas'))) === 0))
            ->when($names !== [], fn ($s) => $s->filter(fn ($st) => collect($names)->contains(fn ($n) => stripos($st->name, $n) !== false)));

        $surahs = $progress->surahs();
        $rows = [];
        $trim = []; // prior id => [[ayat awal, ayat akhir], ...] yang dilepas dari hafalan lama

        foreach ($students as $student) {
            $priors = $student->priorHafalans()->with('surah')->get();
            $setoran = $progress->records($student)->reject(fn ($r) => $r->is_prior)->where('status', 'passed')->values();
            $lines = HafalanRecordSurah::query()->whereIn('id', $setoran->pluck('line_id')->filter())->get()->keyBy('id');

            foreach ($setoran as $record) {
                $line = $lines->get((int) $record->line_id);
                $entered = $line?->updated_at ?? $line?->created_at;
                foreach ($priors->filter(fn ($p) => (int) $p->surah->number === (int) $record->surah_number) as $prior) {
                    $from = max((int) $prior->ayah_start, (int) $record->ayah_start);
                    $to = min((int) $prior->ayah_end, (int) $record->ayah_end);
                    if ($from > $to) {
                        continue;
                    }
                    // Diinput/diubah sesudah tanda hafalan lama dibuat (selisih > 1 menit).
                    $suspect = $entered && $prior->created_at && $entered->gt($prior->created_at->copy()->addMinute());
                    if (! $suspect && ! $this->option('semua')) {
                        continue;
                    }
                    if ($suspect) {
                        $trim[$prior->id][] = [$from, $to];
                    }
                    $rows[] = [
                        $student->classRoom?->name ?? '-',
                        $student->name,
                        Carbon::parse($record->submitted_at)->format('d/m/Y'),
                        ($surahs->get((int) $record->surah_number)?->name_latin ?? $record->surah_number)." {$record->ayah_start}-{$record->ayah_end}",
                        "{$prior->surah->name_latin} {$prior->ayah_start}-{$prior->ayah_end}",
                        $prior->created_at?->format('d/m/Y H:i') ?? '-',
                        $entered?->format('d/m/Y H:i') ?? '-',
                        $suspect ? 'CURIGA: lepas ayat '.($from === $to ? $from : "{$from}-{$to}") : 'ulangan wajar',
                    ];
                }
            }
        }

        if ($rows === []) {
            $this->info('Tidak ada setoran yang tertimpa tanda hafalan lama'.($this->option('semua') ? '.' : ' sesudah tanda itu dibuat.'));

            return self::SUCCESS;
        }
        $this->table(['Kelas', 'Murid', 'Tanggal setoran', 'Setoran', 'Tanda hafalan lama', 'Tanda dibuat', 'Setoran diinput/diubah', 'Keterangan'], $rows);
        $this->line(count($rows).' baris. "CURIGA" = setoran diinput/diubah sesudah tanda hafalan lama dibuat, jadi tanda itu kemungkinan keliru.');

        if ($trim === []) {
            return self::SUCCESS;
        }
        if (! $this->option('perbaiki')) {
            $this->warn('Mode tampil saja. Tambahkan --perbaiki untuk melepas ayat bertanda CURIGA dari hafalan lama (bisa dibatasi --kelas / --murid).');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm('Lepas ayat bertanda CURIGA dari hafalan sebelum aplikasi?')) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        // Cadangan seluruh tabel hafalan lama sebelum diubah (tanpa mysqldump), untuk --pulihkan.
        $backup = $this->backupDirectory().'/hafalan_lama_'.now()->format('Y-m-d_His').'.json';
        File::put($backup, StudentPriorHafalan::query()->orderBy('id')->get()->map->getAttributes()->toJson(JSON_PRETTY_PRINT));
        $this->info('Cadangan: '.$backup.' ('.StudentPriorHafalan::count().' rentang)');

        DB::transaction(function () use ($trim) {
            foreach (StudentPriorHafalan::query()->whereIn('id', array_keys($trim))->get() as $prior) {
                $keep = AyahCoverage::uncovered(AyahCoverage::merge($trim[$prior->id]), (int) $prior->ayah_start, (int) $prior->ayah_end);
                foreach ($keep as [$from, $to]) {
                    StudentPriorHafalan::firstOrCreate(
                        ['student_id' => $prior->student_id, 'surah_id' => $prior->surah_id, 'ayah_start' => $from, 'ayah_end' => $to],
                        ['created_by' => $prior->created_by]
                    );
                }
                $prior->delete();
            }
        });
        $this->info('Tanda hafalan lama diperbaiki untuk '.count($trim).' rentang.');
        $this->line('Untuk membatalkan: php artisan tad:cek-hafalan-lama --pulihkan='.basename($backup));

        return self::SUCCESS;
    }

    private function backupDirectory(): string
    {
        $directory = storage_path('app/backups/hafalan-lama');
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    /** Ganti seluruh isi tabel hafalan lama dengan isi file cadangan. */
    private function restore(string $filename): int
    {
        $path = $this->backupDirectory().'/'.basename($filename);
        $rows = File::exists($path) ? json_decode(File::get($path), true) : null;
        if (! is_array($rows)) {
            $this->error('File cadangan tidak ditemukan/rusak: '.$path);
            $this->line('Cadangan yang ada: '.(collect(File::files($this->backupDirectory()))->map->getFilename()->implode(', ') ?: '-'));

            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm('Ganti isi hafalan sebelum aplikasi ('.StudentPriorHafalan::count().' rentang) dengan cadangan ini ('.count($rows).' rentang)?')) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows) {
            StudentPriorHafalan::query()->delete();
            foreach (array_chunk($rows, 500) as $chunk) {
                StudentPriorHafalan::query()->insert($chunk);
            }
        });
        $this->info('Dipulihkan: '.count($rows).' rentang hafalan sebelum aplikasi.');

        return self::SUCCESS;
    }
}
