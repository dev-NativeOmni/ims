<?php

namespace App\Console\Commands;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\MurajaahRecord;
use App\Models\Student;
use Illuminate\Console\Command;

/**
 * Cek data sebelum/sesudah pemisahan menu Metode Ummi. Hanya membaca, tidak mengubah data:
 * - murid Kelas 10 yang belum berlevel Ummi (semua Kelas 10 seharusnya Ummi);
 * - setoran & muraja'ah lama Kelas 10 di luar Program Tahfizh (sekarang hanya hafalan Ummi).
 */
class CheckGradeTenUmmi extends Command
{
    protected $signature = 'tad:cek-kelas-10';

    protected $description = "Tampilkan murid Kelas 10 non-Ummi dan setoran/muraja'ah lama Kelas 10 di luar Program Tahfizh.";

    public function handle(): int
    {
        $gradeTen = ClassRoom::query()->with('program')->get()->filter(fn (ClassRoom $c) => $c->isGradeTen());

        $nonUmmi = Student::query()->with('classRoom')
            ->where('status', 'active')
            ->whereIn('class_room_id', $gradeTen->pluck('id'))
            ->where(fn ($q) => $q->whereNull('tahfizh_level')->orWhere('tahfizh_level', '!=', 'ummi'))
            ->orderBy('name')
            ->get();

        $this->info("Murid Kelas 10 non-Ummi: {$nonUmmi->count()}");
        if ($nonUmmi->isNotEmpty()) {
            $this->table(['Id', 'Nama', 'Kelas', 'Level'], $nonUmmi->map(fn (Student $s) => [$s->id, $s->name, $s->classRoom?->name, $s->tahfizh_level ?? '-'])->all());
        }

        $outsideTahfizh = $gradeTen->reject(fn (ClassRoom $c) => $c->hasMandiriSheets())->pluck('id');
        $inClass = fn ($q) => $q->whereIn('class_room_id', $outsideTahfizh);
        $this->info('Setoran lama Kelas 10 di luar Program Tahfizh: '.HafalanRecord::query()->whereHas('student', $inClass)->count());
        $this->info("Muraja'ah lama Kelas 10 di luar Program Tahfizh: ".MurajaahRecord::query()->whereHas('student', $inClass)->count());

        return self::SUCCESS;
    }
}
