<?php

namespace App\Policies;

use App\Models\HafalanRecord;
use App\Models\User;
use App\Services\UserAccessService;

class HafalanRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'parent', 'student', 'headmaster', 'supervisor', 'coordinator_tahfizh']);
    }

    public function view(User $user, HafalanRecord $hafalanRecord): bool
    {
        return $hafalanRecord->student
            && app(UserAccessService::class)->canViewStudent($user, $hafalanRecord->student);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function update(User $user, HafalanRecord $hafalanRecord): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if (! $user->hasRole('teacher')) {
            return false;
        }

        return (int) $hafalanRecord->teacher_id === (int) $user->teacherProfile?->id;
    }

    public function delete(User $user, HafalanRecord $hafalanRecord): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if (! $user->hasRole('teacher')) {
            return false;
        }

        return (int) $hafalanRecord->teacher_id === (int) $user->teacherProfile?->id;
    }
}
