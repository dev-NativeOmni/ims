<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHafalanRecordRequest;
use App\Http\Requests\UpdateHafalanRecordRequest;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HafalanRecordController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $hafalanRecords = HafalanRecord::query()
            ->with([
                'student.classRoom.program',
                'teacher.user',
                'surah',
            ])
            ->when($user->hasRole('teacher'), function ($query) use ($user) {
                $query->where('teacher_id', $user->teacherProfile?->id);
            })
            ->when($request->filled('class_room_id'), function ($query) use ($request) {
                $query->whereHas('student', function ($q) use ($request) {
                    $q->where('class_room_id', $request->integer('class_room_id'));
                });
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
            ->latest('submitted_at')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('hafalan-records.index', array_merge(
            [
                'hafalanRecords' => $hafalanRecords,
            ],
            $this->formData($user)
        ));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', HafalanRecord::class);

        return view('hafalan-records.create', $this->formData($request->user()));
    }

    public function store(StoreHafalanRecordRequest $request): RedirectResponse
    {
        $this->authorize('create', HafalanRecord::class);

        $validated = $request->validated();
        $studentId = $validated['student_id'];
        $teacherId = $validated['teacher_id'] ?? null;
        $notes = $validated['notes'] ?? null;
        $submittedAt = $validated['submitted_at'];

        $surahIds = $validated['surah_ids'] ?? [];
        $ayahStarts = $validated['ayah_starts'] ?? [];
        $ayahEnds = $validated['ayah_ends'] ?? [];
        $submissionTypes = $validated['submission_types'] ?? [];
        $scores = $validated['scores'] ?? [];
        $statuses = $validated['statuses'] ?? [];

        DB::transaction(function () use (
            $studentId,
            $teacherId,
            $notes,
            $submittedAt,
            $surahIds,
            $ayahStarts,
            $ayahEnds,
            $submissionTypes,
            $scores,
            $statuses
        ) {
            foreach ($surahIds as $idx => $surahId) {
                if (empty($surahId)) {
                    continue;
                }

                HafalanRecord::query()->create([
                    'student_id' => $studentId,
                    'teacher_id' => $teacherId,
                    'surah_id' => (int) $surahId,
                    'ayah_start' => (int) ($ayahStarts[$idx] ?? 1),
                    'ayah_end' => (int) ($ayahEnds[$idx] ?? 1),
                    'submission_type' => $submissionTypes[$idx] ?? 'new',
                    'score' => isset($scores[$idx]) && $scores[$idx] !== '' ? $scores[$idx] : null,
                    'status' => $statuses[$idx] ?? 'passed',
                    'notes' => $notes,
                    'submitted_at' => $submittedAt,
                ]);
            }
        });

        return redirect()
            ->route('hafalan-records.index')
            ->with('success', 'Data hafalan berhasil ditambahkan.');
    }

    public function show(HafalanRecord $hafalanRecord): View
    {
        $this->authorize('view', $hafalanRecord);

        $hafalanRecord->load([
            'student.classRoom.program',
            'teacher.user',
            'surah',
        ]);

        return view('hafalan-records.show', [
            'hafalanRecord' => $hafalanRecord,
        ]);
    }

    public function edit(Request $request, HafalanRecord $hafalanRecord): View
    {
        $this->authorize('update', $hafalanRecord);

        return view('hafalan-records.edit', array_merge(
            [
                'hafalanRecord' => $hafalanRecord,
            ],
            $this->formData($request->user())
        ));
    }

    public function update(UpdateHafalanRecordRequest $request, HafalanRecord $hafalanRecord): RedirectResponse
    {
        $this->authorize('update', $hafalanRecord);

        $hafalanRecord->update($request->validated());

        return redirect()
            ->route('hafalan-records.index')
            ->with('success', 'Data hafalan berhasil diperbarui.');
    }

    public function destroy(HafalanRecord $hafalanRecord): RedirectResponse
    {
        $this->authorize('delete', $hafalanRecord);

        $hafalanRecord->delete();

        return redirect()
            ->route('hafalan-records.index')
            ->with('success', 'Data hafalan berhasil dihapus.');
    }

    private function formData(User $user): array
    {
        $students = Student::query()
            ->with([
                'classRoom.program',
                'teacher.user',
            ])
            ->where('status', 'active')
            ->when($user->hasRole('teacher'), function ($query) use ($user) {
                $query->where('teacher_id', $user->teacherProfile?->id);
            })
            ->orderBy('name')
            ->get();

        $teachers = TeacherProfile::query()
            ->with('user')
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })
            ->get()
            ->sortBy(fn (TeacherProfile $teacher) => $teacher->user?->name)
            ->values();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        return [
            'students' => $students,
            'teachers' => $teachers,
            'surahs' => $surahs,
            'classRooms' => $classRooms,
        ];
    }
}
