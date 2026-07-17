<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use App\Services\UserAccessService;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'parent', 'student', 'headmaster', 'supervisor', 'coordinator_tahfizh']);
    }

    public function view(User $user, Student $student): bool
    {
        return app(UserAccessService::class)->canViewStudent($user, $student);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, Student $student): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function restore(User $user, Student $student): bool
    {
        return $user->hasAnyRole(['super_admin']);
    }

    public function forceDelete(User $user, Student $student): bool
    {
        return $user->hasAnyRole(['super_admin']);
    }
}
