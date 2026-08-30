<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\Surah;
use App\Models\User;
use App\Services\StudentProgressService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class HafalanTargetController extends Controller
{
    public function index(Request $request): View
    {
        $visibleStudentIds = $this->visibleStudentIds($request->user());
        $activeProgram = $request->input('program', 'reguler');

        $query = HafalanTarget::query()
            ->with([
                'student.classRoom.program',
                'surah',
                'teacher.user',
            ])
            ->whereIn('student_id', $visibleStudentIds)
            ->when($activeProgram === 'ummi', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('ummi_jilid')
                        ->orWhereHas('student.classRoom', function ($c) {
                            $c->where('name', 'like', 'X %')
                              ->orWhere('name', 'like', 'X-%')
                              ->orWhere('name', 'X');
                        });
                });
            })
            ->when($activeProgram === 'reguler', function ($q) {
                $q->whereNull('ummi_jilid');
            })
            ->when($request->filled('class_room_id'), function ($query) use ($request) {
                $query->whereHas('student', function ($q) use ($request) {
                    $q->where('class_room_id', $request->integer('class_room_id'));
                });
            })
            ->when($request->filled('teacher_id'), function ($query) use ($request) {
                $query->where('teacher_id', $request->integer('teacher_id'));
            })
            ->when($request->filled('student_id'), function ($query) use ($request, $visibleStudentIds) {
                $studentId = (int) $request->input('student_id');

                if ($visibleStudentIds->contains($studentId)) {
                    $query->where('student_id', $studentId);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('surah_id'), function ($query) use ($request) {
                $query->where('surah_id', $request->input('surah_id'));
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('target_date', '>=', $request->input('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('target_date', '<=', $request->input('date_to'));
            });

        $targets = (clone $query)
            ->orderBy('target_date')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $activeStatuses = $this->activeTargetStatuses();

        $summary = [
            'total' => (clone $query)->count(),

            'active' => (clone $query)
                ->whereIn('status', $activeStatuses)
                ->count(),

            'planned' => (clone $query)
                ->where('status', 'planned')
                ->count(),

            'in_progress' => (clone $query)
                ->where('status', 'in_progress')
                ->count(),

            'completed' => (clone $query)
                ->where('status', 'completed')
                ->count(),

            'missed' => (clone $query)
                ->where('status', 'missed')
                ->count(),

            'cancelled' => (clone $query)
                ->where('status', 'cancelled')
                ->count(),

            'overdue' => (clone $query)
                ->whereIn('status', $activeStatuses)
                ->whereDate('target_date', '<', today())
                ->count(),
        ];

        $allVisibleStudents = Student::query()
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->get();

        $classRoomIds = $allVisibleStudents->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        $user = $request->user();
        $isTeacherOnly = $user?->hasRole('teacher') && ! $user?->hasAnyRole(['super_admin', 'admin']);

        if ($isTeacherOnly && $user->teacherProfile) {
            $teachers = \App\Models\TeacherProfile::query()
                ->with('user')
                ->where('id', $user->teacherProfile->id)
                ->get();
            $currentTeacherId = $user->teacherProfile->id;
        } else {
            $teachers = \App\Models\TeacherProfile::query()
                ->with('user')
                ->whereHas('user')
                ->orderBy('id')
                ->get();
            $currentTeacherId = (int) ($request->input('teacher_id') ?: ($user->teacherProfile?->id ?? $teachers->first()?->id));
        }

        $students = Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->when($request->filled('class_room_id'), function ($q) use ($request) {
                $q->where('class_room_id', $request->integer('class_room_id'));
            })
            ->when($request->filled('teacher_id') || $isTeacherOnly, function ($q) use ($request, $currentTeacherId) {
                $q->where('teacher_id', $currentTeacherId);
            })
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $statusOptions = $this->targetStatuses();

        return view('hafalan-targets.index', compact(
            'targets',
            'students',
            'classRooms',
            'teachers',
            'surahs',
            'summary',
            'statusOptions',
            'activeProgram',
            'currentTeacherId',
            'isTeacherOnly'
        ));
    }

    public function storeBulkReguler(Request $request): RedirectResponse
    {
        $this->authorize('create', HafalanTarget::class);
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $request->validate([
            'targets' => ['required', 'array'],
            'targets.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'targets.*.surah_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'targets.*.ayah_start' => ['nullable', 'integer', 'min:1'],
            'targets.*.ayah_end' => ['nullable', 'integer', 'min:1'],
            'targets.*.target_date' => ['nullable', 'date'],
            'targets.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $count = 0;
        foreach ($request->input('targets', []) as $row) {
            $studentId = (int) ($row['student_id'] ?? 0);
            if (! $visibleStudentIds->contains($studentId)) {
                continue;
            }

            if (! empty($row['surah_id']) && ! empty($row['target_date'])) {
                $student = Student::find($studentId);
                if (! $student) {
                    continue;
                }

                $teacherId = $this->resolveTeacherId($request, $student);

                HafalanTarget::create([
                    'student_id' => $student->id,
                    'teacher_id' => $teacherId,
                    'surah_id' => (int) $row['surah_id'],
                    'ayah_start' => (int) ($row['ayah_start'] ?? 1),
                    'ayah_end' => (int) ($row['ayah_end'] ?? 1),
                    'target_date' => $row['target_date'],
                    'notes' => $row['notes'] ?? null,
                    'status' => $this->defaultOpenTargetStatus(),
                ]);
                $count++;
            }
        }

        return redirect()
            ->route('hafalan-targets.index', ['program' => 'reguler', 'class_room_id' => $request->input('class_room_id')])
            ->with('success', "Berhasil menyimpan {$count} target hafalan reguler.");
    }

    public function storeBulkUmmi(Request $request): RedirectResponse
    {
        $this->authorize('create', HafalanTarget::class);

        $validated = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teacher_profiles,id'],
            'ummi_jilid' => ['required', 'string', 'max:100'],
            'halaman_peraga' => ['nullable', 'string', 'max:100'],
            'halaman_buku' => ['nullable', 'string', 'max:100'],
            'surah_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'target_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        if ($user?->hasRole('teacher') && ! $user?->hasAnyRole(['super_admin', 'admin']) && $user->teacherProfile) {
            $teacherProfile = $user->teacherProfile;
        } else {
            $teacherProfile = \App\Models\TeacherProfile::findOrFail($validated['teacher_id']);
        }

        $students = Student::query()
            ->where('teacher_id', $teacherProfile->id)
            ->where('status', 'active')
            ->get();

        $count = 0;
        foreach ($students as $student) {
            HafalanTarget::create([
                'student_id' => $student->id,
                'teacher_id' => $teacherProfile->id,
                'ummi_jilid' => $validated['ummi_jilid'],
                'halaman_peraga' => $validated['halaman_peraga'] ?? null,
                'halaman_buku' => $validated['halaman_buku'] ?? null,
                'surah_id' => $validated['surah_id'] ?? null,
                'ayah_start' => null,
                'ayah_end' => null,
                'target_date' => $validated['target_date'],
                'notes' => $validated['notes'] ?? null,
                'status' => $this->defaultOpenTargetStatus(),
            ]);
            $count++;
        }

        return redirect()
            ->route('hafalan-targets.index', ['program' => 'ummi', 'teacher_id' => $validated['teacher_id']])
            ->with('success', "Berhasil menyimpan target Ummi serentak untuk {$count} murid di Halaqah Musyrif.");
    }

    public function create(Request $request): View
    {
        $this->authorize('create', HafalanTarget::class);
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $students = Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $statusOptions = $this->targetStatuses();

        return view('hafalan-targets.create', compact(
            'students',
            'classRooms',
            'surahs',
            'statusOptions'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', HafalanTarget::class);
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $validated = $this->validateTarget($request, $visibleStudentIds);

        $student = Student::query()->findOrFail($validated['student_id']);

        $data = $this->targetPayload($validated);
        $data['student_id'] = $student->id;

        $data['teacher_id'] = $this->resolveTeacherId($request, $student);

        if (empty($data['status'])) {
            $data['status'] = $this->defaultOpenTargetStatus();
        }

        HafalanTarget::query()->create($data);

        return redirect()
            ->route('hafalan-targets.index')
            ->with('success', 'Target hafalan berhasil ditambahkan.');
    }

    public function show(Request $request, HafalanTarget $hafalanTarget): View
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);

        $hafalanTarget->load([
            'student.classRoom.program',
            'surah',
            'teacher.user',
        ]);

        $target = $hafalanTarget;

        return view('hafalan-targets.show', compact(
            'hafalanTarget',
            'target'
        ));
    }

    public function edit(Request $request, HafalanTarget $hafalanTarget): View
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);
        $this->authorize('update', $hafalanTarget);

        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $students = Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->orderBy('name')
            ->get();

        $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $statusOptions = $this->targetStatuses();

        $target = $hafalanTarget;

        return view('hafalan-targets.edit', compact(
            'hafalanTarget',
            'target',
            'students',
            'classRooms',
            'surahs',
            'statusOptions'
        ));
    }

    public function update(Request $request, HafalanTarget $hafalanTarget): RedirectResponse
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);
        $this->authorize('update', $hafalanTarget);

        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $validated = $this->validateTarget($request, $visibleStudentIds);

        $student = Student::query()->findOrFail($validated['student_id']);

        $data = $this->targetPayload($validated);
        $data['student_id'] = $student->id;

        $data['teacher_id'] = $this->resolveTeacherId($request, $student);

        $hafalanTarget->update($data);

        return redirect()
            ->route('hafalan-targets.index')
            ->with('success', 'Target hafalan berhasil diperbarui.');
    }

    public function destroy(Request $request, HafalanTarget $hafalanTarget): RedirectResponse
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);
        $this->authorize('delete', $hafalanTarget);

        $hafalanTarget->delete();

        return redirect()
            ->route('hafalan-targets.index')
            ->with('success', 'Target hafalan berhasil dihapus.');
    }

    public function complete(Request $request, HafalanTarget $hafalanTarget): RedirectResponse
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);
        $this->authorize('update', $hafalanTarget);

        $data = [
            'status' => 'completed',
        ];

        if (Schema::hasColumn('hafalan_targets', 'completed_at')) {
            $data['completed_at'] = now();
        }

        $hafalanTarget->update($data);

        return redirect()
            ->back()
            ->with('success', 'Target hafalan ditandai selesai.');
    }

    public function markMissed(Request $request, HafalanTarget $hafalanTarget): RedirectResponse
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);
        $this->authorize('update', $hafalanTarget);

        $hafalanTarget->update([
            'status' => 'missed',
        ]);

        return redirect()
            ->back()
            ->with('success', 'Target hafalan ditandai terlewat.');
    }

    private function validateTarget(Request $request, Collection $visibleStudentIds): array
    {
        $statuses = $this->targetStatuses();

        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'surah_id' => ['required', 'integer', 'exists:surahs,id'],
            'ayah_start' => ['required', 'integer', 'min:1'],
            'ayah_end' => ['required', 'integer', 'min:1', 'gte:ayah_start'],
            'target_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in($statuses)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validator->after(function ($validator) use ($request, $visibleStudentIds) {
            $studentId = (int) $request->input('student_id');

            if (! $visibleStudentIds->contains($studentId)) {
                $validator->errors()->add(
                    'student_id',
                    'Murid tidak boleh diakses oleh akun ini.'
                );
            }

            $surah = Surah::query()->find($request->input('surah_id'));

            if ($surah && isset($surah->total_ayah)) {
                if ((int) $request->input('ayah_end') > (int) $surah->total_ayah) {
                    $validator->errors()->add(
                        'ayah_end',
                        'Ayat akhir tidak boleh melebihi jumlah ayat surah.'
                    );
                }
            }
        });

        return $validator->validate();
    }

    private function targetPayload(array $validated): array
    {
        $allowedColumns = Schema::getColumnListing('hafalan_targets');

        $payload = [];

        foreach ($validated as $key => $value) {
            if (in_array($key, $allowedColumns, true)) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }

    private function authorizeTargetAccess(Request $request, HafalanTarget $target): void
    {
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        abort_unless(
            $visibleStudentIds->contains((int) $target->student_id),
            403,
            'Target hafalan tidak boleh diakses oleh akun ini.'
        );
    }

    private function visibleStudentIds(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        return app(StudentProgressService::class)
            ->visibleStudentQuery($user)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
    }

    private function resolveTeacherId(Request $request, Student $student): ?int
    {
        $user = $request->user();
        $teacherId = null;

        if ($user?->teacherProfile?->id) {
            $teacherId = (int) $user->teacherProfile->id;
        } elseif ($user?->hasRole('teacher')) {
            $tId = TeacherProfile::query()->where('user_id', $user->id)->value('id');
            if ($tId) {
                $teacherId = (int) $tId;
            }
        }

        if (! $teacherId && $student->teacher_id) {
            $teacherId = (int) $student->teacher_id;
        }

        if (! $teacherId) {
            $teacherId = (int) TeacherProfile::query()->value('id');
        }

        if ($teacherId && ! $student->teacher_id) {
            $student->update(['teacher_id' => $teacherId]);
        }

        return $teacherId;
    }

    private function targetStatuses(): array
    {
        try {
            $column = DB::selectOne("SHOW COLUMNS FROM hafalan_targets LIKE 'status'");

            if ($column && isset($column->Type)) {
                preg_match_all("/'([^']+)'/", (string) $column->Type, $matches);

                if (! empty($matches[1])) {
                    return $matches[1];
                }
            }
        } catch (Throwable) {
            // Fallback di bawah sengaja dibiarkan.
        }

        return [
            'active',
            'planned',
            'in_progress',
            'completed',
            'missed',
            'cancelled',
        ];
    }

    private function activeTargetStatuses(): array
    {
        $statuses = $this->targetStatuses();

        $activeStatuses = array_values(array_intersect($statuses, [
            'active',
            'planned',
            'in_progress',
        ]));

        return ! empty($activeStatuses)
            ? $activeStatuses
            : [$this->defaultOpenTargetStatus()];
    }

    private function defaultOpenTargetStatus(): string
    {
        $statuses = $this->targetStatuses();

        if (in_array('active', $statuses, true)) {
            return 'active';
        }

        if (in_array('planned', $statuses, true)) {
            return 'planned';
        }

        if (in_array('in_progress', $statuses, true)) {
            return 'in_progress';
        }

        return $statuses[0] ?? 'active';
    }
}
