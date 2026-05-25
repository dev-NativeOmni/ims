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
        private readonly StudentProgressService $studentProgressService,
        private readonly StudentMotivationService $studentMotivationService
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

            'latest_hafalan_records' => HafalanRecord::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surah',
                ])
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
                ->latest('reviewed_at')
                ->latest()
                ->limit(8)
                ->get(),

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

            'students_progress' => $this->studentsProgress($activeStudents)->take(10),
        ];
    }

    public function teacherStats(User $user): array
    {
        $teacher = $user->teacherProfile;

        if (! $teacher) {
            return [
                'teacher' => null,
                'students' => collect(),
                'students_progress' => collect(),
                'total_students' => 0,
                'active_targets' => 0,
                'overdue_targets' => 0,
                'latest_targets' => collect(),
                'latest_hafalan_records' => collect(),
                'latest_murajaah_records' => collect(),
            ];
        }

        $today = now()->toDateString();

        $students = $teacher->students()
            ->with([
                'classRoom.program',
                'teacher.user',
            ])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');

        return [
            'teacher' => $teacher,
            'students' => $students,
            'students_progress' => $this->studentsProgress($students),
            'total_students' => $students->count(),

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

    public function parentStats(User $user): array
    {
        $parent = $user->parentProfile;

        if (! $parent) {
            return [
                'parent' => null,
                'children' => collect(),
                'children_progress' => collect(),
                'children_motivation' => collect(),
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
            'children_motivation' => $this->studentsMotivation($children),
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
                'progress' => [],
                'summary' => [],
                'motivation' => [],
                'active_targets' => collect(),
                'overdue_targets' => collect(),
                'latest_targets' => collect(),
                'latest_hafalan_records' => collect(),
                'latest_murajaah_records' => collect(),
            ];
        }

        $student->load([
            'user',
            'classRoom.program',
            'teacher.user',
            'parents.user',
        ]);

        $today = now()->toDateString();
        $progress = $this->studentProgressService->calculate($student);

        $activeTargets = HafalanTarget::query()
            ->with([
                'surah',
                'teacher.user',
            ])
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->orderBy('target_date')
            ->get();

        $overdueTargets = HafalanTarget::query()
            ->with([
                'surah',
                'teacher.user',
            ])
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->whereDate('target_date', '<', $today)
            ->orderBy('target_date')
            ->get();

        $latestTargets = HafalanTarget::query()
            ->with([
                'surah',
                'teacher.user',
            ])
            ->where('student_id', $student->id)
            ->orderBy('target_date')
            ->latest()
            ->limit(8)
            ->get();

        $latestHafalanRecords = HafalanRecord::query()
            ->with([
                'teacher.user',
                'surah',
            ])
            ->where('student_id', $student->id)
            ->latest('submitted_at')
            ->latest()
            ->limit(8)
            ->get();

        $latestMurajaahRecords = MurajaahRecord::query()
            ->with([
                'teacher.user',
                'surah',
            ])
            ->where('student_id', $student->id)
            ->latest('reviewed_at')
            ->latest()
            ->limit(8)
            ->get();

        return [
            'student' => $student,
            'progress' => $progress,
            'summary' => $this->progressAliases($student, $progress),
            'motivation' => $this->studentMotivationService->build($student, $progress),
            'active_targets' => $activeTargets,
            'overdue_targets' => $overdueTargets,
            'latest_targets' => $latestTargets,
            'latest_hafalan_records' => $latestHafalanRecords,
            'latest_murajaah_records' => $latestMurajaahRecords,
        ];
    }

    private function studentsProgress(Collection $students): Collection
    {
        return $students
            ->map(function (Student $student) {
                $progress = $this->studentProgressService->calculate($student);

                return $this->progressAliases($student, $progress);
            })
            ->sortByDesc('progress_percentage')
            ->values();
    }

    private function studentsMotivation(Collection $students): Collection
    {
        return $students
            ->map(function (Student $student) {
                $progress = $this->studentProgressService->calculate($student);

                return [
                    'student' => $student,
                    'progress' => $progress,
                    'motivation' => $this->studentMotivationService->build($student, $progress),
                ];
            })
            ->values();
    }

    private function progressAliases(Student $student, array $progress): array
    {
        $progressPercent = (float) ($progress['progress_percent'] ?? 0);
        $memorizedAyahs = (int) ($progress['memorized_ayahs'] ?? 0);
        $totalQuranAyahs = (int) ($progress['total_quran_ayahs'] ?? 6236);

        return array_merge($progress, [
            'student' => $student,
            'student_id' => $student->id,
            'student_name' => $student->name,
            'student_number' => $student->student_number,
            'class_room_name' => $student->classRoom?->name,
            'program_name' => $student->classRoom?->program?->name,

            'memorized_ayah_count' => $memorizedAyahs,
            'total_ayah_count' => $totalQuranAyahs,
            'progress_percentage' => $progressPercent,

            'memorized_ayahs' => $memorizedAyahs,
            'total_quran_ayahs' => $totalQuranAyahs,
            'progress_percent' => $progressPercent,

            'total_hafalan_records' => (int) ($progress['total_hafalan_records'] ?? 0),
            'total_murajaah_records' => (int) ($progress['total_murajaah_records'] ?? 0),
            'active_targets' => (int) ($progress['active_targets'] ?? 0),
            'overdue_targets' => (int) ($progress['overdue_targets'] ?? 0),
            'average_hafalan_score' => (float) ($progress['average_hafalan_score'] ?? 0),
            'average_murajaah_score' => (float) ($progress['average_murajaah_score'] ?? 0),
        ]);
    }
}