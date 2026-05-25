<?php

namespace App\Services;

use App\Models\HafalanRecord;
use App\Models\HafalanTarget;

class HafalanTargetAutoCompletionService
{
    public function completeTargetsFromRecord(HafalanRecord $record): int
    {
        if ($record->status !== 'passed') {
            return 0;
        }

        if (
            ! $record->student_id ||
            ! $record->surah_id ||
            ! $record->ayah_start ||
            ! $record->ayah_end
        ) {
            return 0;
        }

        $completedAt = $record->submitted_at
            ? $record->submitted_at->copy()->endOfDay()
            : now();

        return HafalanTarget::query()
            ->where('student_id', $record->student_id)
            ->where('surah_id', $record->surah_id)
            ->where('status', 'active')
            ->where('ayah_start', '>=', $record->ayah_start)
            ->where('ayah_end', '<=', $record->ayah_end)
            ->update([
                'status' => 'completed',
                'completed_at' => $completedAt,
                'updated_at' => now(),
            ]);
    }

    public function syncExistingTargets(bool $dryRun = false): int
    {
        $matchedTargets = 0;

        HafalanTarget::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->chunkById(100, function ($targets) use (&$matchedTargets, $dryRun) {
                foreach ($targets as $target) {
                    $record = $this->matchingPassedRecordForTarget($target);

                    if (! $record) {
                        continue;
                    }

                    $matchedTargets++;

                    if ($dryRun) {
                        continue;
                    }

                    $target->update([
                        'status' => 'completed',
                        'completed_at' => $record->submitted_at
                            ? $record->submitted_at->copy()->endOfDay()
                            : now(),
                    ]);
                }
            });

        return $matchedTargets;
    }

    public function matchingPassedRecordForTarget(HafalanTarget $target): ?HafalanRecord
    {
        return HafalanRecord::query()
            ->where('student_id', $target->student_id)
            ->where('surah_id', $target->surah_id)
            ->where('status', 'passed')
            ->where('ayah_start', '<=', $target->ayah_start)
            ->where('ayah_end', '>=', $target->ayah_end)
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->first();
    }
}