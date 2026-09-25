<?php

namespace App\Services;

use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;

class HafalanTargetAutoCompletionService
{
    /**
     * Tandai target guru (surah & ayat) yang sudah tercapai menjadi completed dengan aturan yang
     * sama dengan Target Triwulan: semua ayat dari setoran pertama triwulan target sampai ayat
     * target sudah lulus disetor (HafalanProgressService::evaluate).
     */
    public function syncExistingTargets(bool $dryRun = false): int
    {
        $matchedTargets = 0;
        $calendar = app(AcademicCalendarService::class);
        $progress = app(HafalanProgressService::class);

        HafalanTarget::query()
            ->with(['student', 'surah'])
            ->where('status', 'active')
            ->whereNotNull('surah_id')
            ->orderBy('id')
            ->chunkById(100, function ($targets) use (&$matchedTargets, $dryRun, $calendar, $progress) {
                foreach ($targets as $target) {
                    if (! $target->student || ! $target->surah || ! $target->target_date) {
                        continue;
                    }

                    $months = $calendar->termMonths($target->target_date);
                    $reached = $progress->evaluate(
                        $target->student, (int) $target->surah->number, (int) $target->ayah,
                        reset($months)['start'], end($months)['end'], now()
                    )['position_reached'];

                    if (! $reached) {
                        continue;
                    }

                    $matchedTargets++;

                    if (! $dryRun) {
                        $target->update([
                            'status' => 'completed',
                            'completed_at' => $this->matchingPassedRecordForTarget($target)?->hafalanRecord?->submitted_at?->copy()->endOfDay() ?? now(),
                        ]);
                    }
                }
            });

        return $matchedTargets;
    }

    public function matchingPassedRecordForTarget(HafalanTarget $target): ?HafalanRecordSurah
    {
        return HafalanRecordSurah::query()
            ->select('hafalan_record_surahs.*')
            ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
            ->whereNull('hafalan_records.deleted_at')
            ->where('hafalan_records.student_id', $target->student_id)
            ->where('hafalan_record_surahs.surah_id', $target->surah_id)
            ->where('hafalan_record_surahs.status', 'passed')
            ->where('hafalan_record_surahs.ayah_end', '>=', $target->ayah)
            ->with('hafalanRecord')
            ->orderBy('hafalan_records.submitted_at')
            ->orderBy('hafalan_record_surahs.id')
            ->first();
    }
}
