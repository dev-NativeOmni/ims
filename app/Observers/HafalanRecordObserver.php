<?php

namespace App\Observers;

use App\Models\HafalanRecord;
use App\Services\HafalanTargetAutoCompletionService;

class HafalanRecordObserver
{
    public function created(HafalanRecord $hafalanRecord): void
    {
        app(HafalanTargetAutoCompletionService::class)
            ->completeTargetsFromRecord($hafalanRecord);
    }

    public function updated(HafalanRecord $hafalanRecord): void
    {
        if (
            $hafalanRecord->wasChanged('student_id') ||
            $hafalanRecord->wasChanged('surah_id') ||
            $hafalanRecord->wasChanged('ayah_start') ||
            $hafalanRecord->wasChanged('ayah_end') ||
            $hafalanRecord->wasChanged('status') ||
            $hafalanRecord->wasChanged('submitted_at')
        ) {
            app(HafalanTargetAutoCompletionService::class)
                ->completeTargetsFromRecord($hafalanRecord);
        }
    }

    public function deleted(HafalanRecord $hafalanRecord): void
    {
        //
    }

    public function restored(HafalanRecord $hafalanRecord): void
    {
        app(HafalanTargetAutoCompletionService::class)
            ->completeTargetsFromRecord($hafalanRecord);
    }

    public function forceDeleted(HafalanRecord $hafalanRecord): void
    {
        //
    }
}