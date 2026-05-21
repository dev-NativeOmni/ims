<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHafalanTargetRequest;
use App\Http\Requests\UpdateHafalanTargetRequest;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\Surah;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class HafalanTargetController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = $this->filteredTargetQuery($request, $user);

        $targets = (clone $query)
            ->orderBy('target_date')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $summary = [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('status', 'active')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'missed' => (clone $query)->where('status', 'missed')->count(),
            'overdue' => (clone $query)
                ->where('status', 'active')
                ->whereDate('target_date', '<', today())
                ->count(),
        ];

        return view('hafalan-targets.index', array_merge(
            [
                'targets' => $targets,
                'summary' => $summary,
            ],
            $this->filterData($user)
        ));
    }

    public function create(Request $request): View
    {
        return view('hafalan-targets.create', $this->filterData($request->user()));
    }

    public function store(StoreHafalanTargetRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        HafalanTarget::create(
            $this->targetPayload($validated, $request->user())
        );

        return redirect()
            ->route('hafalan-targets.index')
            ->with('success', 'Target hafalan berhasil dibuat.');
    }

    public function show(Request $request, HafalanTarget $hafalanTarget): View
    {
        $this->authorizeTargetAccess($request->user(), $hafalanTarget);

        $hafalanTarget->load([
            'student.classRoom.program',
            'student.teacher.user',
            'teacher.user',
            'surah',
        ]);

        return view('hafalan-targets.show', [
            'target' => $hafalanTarget,
        ]);
    }

    public function edit(Request $request, HafalanTarget $hafalanTarget): View
    {
        $this->authorizeTargetAccess($request->user(), $hafalanTarget);

        $hafalanTarget->load([
            'student.classRoom.program',
            'teacher.user',
            'surah',
        ]);

        return view('hafalan-targets.edit', array_merge(
            [
                'target' => $hafalanTarget,
            ],
            $this->filterData($request->user())
        ));
    }

    public function update(
        UpdateHafalanTargetRequest $request,
        HafalanTarget $hafalanTarget
    ): RedirectResponse {
        $this->authorizeTargetAccess($request->user(), $hafalanTarget);

        $validated = $request->validated();

        $hafalanTarget->update(
            $this->targetPayload($validated, $request->user(), $hafalanTarget)
        );

        return redirect()
            ->route('hafalan-targets.index')
            ->with('success', 'Target hafalan berhasil diperbarui.');
    }

    public function destroy(Request $request, HafalanTarget $hafalanTarget): RedirectResponse
    {
        $this->authorizeTargetAccess($request->user(), $hafalanTarget);

        $hafalanTarget->delete();

        return redirect()
            ->route('hafalan-targets.index')
            ->with('success', 'Target hafalan berhasil dihapus.');
    }

    public function complete(Request $request, HafalanTarget $hafalanTarget): RedirectResponse
    {
        $this->authorizeTargetAccess($request->user(), $hafalanTarget);

        if ($hafalanTarget->status === 'completed') {
            return back()->with('error', 'Target ini sudah ditandai selesai.');
        }

        $hafalanTarget->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Target hafalan berhasil ditandai selesai.');
    }

    private function filteredTargetQuery(Request $request, User $user)
    {
        return HafalanTarget::query()
            ->with([
                'student.classRoom.program',
                'teacher.user',
                'surah',
            ])
            ->when($user->hasRole('teacher'), function ($query) use ($user) {
                $query->where('teacher_id', $user->teacherProfile?->id ?? 0);
            })
            ->when($request->filled('student_id'), function ($query) use ($request) {
                $query->where('student_id', $request->integer('student_id'));
            })
            ->when($request->filled('surah_id'), function ($query) use ($request) {
                $query->where('surah_id', $request->integer('surah_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status')->toString());
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('target_date', '>=', $request->date('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('target_date', '<=', $request->date('date_to'));
            });
    }

    private function filterData(User $user): array
    {
        $studentsQuery = Student::query()
            ->with([
                'classRoom.program',
                'teacher.user',
            ])
            ->where('status', 'active')
            ->whereNotNull('teacher_id')
            ->orderBy('name');

        if ($user->hasRole('teacher')) {
            $studentsQuery->where('teacher_id', $user->teacherProfile?->id ?? 0);
        }

        return [
            'students' => $studentsQuery->get(),
            'surahs' => Surah::query()
                ->orderBy('number')
                ->get(),
        ];
    }

    private function targetPayload(
        array $validated,
        User $user,
        ?HafalanTarget $target = null
    ): array {
        $student = Student::findOrFail($validated['student_id']);

        $payload = Arr::only($validated, [
            'student_id',
            'surah_id',
            'ayah_start',
            'ayah_end',
            'target_date',
            'status',
            'notes',
        ]);

        $payload['teacher_id'] = $this->resolveTeacherId($user, $student);

        if (($payload['status'] ?? null) === 'completed') {
            $payload['completed_at'] = $target?->completed_at ?? now();
        }

        if (($payload['status'] ?? null) !== 'completed') {
            $payload['completed_at'] = null;
        }

        return $payload;
    }

    private function resolveTeacherId(User $user, Student $student): int
    {
        if ($user->hasRole('teacher')) {
            $teacherId = $user->teacherProfile?->id;

            abort_if(
                ! $teacherId || (int) $student->teacher_id !== (int) $teacherId,
                403,
                'Guru hanya boleh mengelola target santri bimbingannya.'
            );

            return (int) $teacherId;
        }

        abort_if(
            ! $student->teacher_id,
            422,
            'Santri belum memiliki guru pembimbing.'
        );

        return (int) $student->teacher_id;
    }

    private function authorizeTargetAccess(User $user, HafalanTarget $target): void
    {
        if (! $user->hasRole('teacher')) {
            return;
        }

        abort_if(
            (int) $target->teacher_id !== (int) ($user->teacherProfile?->id ?? 0),
            403,
            'Guru hanya boleh mengakses target santri bimbingannya.'
        );
    }
}