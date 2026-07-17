<?php

namespace App\Observers;

use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;

class ModelAuditObserver
{
    public function created(Model $model): void
    {
        app(AuditLogService::class)->logCreated($model);
    }

    public function updated(Model $model): void
    {
        app(AuditLogService::class)->logUpdated($model);
    }

    public function deleted(Model $model): void
    {
        app(AuditLogService::class)->logDeleted($model);
    }

    public function restored(Model $model): void
    {
        app(AuditLogService::class)->logRestored($model);
    }

    public function forceDeleted(Model $model): void
    {
        app(AuditLogService::class)->logForceDeleted($model);
    }
}
