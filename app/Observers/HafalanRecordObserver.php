<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\HafalanRecord;
use Illuminate\Support\Arr;

class HafalanRecordObserver
{
    public function created(HafalanRecord $hafalanRecord): void
    {
        $this->writeLog(
            hafalanRecord: $hafalanRecord,
            event: 'created',
            oldValues: null,
            newValues: Arr::only($hafalanRecord->getAttributes(), $this->trackedFields())
        );
    }

    public function updated(HafalanRecord $hafalanRecord): void
    {
        $changes = Arr::except($hafalanRecord->getChanges(), [
            'updated_at',
        ]);

        if ($changes === []) {
            return;
        }

        $changedKeys = array_keys($changes);

        $oldValues = Arr::only($hafalanRecord->getOriginal(), $changedKeys);
        $newValues = Arr::only($hafalanRecord->getAttributes(), $changedKeys);

        $this->writeLog(
            hafalanRecord: $hafalanRecord,
            event: 'updated',
            oldValues: $oldValues,
            newValues: $newValues
        );
    }

    public function deleted(HafalanRecord $hafalanRecord): void
    {
        $this->writeLog(
            hafalanRecord: $hafalanRecord,
            event: 'deleted',
            oldValues: Arr::only($hafalanRecord->getOriginal(), $this->trackedFields()),
            newValues: Arr::only($hafalanRecord->getAttributes(), $this->trackedFields())
        );
    }

    public function restored(HafalanRecord $hafalanRecord): void
    {
        $this->writeLog(
            hafalanRecord: $hafalanRecord,
            event: 'restored',
            oldValues: Arr::only($hafalanRecord->getOriginal(), $this->trackedFields()),
            newValues: Arr::only($hafalanRecord->getAttributes(), $this->trackedFields())
        );
    }

    private function writeLog(
        HafalanRecord $hafalanRecord,
        string $event,
        ?array $oldValues,
        ?array $newValues
    ): void {
        $runningInConsole = app()->runningInConsole();

        AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => $hafalanRecord->getMorphClass(),
            'auditable_id' => $hafalanRecord->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => $runningInConsole ? null : request()->fullUrl(),
            'ip_address' => $runningInConsole ? null : request()->ip(),
            'user_agent' => $runningInConsole ? null : request()->userAgent(),
        ]);
    }

    private function trackedFields(): array
    {
        return [
            'id',
            'student_id',
            'teacher_id',
            'surah_id',
            'ayah_start',
            'ayah_end',
            'submission_type',
            'score',
            'status',
            'notes',
            'submitted_at',
            'deleted_at',
        ];
    }
}