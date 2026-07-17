<?php

namespace App\Policies;

use App\Models\HafalanTarget;
use App\Models\User;
use App\Services\UserAccessService;

class HafalanTargetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'parent', 'student', 'headmaster', 'supervisor', 'coordinator_tahfizh']);
    }

    public function view(User $user, HafalanTarget $hafalanTarget): bool
    {
        return $hafalanTarget->student
            && app(UserAccessService::class)->canViewStudent($user, $hafalanTarget->student);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function update(User $user, HafalanTarget $hafalanTarget): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if (! $user->hasRole('teacher')) {
            return false;
        }

        return $hafalanTarget->student
            && app(UserAccessService::class)->canViewStudent($user, $hafalanTarget->student);
    }

    public function delete(User $user, HafalanTarget $hafalanTarget): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if (! $user->hasRole('teacher')) {
            return false;
        }

        return $hafalanTarget->student
            && app(UserAccessService::class)->canViewStudent($user, $hafalanTarget->student);
    }
}
