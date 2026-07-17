<?php

namespace App\Policies;

use App\Models\MurajaahRecord;
use App\Models\User;
use App\Services\UserAccessService;

class MurajaahRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'parent', 'student', 'headmaster', 'supervisor', 'coordinator_tahfizh']);
    }

    public function view(User $user, MurajaahRecord $murajaahRecord): bool
    {
        return $murajaahRecord->student
            && app(UserAccessService::class)->canViewStudent($user, $murajaahRecord->student);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function update(User $user, MurajaahRecord $murajaahRecord): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if (! $user->hasRole('teacher')) {
            return false;
        }

        return (int) $murajaahRecord->teacher_id === (int) $user->teacherProfile?->id;
    }

    public function delete(User $user, MurajaahRecord $murajaahRecord): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if (! $user->hasRole('teacher')) {
            return false;
        }

        return (int) $murajaahRecord->teacher_id === (int) $user->teacherProfile?->id;
    }
}
