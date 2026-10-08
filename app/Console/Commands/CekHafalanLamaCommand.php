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

/**
 * Setoran yang tertimpa "Hafalan Sebelum Aplikasi" (StudentPriorHafalan): ayatnya ikut ditandai hafalan
 * lama sehingga dianggap ulangan (0 baris). Yang mencurigakan = setoran yang diinput/diubah SESUDAH tanda
 * hafalan lama dibuat -- biasanya guru baru melengkapi/membetulkan setoran awal triwulan, padahal tanda
 * hafalan lama dibuat dari data lama (mis. tad:usul-hafalan-awal). --perbaiki memotong tanda hafalan lama
 * pada ayat setoran itu sehingga setoran kembali dihitung ayat baru. Bawaan hanya menampilkan.
 */
class CekHafalanLamaCommand extends Command
{
    protected $signature = 'tad:cek-hafalan-lama
        {--kelas= : Hanya kelas ini, mis. "XII F3"}
        {--murid=* : Hanya murid ini (nama, boleh sebagian; bisa diulang)}
        {--semua : Tampilkan juga setoran yang diinput sebelum tanda hafalan lama dibuat (ulangan hafalan lama yang wajar)}
        {--perbaiki : Potong tanda hafalan lama pada ayat setoran yang mencurigakan}
        {--force : Perbaiki tanpa pertanyaan konfirmasi}';

    protected $description = 'Cari setoran yang tertimpa tanda hafalan sebelum aplikasi (dihitung ulangan).';

    public function handle(HafalanProgressService $progress): int
    {
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

        return self::SUCCESS;
    }
}
