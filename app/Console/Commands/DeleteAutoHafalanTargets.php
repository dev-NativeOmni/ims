<?php

namespace App\Console\Commands;

use App\Models\HafalanTarget;
use Illuminate\Console\Command;

/**
 * Hapus permanen semua target hafalan buatan sistem (target otomatis lama, kolom auto_month
 * terisi), termasuk yang sudah dibuang ke sampah. Target buatan guru -- termasuk target otomatis
 * yang pernah diedit/disimpan ulang guru (auto_month kosong) -- tidak disentuh.
 */
class DeleteAutoHafalanTargets extends Command
{
    protected $signature = 'tad:hapus-target-otomatis
        {--dry-run : Tampilkan jumlah yang akan dihapus tanpa menghapus}
        {--force : Jalankan tanpa pertanyaan konfirmasi}';

    protected $description = 'Hapus permanen semua target hafalan buatan sistem (auto), sisakan target buatan guru.';

    public function handle(): int
    {
        $auto = HafalanTarget::withTrashed()->whereNotNull('auto_month');
        $total = (clone $auto)->count();
        $students = (clone $auto)->distinct()->count('student_id');
        $teacherTargets = HafalanTarget::query()->whereNull('auto_month')->count();

        $this->table(['Jenis', 'Jumlah'], [
            ['Target buatan sistem (akan dihapus)', "{$total} target, {$students} murid"],
            ['Target buatan guru (tetap)', "{$teacherTargets} target"],
        ]);

        if ($total === 0) {
            $this->info('Tidak ada target buatan sistem.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('Mode uji coba: tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Hapus permanen {$total} target buatan sistem?")) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        $deleted = 0;
        (clone $auto)->orderBy('id')->chunkById(200, function ($targets) use (&$deleted) {
            foreach ($targets as $target) {
                $target->forceDelete();
                $deleted++;
            }
        });

        $this->info("{$deleted} target buatan sistem dihapus. Target buatan guru tetap: ".HafalanTarget::query()->whereNull('auto_month')->count().'.');

        return self::SUCCESS;
    }
}
