<?php

namespace App\Observers;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StudentProgressCacheObserver
{
    public function saved(Model $model): void
    {
        $this->clearCache($model);
    }

    public function deleted(Model $model): void
    {
        $this->clearCache($model);
    }

    public function restored(Model $model): void
    {
        $this->clearCache($model);
    }

    private function clearCache(Model $model): void
    {
        $studentId = $model instanceof Student ? $model->id : ($model->student_id ?? null);
        if ($studentId) {
            Cache::forget("student_progress_calc_{$studentId}");
        }
    }
}

