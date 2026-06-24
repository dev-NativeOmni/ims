<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('student')) {
            $student = Student::query()->where('user_id', $user->id)->first();
            if ($student) {
                return redirect()->route('reports.student', $student);
            }
            abort(403, 'Akun santri belum memiliki profil santri.');
        }

        if ($user->hasRole('parent')) {
            $visibleStudentIds = $this->visibleStudentIds($user);
            if ($visibleStudentIds->count() === 1) {
                $student = Student::query()->find($visibleStudentIds->first());
                if ($student) {
                    return redirect()->route('reports.student', $student);
                }
            }
        }

        $visibleStudentIds = $this->visibleStudentIds($user);

        $hafalanQuery = $this->filteredHafalanQuery($request, $visibleStudentIds);
        $murajaahQuery = $this->filteredMurajaahQuery($request, $visibleStudentIds);
        $targetQuery = $this->filteredTargetQuery($request, $visibleStudentIds);

        $summary = [
            'total_students' => $visibleStudentIds->count(),

            'total_hafalan' => (clone $hafalanQuery)->count(),
            'total_murajaah' => (clone $murajaahQuery)->count(),
            'total_targets' => (clone $targetQuery)->count(),

            'active_targets' => (clone $targetQuery)
                ->whereIn('status', ['active', 'planned', 'in_progress'])
                ->count(),

            'completed_targets' => (clone $targetQuery)
                ->where('status', 'completed')
                ->count(),

            'missed_targets' => (clone $targetQuery)
                ->where('status', 'missed')
                ->count(),

            'passed_hafalan' => (clone $hafalanQuery)
                ->where('status', 'passed')
                ->count(),

            'repeat_hafalan' => (clone $hafalanQuery)
                ->whereIn('status', ['repeat', 'needs_improvement'])
                ->count(),

            'passed_murajaah' => (clone $murajaahQuery)
                ->where('status', 'passed')
                ->count(),

            'repeat_murajaah' => (clone $murajaahQuery)
                ->whereIn('status', ['repeat', 'needs_improvement'])
                ->count(),

            'average_hafalan_score' => round((float) (clone $hafalanQuery)
                ->whereNotNull('score')
                ->avg('score'), 2),

            'average_murajaah_score' => round((float) (clone $murajaahQuery)
                ->whereNotNull('overall_score')
                ->avg('overall_score'), 2),
        ];

        $hafalanRecords = (clone $hafalanQuery)
            ->with([
                'student.classRoom.program',
                'student.teacher.user',
                'surah',
                'teacher.user',
            ])
            ->latest('submitted_at')
            ->latest()
            ->paginate(10, ['*'], 'hafalan_page')
            ->withQueryString();

        $murajaahRecords = (clone $murajaahQuery)
            ->with([
                'student.classRoom.program',
                'student.teacher.user',
                'surah',
                'teacher.user',
            ])
            ->latest('reviewed_at')
            ->latest()
            ->paginate(10, ['*'], 'murajaah_page')
            ->withQueryString();

        $hafalanTargets = (clone $targetQuery)
            ->with([
                'student.classRoom.program',
                'student.teacher.user',
                'surah',
                'teacher.user',
            ])
            ->orderByRaw("
                CASE
                    WHEN status IN ('active', 'planned', 'in_progress') THEN 0
                    WHEN status = 'missed' THEN 1
                    WHEN status = 'completed' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('target_date')
            ->paginate(10, ['*'], 'target_page')
            ->withQueryString();

        return view('reports.index', array_merge([
            'summary' => $summary,
            'hafalanRecords' => $hafalanRecords,
            'murajaahRecords' => $murajaahRecords,
            'hafalanTargets' => $hafalanTargets,
            'filters' => $request->only([
                'student_id',
                'class_room_id',
                'teacher_id',
                'surah_id',
                'status',
                'from',
                'to',
            ]),
        ], $this->filterData($visibleStudentIds)));
    }

    public function student(Request $request, Student $student): View
    {
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        abort_unless($visibleStudentIds->contains($student->id), 403);

        $student->load([
            'user',
            'classRoom.program',
            'teacher.user',
            'parents.user',
        ]);

        $hafalanRecords = HafalanRecord::query()
            ->with(['surah', 'teacher.user'])
            ->where('student_id', $student->id)
            ->latest('submitted_at')
            ->latest()
            ->limit(500)
            ->get();

        $murajaahRecords = MurajaahRecord::query()
            ->with(['surah', 'teacher.user'])
            ->where('student_id', $student->id)
            ->latest('reviewed_at')
            ->latest()
            ->limit(500)
            ->get();

        $hafalanTargets = HafalanTarget::query()
            ->with(['surah', 'teacher.user'])
            ->where('student_id', $student->id)
            ->orderByRaw("
                CASE
                    WHEN status IN ('active', 'planned', 'in_progress') THEN 0
                    WHEN status = 'missed' THEN 1
                    WHEN status = 'completed' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('target_date')
            ->get();

        $summary = [
            'total_hafalan' => $hafalanRecords->count(),
            'total_murajaah' => $murajaahRecords->count(),
            'total_targets' => $hafalanTargets->count(),
            'active_targets' => $hafalanTargets
                ->whereIn('status', ['active', 'planned', 'in_progress'])
                ->count(),
            'completed_targets' => $hafalanTargets
                ->where('status', 'completed')
                ->count(),
            'missed_targets' => $hafalanTargets
                ->where('status', 'missed')
                ->count(),
            'average_hafalan_score' => round((float) $hafalanRecords->avg('score'), 2),
            'average_murajaah_score' => round((float) $murajaahRecords->avg('overall_score'), 2),
        ];

        return view('reports.student', compact(
            'student',
            'hafalanRecords',
            'murajaahRecords',
            'hafalanTargets',
            'summary'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $hafalanQuery = $this->filteredHafalanQuery($request, $visibleStudentIds)
            ->with(['student.classRoom.program', 'surah', 'teacher.user'])
            ->latest('submitted_at')
            ->latest();

        $murajaahQuery = $this->filteredMurajaahQuery($request, $visibleStudentIds)
            ->with(['student.classRoom.program', 'surah', 'teacher.user'])
            ->latest('reviewed_at')
            ->latest();

        $fileName = 'laporan-hafizplus-' . now()->format('Ymd-His') . '.csv';

        // Gunakan cursor() agar hanya satu baris dimuat ke memory pada satu waktu.
        return response()->streamDownload(function () use ($hafalanQuery, $murajaahQuery) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Jenis',
                'Santri',
                'Kelas',
                'Program',
                'Surah',
                'Ayat Mulai',
                'Ayat Akhir',
                'Status',
                'Nilai',
                'Tanggal',
                'Guru',
                'Catatan',
            ]);

            foreach ($hafalanQuery->cursor() as $record) {
                fputcsv($handle, [
                    'Hafalan',
                    $record->student?->name,
                    $record->student?->classRoom?->name,
                    $record->student?->classRoom?->program?->name,
                    $record->surah?->name_latin ?? $record->surah?->name,
                    $record->ayah_start,
                    $record->ayah_end,
                    $record->status,
                    $record->score,
                    $this->formatDateForCsv($record->submitted_at),
                    $record->teacher?->user?->name,
                    $record->notes,
                ]);
            }

            foreach ($murajaahQuery->cursor() as $record) {
                fputcsv($handle, [
                    'Murajaah',
                    $record->student?->name,
                    $record->student?->classRoom?->name,
                    $record->student?->classRoom?->program?->name,
                    $record->surah?->name_latin ?? $record->surah?->name,
                    $record->ayah_start,
                    $record->ayah_end,
                    $record->status,
                    $record->overall_score,
                    $this->formatDateForCsv($record->reviewed_at),
                    $record->teacher?->user?->name,
                    $record->notes,
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportStudentCsv(Request $request, Student $student): StreamedResponse
    {
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        abort_unless($visibleStudentIds->contains($student->id), 403);

        $request->merge([
            'student_id' => $student->id,
        ]);

        return $this->exportCsv($request);
    }

    private function filteredHafalanQuery(Request $request, Collection $visibleStudentIds): Builder
    {
        $query = HafalanRecord::query();

        $this->applyCommonFilters($query, $request, $visibleStudentIds);
        $this->applyDateFilters($query, $request, 'submitted_at');

        return $query;
    }

    private function filteredMurajaahQuery(Request $request, Collection $visibleStudentIds): Builder
    {
        $query = MurajaahRecord::query();

        $this->applyCommonFilters($query, $request, $visibleStudentIds);
        $this->applyDateFilters($query, $request, 'reviewed_at');

        return $query;
    }

    private function filteredTargetQuery(Request $request, Collection $visibleStudentIds): Builder
    {
        $query = HafalanTarget::query();

        $this->applyCommonFilters($query, $request, $visibleStudentIds, false);
        $this->applyDateFilters($query, $request, 'target_date');

        return $query;
    }

    private function applyCommonFilters(
        Builder $query,
        Request $request,
        Collection $visibleStudentIds,
        bool $allowStatusFilter = true
    ): void {
        $query->whereIn('student_id', $visibleStudentIds);

        $studentId = $request->integer('student_id');

        if ($studentId > 0) {
            abort_unless($visibleStudentIds->contains($studentId), 403);

            $query->where('student_id', $studentId);
        }

        if ($request->filled('class_room_id')) {
            $query->whereHas('student', function (Builder $studentQuery) use ($request) {
                $studentQuery->where('class_room_id', $request->integer('class_room_id'));
            });
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->integer('teacher_id'));
        }

        if ($request->filled('surah_id')) {
            $query->where('surah_id', $request->integer('surah_id'));
        }

        if ($allowStatusFilter && $request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
    }

    private function applyDateFilters(Builder $query, Request $request, string $column): void
    {
        if ($request->filled('from')) {
            $query->whereDate($column, '>=', $request->date('from')->toDateString());
        }

        if ($request->filled('to')) {
            $query->whereDate($column, '<=', $request->date('to')->toDateString());
        }
    }

    private function filterData(Collection $visibleStudentIds): array
    {
        $students = Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->orderBy('name')
            ->get();

        $classRoomIds = $students
            ->pluck('class_room_id')
            ->filter()
            ->unique()
            ->values();

        $teacherIds = $students
            ->pluck('teacher_id')
            ->filter()
            ->unique()
            ->values();

        return [
            'students' => $students,

            'classRooms' => ClassRoom::query()
                ->with('program')
                ->when($classRoomIds->isNotEmpty(), fn (Builder $query) => $query->whereIn('id', $classRoomIds))
                ->orderBy('name')
                ->get(),

            'teachers' => TeacherProfile::query()
                ->with('user')
                ->when($teacherIds->isNotEmpty(), fn (Builder $query) => $query->whereIn('id', $teacherIds))
                ->orderBy('id')
                ->get(),

            'surahs' => Surah::query()
                ->orderBy('number')
                ->get(),
        ];
    }

    private function visibleStudentIds(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        if ($this->userHasAnyRole($user, ['super_admin', 'admin'])) {
            return Student::query()
                ->pluck('id');
        }

        if ($this->userHasAnyRole($user, ['teacher'])) {
            $teacherId = TeacherProfile::query()
                ->where('user_id', $user->id)
                ->value('id');

            if (! $teacherId) {
                return collect();
            }

            return Student::query()
                ->where('teacher_id', $teacherId)
                ->pluck('id');
        }

        if ($this->userHasAnyRole($user, ['parent'])) {
            $parentId = ParentProfile::query()
                ->where('user_id', $user->id)
                ->value('id');

            if (! $parentId || ! Schema::hasTable('parent_student')) {
                return collect();
            }

            return DB::table('parent_student')
                ->where('parent_id', $parentId)
                ->pluck('student_id');
        }

        if ($this->userHasAnyRole($user, ['student'])) {
            if (! Schema::hasColumn('students', 'user_id')) {
                return collect();
            }

            return Student::query()
                ->where('user_id', $user->id)
                ->pluck('id');
        }

        return collect();
    }

    private function userHasAnyRole(User $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return true;
            }

            if (($user->role?->name ?? null) === $role) {
                return true;
            }
        }

        return false;
    }

    private function formatDateForCsv(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
    }
}