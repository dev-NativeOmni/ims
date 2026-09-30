<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\UmmiRecord;
use App\Services\UmmiTatapMukaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rapikan nomor Tatap Muka (TM) Ummi satu triwulan: TM = urutan pertemuan Ummi yang benar-benar
 * terjadi di triwulan itu, per halaqoh (lihat UmmiTatapMukaService). Setiap perubahan dicatat di
 * Audit Log (TM lama & baru).
 */
class RenumberUmmiTatapMuka extends Command
{
    protected $signature = 'tad:urutkan-tm-ummi
        {--tanggal= : Tanggal di dalam triwulan yang dirapikan (default: hari ini = triwulan aktif)}
        {--dry-run : Tampilkan perubahan tanpa menyimpan}
        {--force : Jalankan tanpa pertanyaan konfirmasi}';

    protected $description = 'Urutkan ulang TM Ummi dari pertemuan Ummi pertama di triwulan, per halaqoh.';

    public function handle(UmmiTatapMukaService $service): int
    {
        $date = $this->option('tanggal') ? Carbon::parse($this->option('tanggal')) : today();
        $changes = $service->renumberTerm($date, dryRun: true);

        if ($changes->isEmpty()) {
            $this->info('Semua TM Ummi di triwulan ini sudah urut.');

            return self::SUCCESS;
        }

        // Ringkas per halaqoh & tanggal pertemuan (satu tanggal = satu TM untuk semua murid halaqoh).
        $rows = $changes
            ->groupBy(fn ($c) => $c['record']->student?->class_room_id.'|'.$c['record']->student?->teacher_id.'|'.$c['record']->tanggal->toDateString())
            ->map(function ($items) {
                $student = $items->first()['record']->student;

                return [
                    $student?->classRoom?->name ?? '-',
                    $student?->teacher?->user?->name ?? '-',
                    $items->first()['record']->tanggal->toDateString(),
                    $items->pluck('old')->unique()->sort()->implode(', '),
                    $items->first()['new'],
                    $items->count(),
                ];
            })
            ->sortBy(fn ($r) => $r[0].'|'.$r[1].'|'.$r[2])
            ->values()
            ->all();

        $this->info("TM yang akan diurutkan ulang: {$changes->count()} setoran");
        $this->table(['Kelas', 'Halaqoh', 'Tanggal', 'TM lama', 'TM baru', 'Jumlah murid'], $rows);

        if ($this->option('dry-run')) {
            $this->warn('Mode uji coba: tidak ada data yang diubah.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Terapkan urutan TM baru pada {$changes->count()} setoran?")) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($service, $date) {
            foreach ($service->renumberTerm($date) as $change) {
                AuditLog::query()->create([
                    'user_name' => 'Sistem (tad:urutkan-tm-ummi)',
                    'action' => 'update',
                    'auditable_type' => UmmiRecord::class,
                    'auditable_id' => $change['record']->id,
                    'auditable_label' => 'Setoran Ummi',
                    'auditable_name' => $change['record']->student?->name,
                    'description' => "Urutkan TM Ummi: {$change['old']} -> {$change['new']}",
                    'old_values' => ['tatap_muka' => $change['old']],
                    'new_values' => ['tatap_muka' => $change['new']],
                ]);
            }
        });

        $this->info("{$changes->count()} TM Ummi diurutkan ulang. TM lama tercatat di Audit Log.");

        return self::SUCCESS;
    }
}
