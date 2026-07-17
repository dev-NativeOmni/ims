<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

class UserAccessService
{
    public function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function canManageAcademicRecords(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function canViewStudent(User $user, Student $student): bool
    {
        if ($this->isAdmin($user) || $user->hasAnyRole(['headmaster', 'supervisor', 'coordinator_tahfizh'])) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            $teacherProfile = $user->teacherProfile;

            return $teacherProfile
                && (int) $student->teacher_id === (int) $teacherProfile->id;
        }

        if ($user->hasRole('parent')) {
            $parentProfile = $user->parentProfile;

            if (! $parentProfile) {
                return false;
            }

            return $student->parents()
                ->where('parent_profiles.id', $parentProfile->id)
                ->exists();
        }

        if ($user->hasRole('student')) {
            return (int) $student->user_id === (int) $user->id;
        }

        return false;
    }

    public function visibleStudentIds(User $user): Collection
    {
        if ($this->isAdmin($user) || $user->hasAnyRole(['headmaster', 'supervisor', 'coordinator_tahfizh'])) {
            return Student::query()
                ->pluck('id');
        }

        if ($user->hasRole('teacher')) {
            $teacherProfile = $user->teacherProfile;

            if (! $teacherProfile) {
                return collect();
            }

            return Student::query()
                ->where('teacher_id', $teacherProfile->id)
                ->pluck('id');
        }

        if ($user->hasRole('parent')) {
            $parentProfile = $user->parentProfile;

            if (! $parentProfile) {
                return collect();
            }

            return $parentProfile->students()
                ->pluck('students.id');
        }

        if ($user->hasRole('student')) {
            return Student::query()
                ->where('user_id', $user->id)
                ->pluck('id');
        }

        return collect();
    }
}
