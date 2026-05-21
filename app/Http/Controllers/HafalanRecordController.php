<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHafalanRecordRequest;
use App\Http\Requests\UpdateHafalanRecordRequest;
use App\Models\HafalanRecord;
use App\Models\Student;
use App\Models\Surah;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class HafalanRecordController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $hafalanRecords = HafalanRecord::query()
            ->with([
                'student.classRoom.program',
                'student.teacher.user',
                'teacher.user',
                'surah',
            ])
            ->when($user?->hasRole('teacher'), function ($query) use ($user) {
                $teacherId = $user->teacherProfile?->id;

                $query->whereHas('student', function ($studentQuery) use ($teacherId) {
                    $studentQuery->where('teacher_id', $teacherId);
                });
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->whereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('student_number', 'like', "%{$search}%");
                    })->orWhereHas('surah', function ($surahQuery) use ($search) {
                        $surahQuery->where('name_latin', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%");
                    });
                });
            })
            ->when($request->filled('surah_id'), function ($query) use ($request) {
                $query->where('surah_id', $request->integer('surah_id'));
            })
            ->when($request->filled('submission_type'), function ($query) use ($request) {
                $query->where('submission_type', $request->string('submission_type')->toString());
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status')->toString());
            })
            ->latest('submitted_at')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('hafalan-records.index', compact('hafalanRecords', 'surahs'));
    }

    public function create(Request $request): View
    {
        return view('hafalan-records.create', $this->formData($request->user()));
    }

    public function store(StoreHafalanRecordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $student = Student::query()
            ->with('teacher')
            ->findOrFail($validated['student_id']);

        $this->authorizeStudentAccess($request->user(), $student);

        $validated['teacher_id'] = $student->teacher_id;

        HafalanRecord::create($this->payload($validated));

        return redirect()
            ->route('hafalan-records.index')
            ->with('success', 'Setoran hafalan berhasil ditambahkan.');
    }

    public function show(Request $request, HafalanRecord $hafalanRecord): View
    {
        $hafalanRecord->load([
            'student.classRoom.program',
            'student.parents.user',
            'teacher.user',
            'surah',
        ]);

        $this->authorizeRecordAccess($request->user(), $hafalanRecord);

        return view('hafalan-records.show', compact('hafalanRecord'));
    }

    public function edit(Request $request, HafalanRecord $hafalanRecord): View
    {
        $hafalanRecord->load([
            'student',
            'teacher.user',
            'surah',
        ]);

        $this->authorizeRecordAccess($request->user(), $hafalanRecord);

        return view('hafalan-records.edit', array_merge(
            ['hafalanRecord' => $hafalanRecord],
            $this->formData($request->user())
        ));
    }

    public function update(UpdateHafalanRecordRequest $request, HafalanRecord $hafalanRecord): RedirectResponse
    {
        $hafalanRecord->load('student');

        $this->authorizeRecordAccess($request->user(), $hafalanRecord);

        $validated = $request->validated();

        $student = Student::query()
            ->with('teacher')
            ->findOrFail($validated['student_id']);

        $this->authorizeStudentAccess($request->user(), $student);

        $validated['teacher_id'] = $student->teacher_id;

        $hafalanRecord->update($this->payload($validated));

        return redirect()
            ->route('hafalan-records.index')
            ->with('success', 'Setoran hafalan berhasil diperbarui.');
    }

    public function destroy(Request $request, HafalanRecord $hafalanRecord): RedirectResponse
    {
        $hafalanRecord->load('student');

        $this->authorizeRecordAccess($request->user(), $hafalanRecord);

        $hafalanRecord->delete();

        return redirect()
            ->route('hafalan-records.index')
            ->with('success', 'Setoran hafalan berhasil dihapus.');
    }

    private function formData(?User $user): array
    {
        $students = Student::query()
            ->with([
                'classRoom.program',
                'teacher.user',
            ])
            ->where('status', 'active')
            ->when($user?->hasRole('teacher'), function ($query) use ($user) {
                $query->where('teacher_id', $user->teacherProfile?->id);
            })
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        return compact('students', 'surahs');
    }

    private function payload(array $validated): array
    {
        return Arr::only($validated, [
            'student_id',
            'teacher_id',
            'surah_id',
            'ayah_start',
            'ayah_end',
            'submission_type',
            'score',
            'status',
            'notes',
            'submitted_at',
        ]);
    }

    private function authorizeStudentAccess(?User $user, Student $student): void
    {
        if ($user?->hasAnyRole(['super_admin', 'admin'])) {
            return;
        }

        if ($user?->hasRole('teacher')) {
            $teacherId = $user->teacherProfile?->id;

            abort_unless($teacherId && (int) $student->teacher_id === (int) $teacherId, 403);

            return;
        }

        abort(403);
    }

    private function authorizeRecordAccess(?User $user, HafalanRecord $hafalanRecord): void
    {
        if ($user?->hasAnyRole(['super_admin', 'admin'])) {
            return;
        }

        if ($user?->hasRole('teacher')) {
            $teacherId = $user->teacherProfile?->id;

            abort_unless(
                $teacherId && (int) $hafalanRecord->student?->teacher_id === (int) $teacherId,
                403
            );

            return;
        }

        abort(403);
    }
}