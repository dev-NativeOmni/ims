<?php

namespace App\Console\Commands;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Services\AutoHafalanTargetService;
use App\Services\HafalanProgressService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Isi Target Triwulan (Kelas 11 & 12) untuk murid satu guru pengampu di kelas-kelas tertentu,
 * memakai rumus aplikasi: dari setoran pertama triwulan, maju sejauh pertemuan aktif x baris per
 * level (kumulatif per bulan) dengan kalkulator baris -- lihat
 * AutoHafalanTargetService::suggestedTermTargets(). Disimpan sama seperti menu Target Triwulan
 * (target guru, deadline = hari aktif terakhir bulan). Target yang sudah diisi guru tidak
 * ditimpa kecuali --timpa.
 */
class FillTermTargets extends Command
{
    protected $signature = 'tad:isi-target-triwulan
        {--guru= : Sebagian nama guru pengampu, mis. "Fuad"}
        {--kelas=* : Nama kelas persis, bisa berulang, mis. --kelas="XI F3" --kelas="XII F3"}
        {--periode= : Tanggal di dalam triwulan (default: hari ini = triwulan aktif)}
        {--timpa : Timpa target bulan yang sudah diisi guru}
        {--dry-run : Tampilkan target tanpa menyimpan}
        {--force : Jalankan tanpa pertanyaan konfirmasi}';

    protected $description = 'Isi Target Triwulan murid satu guru dari rumus pertemuan x baris per level (kalkulator baris).';

    public function handle(AutoHafalanTargetService $service): int
    {
        $teachers = TeacherProfile::query()
            ->with('user:id,name')
            ->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.$this->option('guru').'%'))
            ->get();
        if (blank($this->option('guru')) || $teachers->count() !== 1) {
            $this->error($teachers->isEmpty()
                ? 'Guru tidak ditemukan. Isi --guru dengan sebagian nama guru pengampu.'
                : 'Nama guru cocok lebih dari satu: '.$teachers->pluck('user.name')->implode(', ').'. Pakai nama yang lebih lengkap.');

            return self::FAILURE;
        }
        $teacher = $teachers->first();

        $classes = ClassRoom::query()->with('program')->whereIn('name', (array) $this->option('kelas'))->get();
        $missing = array_diff((array) $this->option('kelas'), $classes->pluck('name')->all());
        if ($classes->isEmpty() || $missing !== []) {
            $this->error('Kelas tidak ditemukan: '.($missing ? implode(', ', $missing) : '(isi --kelas)'));

            return self::FAILURE;
        }

        $date = $this->option('periode') ? Carbon::parse($this->option('periode')) : today();
        $plans = [];
        foreach ($classes as $classRoom) {
            if ($classRoom->isGradeTen()) {
                $this->warn("{$classRoom->name}: Kelas 10 memakai Target Ummi, dilewati.");

                continue;
            }
            $months = $service->termMonths($classRoom, $date);
            $students = Student::query()
                ->where('class_room_id', $classRoom->id)
                ->where('teacher_id', $teacher->id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->each(fn (Student $s) => $s->setRelation('classRoom', $classRoom));

            foreach ($students as $student) {
                $suggestion = $service->suggestedTermTargets($student, $months);
                foreach ($months as $monthKey => $month) {
                    $target = $suggestion['months'][$monthKey];
                    $existing = $service->storedTermTargets($student, $month['start'], $month['end'])->last();
                    $plans[] = [
                        'class' => $classRoom,
                        'student' => $student,
                        'start' => $suggestion['start'],
                        'monthKey' => $monthKey,
                        'month' => $month,
                        'months' => $months,
                        'target' => $target,
                        'existing' => $existing,
                        'action' => match (true) {
                            $target === null || $target['surah'] === null => 'lewati (tidak ada pertemuan)',
                            $existing && ! $this->option('timpa') => 'lewati (sudah diisi guru)',
                            $existing && (int) $existing->surah_id === $target['surah']->id && (int) $existing->ayah === $target['ayah'] => 'sama',
                            (bool) $existing => 'timpa',
                            default => 'baru',
                        },
                    ];
                }
            }
        }

        $surahs = app(HafalanProgressService::class)->surahs();
        $this->table(
            ['Kelas', 'Murid', 'Level', 'Awal triwulan', 'Bulan', 'Target baris', 'Target', 'Tersimpan', 'Aksi'],
            collect($plans)->map(fn ($p) => [
                $p['class']->name,
                $p['student']->name,
                ucfirst($p['student']->tahfizh_level ?? '-'),
                ($surahs->get($p['start']['surah'])?->name_latin ?? '-').' '.$p['start']['ayah'].($p['start']['source'] === 'first_setoran' ? '' : ' ('.$p['start']['source'].')'),
                $p['month']['label'],
                $p['target']['lines'] ?? '-',
                $p['target'] && $p['target']['surah'] ? "{$p['target']['surah']->name_latin} {$p['target']['ayah']}" : '-',
                $p['existing'] ? ($p['existing']->surah?->name_latin ?? '-').' '.$p['existing']->ayah : '-',
                $p['action'],
            ])->all()
        );

        $toSave = collect($plans)->whereIn('action', ['baru', 'timpa'])->values();
        $this->info("Target baru: {$toSave->where('action', 'baru')->count()}, ditimpa: {$toSave->where('action', 'timpa')->count()}.");

        if ($toSave->isEmpty() || $this->option('dry-run')) {
            if ($this->option('dry-run')) {
                $this->warn('Mode uji coba: tidak ada target yang disimpan.');
            }

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Simpan {$toSave->count()} target?")) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($toSave, $service, $teacher) {
            foreach ($toSave as $p) {
                $attributes = [
                    'surah_id' => $p['target']['surah']->id,
                    'ayah' => $p['target']['ayah'],
                    'target_date' => $p['existing']?->deadline_manual ? $p['existing']->target_date->toDateString() : $p['month']['deadline']->toDateString(),
                    'auto_month' => null,
                ];
                $target = $p['existing'];
                if ($target) {
                    $target->update($attributes);
                } else {
                    $target = HafalanTarget::create($attributes + [
                        'student_id' => $p['student']->id,
                        'teacher_id' => $teacher->id,
                        'status' => 'active',
                    ]);
                }
                $service->refreshStatus($target, reset($p['months'])['start'], end($p['months'])['end']);
            }
        });

        $this->info("{$toSave->count()} target disimpan. Bisa dicek & diubah di menu Target Triwulan.");

        return self::SUCCESS;
    }
}
