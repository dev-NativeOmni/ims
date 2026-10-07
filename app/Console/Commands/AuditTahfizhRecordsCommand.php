<?php

namespace App\Console\Commands;

use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Services\AcademicCalendarService;
use App\Services\HafalanTargetAutoCompletionService;
use App\Services\TargetDeadlineService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AuditTahfizhRecordsCommand extends Command
{
    protected $signature = 'tahfizh:audit-sync {--fix : Otomatis sinkronisasi dan perbaiki data target yang tidak konsisten}';

    protected $description = 'Audit kesehatan data tahfizh: sinkronisasi status target bulanan/triwulan, deteksi santri tanpa target, dan selaraskan deadline.';

    public function handle(
        HafalanTargetAutoCompletionService $autoCompletionService,
        TargetDeadlineService $deadlineService,
        AcademicCalendarService $calendarService
    ): int {
        $fix = (bool) $this->option('fix');

        $this->info($fix
            ? '🚀 Memulai Audit & Sinkronisasi Data Tahfizh...'
            : '🔍 Memulai Audit Data Tahfizh (Mode Read-Only / Preview)...'
        );
        $this->newLine();

        // 1. Selaraskan Deadline Target dengan Kalender Akademik
        $this->info('📅 Memeriksa keselarasan deadline target bulanan...');
        if ($fix) {
            $deadlineService->syncAll();
            $this->line('   <info>✓</info> Deadline target telah diselaraskan dengan hari aktif terakhir kalender akademik.');
        } else {
            $this->line('   <comment>-</comment> Mode preview: lewati perubahan deadline.');
        }

        // 2. Sinkronisasi Status Target Aktif
        $this->info('🎯 Memeriksa kelengkapan capaian setoran terhadap target aktif...');
        $syncCounts = $autoCompletionService->syncExistingTargets(! $fix);
        $this->line("   <info>✓</info> Target Selesai: {$syncCounts['completed']} | Target Terlewat: {$syncCounts['missed']}");

        // 3. Deteksi Santri Aktif Tanpa Target di Bulan Berjalan
        $this->newLine();
        $this->info('👥 Memeriksa santri aktif yang belum memiliki target bulan ini...');

        $today = Carbon::today();
        $currentMonthStart = $today->copy()->startOfMonth()->toDateString();
        $currentMonthEnd = $today->copy()->endOfMonth()->toDateString();

        $activeStudents = Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $studentsWithTargetIds = HafalanTarget::query()
            ->whereBetween('target_date', [$currentMonthStart, $currentMonthEnd])
            ->pluck('student_id')
            ->unique()
            ->toArray();

        $studentsWithoutTarget = $activeStudents->whereNotIn('id', $studentsWithTargetIds);

        if ($studentsWithoutTarget->isNotEmpty()) {
            $this->warn("   ⚠️  Ditemukan {$studentsWithoutTarget->count()} santri aktif tanpa target di bulan {$today->translatedFormat('F Y')}:");

            $tableData = $studentsWithoutTarget->take(20)->map(fn ($s) => [
                'ID' => $s->id,
                'NIS' => $s->student_number ?? '-',
                'Nama' => $s->name,
                'Kelas' => $s->classRoom?->name ?? '-',
                'Guru Pengampu' => $s->teacher?->user?->name ?? '-',
            ])->toArray();

            $this->table(['ID', 'NIS', 'Nama Santri', 'Kelas', 'Guru Pengampu / Halaqah'], $tableData);

            if ($studentsWithoutTarget->count() > 20) {
                $this->line('   ... dan '.($studentsWithoutTarget->count() - 20).' santri lainnya.');
            }
        } else {
            $this->line('   <info>✓</info> Alhamdulillah! Seluruh santri aktif telah memiliki target bulan ini.');
        }

        // 4. Ringkasan Statistik Target & Setoran
        $this->newLine();
        $this->info('📊 Ringkasan Statistik Data Tahfizh:');

        $totalActiveTargets = HafalanTarget::where('status', 'active')->count();
        $totalCompletedTargets = HafalanTarget::where('status', 'completed')->count();
        $totalMissedTargets = HafalanTarget::where('status', 'missed')->count();
        $totalPassedSetoran = HafalanRecordSurah::where('status', 'passed')->count();

        $this->table(['Indikator', 'Jumlah'], [
            ['Total Santri Aktif', $activeStudents->count()],
            ['Santri Tanpa Target Bulan Ini', $studentsWithoutTarget->count()],
            ['Target Status Aktif', $totalActiveTargets],
            ['Target Status Selesai (Completed)', $totalCompletedTargets],
            ['Target Status Terlewat (Missed)', $totalMissedTargets],
            ['Total Setoran Berstatus Passed (Lulus)', $totalPassedSetoran],
        ]);

        $this->newLine();
        if (! $fix) {
            $this->comment('💡 Tip: Jalankan `php artisan tahfizh:audit-sync --fix` untuk otomatis menerapkan sinkronisasi dan penyesuaian deadline.');
        } else {
            $this->info('✅ Audit & Sinkronisasi Tahfizh selesai dengan sukses!');
        }

        return self::SUCCESS;
    }
}
