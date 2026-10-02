<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\UmmiRecordSurah;
use App\Support\UmmiAyah;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rapikan ayat hafalan Ummi yang melebihi jumlah ayat surahnya (mis. Al-Bayyinah "1-10" padahal
 * hanya 8 ayat): setiap angka di atas jumlah ayat diganti ayat terakhir surah ("1-10" -> "1-8").
 * Input baru sudah divalidasi; perintah ini untuk data lama. Setiap perubahan dicatat di Audit Log.
 */
class FixUmmiAyahOverflow extends Command
{
    protected $signature = 'tad:perbaiki-ayat-ummi
        {--dry-run : Tampilkan usulan koreksi tanpa mengubah data}
        {--force : Jalankan tanpa pertanyaan konfirmasi}';

    protected $description = 'Rapikan ayat hafalan Ummi yang melebihi jumlah ayat surah (mis. Al-Bayyinah 1-10 -> 1-8).';

    public function handle(): int
    {
        $fixes = UmmiRecordSurah::query()
            ->with(['surah:id,name_latin,total_ayah', 'ummiRecord:id,student_id,tanggal', 'ummiRecord.student:id,name'])
            ->whereNotNull('hafalan_ayah')
            ->get()
            ->filter(fn ($entry) => $entry->surah && UmmiAyah::maxNumber($entry->hafalan_ayah) > (int) $entry->surah->total_ayah)
            ->map(fn ($entry) => [
                'entry' => $entry,
                'new' => UmmiAyah::clamp((string) $entry->hafalan_ayah, (int) $entry->surah->total_ayah),
            ])
            ->values();

        if ($fixes->isEmpty()) {
            $this->info('Tidak ada ayat hafalan Ummi yang melebihi jumlah ayat surah.');

            return self::SUCCESS;
        }

        $this->table(['Murid', 'Tanggal', 'Surah (jumlah ayat)', 'Tersimpan', 'Menjadi'], $fixes->map(fn ($f) => [
            $f['entry']->ummiRecord?->student?->name ?? '-',
            $f['entry']->ummiRecord?->tanggal?->format('d/m/Y') ?? '-',
            $f['entry']->surah->name_latin.' ('.$f['entry']->surah->total_ayah.')',
            $f['entry']->hafalan_ayah,
            $f['new'],
        ])->all());

        if ($this->option('dry-run')) {
            $this->warn("Mode uji coba: {$fixes->count()} ayat akan dirapikan, belum ada yang diubah.");

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm("Rapikan {$fixes->count()} ayat hafalan Ummi di atas?")) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($fixes) {
            foreach ($fixes as $fix) {
                $entry = $fix['entry'];
                $old = $entry->hafalan_ayah;
                $entry->update(['hafalan_ayah' => $fix['new']]);
                AuditLog::query()->create([
                    'user_name' => 'Sistem (tad:perbaiki-ayat-ummi)',
                    'action' => 'update',
                    'auditable_type' => UmmiRecordSurah::class,
                    'auditable_id' => $entry->id,
                    'auditable_label' => 'Hafalan Setoran Ummi',
                    'auditable_name' => $entry->ummiRecord?->student?->name,
                    'description' => "Ayat {$entry->surah->name_latin} melebihi {$entry->surah->total_ayah} ayat: {$old} -> {$fix['new']}",
                    'old_values' => ['hafalan_ayah' => $old],
                    'new_values' => ['hafalan_ayah' => $fix['new']],
                ]);
            }
        });

        $this->info("{$fixes->count()} ayat hafalan Ummi dirapikan (tercatat di Audit Log).");

        return self::SUCCESS;
    }
}
