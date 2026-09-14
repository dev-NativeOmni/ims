<?php

namespace App\Services;

use App\Models\HafalanRecord;
use App\Models\HafalanTarget;

class HafalanTargetAutoCompletionService
{
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
            ->where('ayah_end', '>=', $target->ayah)
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->first();
    }
}
