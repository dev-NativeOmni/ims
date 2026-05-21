<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\ParentProfile;
use App\Models\Program;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly HafalanProgressService $progressService
    ) {
        //
    }

    public function adminStats(): array
    {
        $today = now()->toDateString();

        $activeStudents = Student::query()
            ->with([
                'classRoom.program',
                'teacher.user',
            ])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return [
            'total_students' => Student::query()->count(),
            'active_students' => Student::query()->where('status', 'active')->count(),
            'inactive_students' => Student::query()->where('status', 'inactive')->count(),
            'graduated_students' => Student::query()->where('status', 'graduated')->count(),

            'total_teachers' => TeacherProfile::query()->count(),
            'total_parents' => ParentProfile::query()->count(),
            'total_programs' => Program::query()->count(),
            'total_class_rooms' => ClassRoom::query()->count(),

            'hafalan_today' => HafalanRecord::query()
                ->whereDate('submitted_at', $today)
                ->count(),

            'murajaah_today' => MurajaahRecord::query()
                ->whereDate('reviewed_at', $today)
                ->count(),

            'active_targets' => HafalanTarget::query()
                ->where('status', 'active')
                ->count(),

            'overdue_targets' => HafalanTarget::query()
                ->where('status', 'active')
                ->whereDate('target_date', '<', $today)
                ->count(),

            'completed_targets' => HafalanTarget::query()
                ->where('status', 'completed')
                ->count(),

            'hafalan_need_attention' => HafalanRecord::query()
                ->whereIn('status', [
                    'repeat',
                    'needs_improvement',
                ])
                ->count(),

            'murajaah_need_attention' => MurajaahRecord::query()
                ->whereIn('status', [
                    'repeat',
                    'needs_improvement',
                ])
                ->count(),

            'latest_targets' => HafalanTarget::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surah',
                ])
                ->orderBy('target_date')
                ->latest()
                ->limit(8)
                ->get(),

            'latest_hafalan_records' => HafalanRecord::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surah',
                ])
                ->latest('submitted_at')
                ->latest()
                ->limit(5)
                ->get(),

            'latest_murajaah_records' => MurajaahRecord::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surah',
                ])
                ->latest('reviewed_at')
                ->latest()
                ->limit(5)
                ->get(),

            'students_progress' => $this->studentsProgress($activeStudents)->take(10),
        ];
    }

    public function teacherStats(User $user): array
    {
        $teacher = $user->teacherProfile;

        if (! $teacher) {
            return [
                'teacher' => null,
                'total_students' => 0,
                'hafalan_today' => 0,
                'murajaah_today' => 0,
                'active_targets' => 0,
                'overdue_targets' => 0,
                'completed_targets' => 0,
                'hafalan_need_attention' => 0,
                'murajaah_need_attention' => 0,
                'latest_targets' => collect(),
                'latest_hafalan_records' => collect(),
                'latest_murajaah_records' => collect(),
                'students_progress' => collect(),
            ];
        }

        $today = now()->toDateString();

        $students = Student::query()
            ->with([
                'classRoom.program',
                'teacher.user',
            ])
            ->where('teacher_id', $teacher->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');

        return [
            'teacher' => $teacher,
            'total_students' => $students->count(),

            'hafalan_today' => HafalanRecord::query()
                ->whereIn('student_id', $studentIds)
                ->whereDate('submitted_at', $today)
                ->count(),

            'murajaah_today' => MurajaahRecord::query()
                ->whereIn('student_id', $studentIds)
                ->whereDate('reviewed_at', $today)
                ->count(),

            'active_targets' => HafalanTarget::query()
                ->whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->count(),

            'overdue_targets' => HafalanTarget::query()
                ->whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->whereDate('target_date', '<', $today)
                ->count(),

            'completed_targets' => HafalanTarget::query()
                ->whereIn('student_id', $studentIds)
                ->where('status', 'completed')
                ->count(),

            'hafalan_need_attention' => HafalanRecord::query()
                ->whereIn('student_id', $studentIds)
                ->whereIn('status', [
                    'repeat',
                    'needs_improvement',
                ])
                ->count(),

            'murajaah_need_attention' => MurajaahRecord::query()
                ->whereIn('student_id', $studentIds)
                ->whereIn('status', [
                    'repeat',
                    'needs_improvement',
                ])
                ->count(),

            'latest_targets' => HafalanTarget::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surah',
                ])
                ->whereIn('student_id', $studentIds)
                ->orderBy('target_date')
                ->latest()
                ->limit(8)
                ->get(),

            'latest_hafalan_records' => HafalanRecord::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surah',
                ])
                ->whereIn('student_id', $studentIds)
                ->latest('submitted_at')
                ->latest()
                ->limit(5)
                ->get(),

            'latest_murajaah_records' => MurajaahRecord::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surah',
                ])
                ->whereIn('student_id', $studentIds)
                ->latest('reviewed_at')
                ->latest()
                ->limit(5)
                ->get(),

            'students_progress' => $this->studentsProgress($students),
        ];
    }

    public function parentStats(User $user): array
    {
        $parent = $user->parentProfile;

        if (! $parent) {
            return [
                'parent' => null,
                'children' => collect(),
                'children_progress' => collect(),
                'total_children' => 0,
                'active_targets' => 0,
                'overdue_targets' => 0,
                'latest_targets' => collect(),
                'latest_hafalan_records' => collect(),
                'latest_murajaah_records' => collect(),
            ];
        }

        $today = now()->toDateString();

        $children = $parent->students()
            ->with([
                'classRoom.program',
                'teacher.user',
            ])
            ->where('students.status', 'active')
            ->orderBy('students.name')
            ->get();

        $studentIds = $children->pluck('id');

        return [
            'parent' => $parent,
            'children' => $children,
            'children_progress' => $this->studentsProgress($children),
            'total_children' => $children->count(),

            'active_targets' => HafalanTarget::query()
                ->whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->count(),

            'overdue_targets' => HafalanTarget::query()
                ->whereIn('student_id', $studentIds)
                ->where('status', 'active')
                ->whereDate('target_date', '<', $today)
                ->count(),

            'latest_targets' => HafalanTarget::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surah',
                ])
                ->whereIn('student_id', $studentIds)
                ->orderBy('target_date')
                ->latest()
                ->limit(8)
                ->get(),

            'latest_hafalan_records' => HafalanRecord::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surah',
                ])
                ->whereIn('student_id', $studentIds)
                ->latest('submitted_at')
                ->latest()
                ->limit(8)
                ->get(),

            'latest_murajaah_records' => MurajaahRecord::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surah',
                ])
                ->whereIn('student_id', $studentIds)
                ->latest('reviewed_at')
                ->latest()
                ->limit(8)
                ->get(),
        ];
    }

    public function studentStats(User $user): array
    {
        $student = $user->studentProfile;

        if (! $student) {
            return [
                'student' => null,
                'summary' => null,
                'active_targets' => collect(),
                'overdue_targets' => collect(),
                'latest_hafalan_records' => collect(),
                'latest_murajaah_records' => collect(),
            ];
        }

        $student->load([
            'classRoom.program',
            'teacher.user',
            'parents.user',
        ]);

        $today = now()->toDateString();

        return [
            'student' => $student,
            'summary' => $this->progressService->summary($student),

            'active_targets' => HafalanTarget::query()
                ->with([
                    'surah',
                    'teacher.user',
                ])
                ->where('student_id', $student->id)
                ->where('status', 'active')
                ->orderBy('target_date')
                ->get(),

            'overdue_targets' => HafalanTarget::query()
                ->with([
                    'surah',
                    'teacher.user',
                ])
                ->where('student_id', $student->id)
                ->where('status', 'active')
                ->whereDate('target_date', '<', $today)
                ->orderBy('target_date')
                ->get(),

            'latest_hafalan_records' => $student->hafalanRecords()
                ->with([
                    'teacher.user',
                    'surah',
                ])
                ->latest('submitted_at')
                ->latest()
                ->limit(8)
                ->get(),

            'latest_murajaah_records' => $student->murajaahRecords()
                ->with([
                    'teacher.user',
                    'surah',
                ])
                ->latest('reviewed_at')
                ->latest()
                ->limit(8)
                ->get(),
        ];
    }

    private function studentsProgress(Collection $students): Collection
    {
        return $students->map(function (Student $student) {
            return [
                'student' => $student,
                'memorized_ayah_count' => $this->progressService->memorizedAyahCount($student),
                'progress_percentage' => $this->progressService->progressPercentage($student),
                'active_target_count' => HafalanTarget::query()
                    ->where('student_id', $student->id)
                    ->where('status', 'active')
                    ->count(),
                'overdue_target_count' => HafalanTarget::query()
                    ->where('student_id', $student->id)
                    ->where('status', 'active')
                    ->whereDate('target_date', '<', now()->toDateString())
                    ->count(),
            ];
        })->sortByDesc('progress_percentage')->values();
    }
}