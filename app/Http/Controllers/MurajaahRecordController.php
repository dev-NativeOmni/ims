<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMurajaahRecordRequest;
use App\Http\Requests\UpdateMurajaahRecordRequest;
use App\Models\MurajaahRecord;
use App\Models\Student;
use App\Models\Surah;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class MurajaahRecordController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $murajaahRecords = MurajaahRecord::query()
            ->with([
                'student.classRoom.program',
                'student.teacher.user',
                'teacher.user',
                'surah',
            ])
            ->when($user?->hasRole('teacher'), function ($query) use ($user) {
                $teacherId = $user->teacherProfile?->id;

                if (! $teacherId) {
                    $query->whereRaw('1 = 0');

                    return;
                }

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
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status')->toString());
            })
            ->latest('reviewed_at')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('murajaah-records.index', compact('murajaahRecords', 'surahs'));
    }

    public function create(Request $request): View
    {
        return view('murajaah-records.create', $this->formData($request->user()));
    }

    public function store(StoreMurajaahRecordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $student = Student::query()
            ->with('teacher')
            ->findOrFail($validated['student_id']);

        $this->authorizeStudentAccess($request->user(), $student);

        $validated['teacher_id'] = $student->teacher_id;

        MurajaahRecord::create($this->payload($validated));

        return redirect()
            ->route('murajaah-records.index')
            ->with('success', 'Data murajaah berhasil ditambahkan.');
    }

    public function show(Request $request, MurajaahRecord $murajaahRecord): View
    {
        $murajaahRecord->load([
            'student.classRoom.program',
            'student.parents.user',
            'teacher.user',
            'surah',
        ]);

        $this->authorizeRecordAccess($request->user(), $murajaahRecord);

        return view('murajaah-records.show', compact('murajaahRecord'));
    }

    public function edit(Request $request, MurajaahRecord $murajaahRecord): View
    {
        $murajaahRecord->load([
            'student',
            'teacher.user',
            'surah',
        ]);

        $this->authorizeRecordAccess($request->user(), $murajaahRecord);

        return view('murajaah-records.edit', array_merge(
            ['murajaahRecord' => $murajaahRecord],
            $this->formData($request->user())
        ));
    }

    public function update(UpdateMurajaahRecordRequest $request, MurajaahRecord $murajaahRecord): RedirectResponse
    {
        $murajaahRecord->load('student');

        $this->authorizeRecordAccess($request->user(), $murajaahRecord);

        $validated = $request->validated();

        $student = Student::query()
            ->with('teacher')
            ->findOrFail($validated['student_id']);

        $this->authorizeStudentAccess($request->user(), $student);

        $validated['teacher_id'] = $student->teacher_id;

        $murajaahRecord->update($this->payload($validated));

        return redirect()
            ->route('murajaah-records.index')
            ->with('success', 'Data murajaah berhasil diperbarui.');
    }

    public function destroy(Request $request, MurajaahRecord $murajaahRecord): RedirectResponse
    {
        $murajaahRecord->load('student');

        $this->authorizeRecordAccess($request->user(), $murajaahRecord);

        $murajaahRecord->delete();

        return redirect()
            ->route('murajaah-records.index')
            ->with('success', 'Data murajaah berhasil dihapus.');
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
            'fluency_score',
            'tajwid_score',
            'makhraj_score',
            'overall_score',
            'status',
            'notes',
            'reviewed_at',
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

    private function authorizeRecordAccess(?User $user, MurajaahRecord $murajaahRecord): void
    {
        if ($user?->hasAnyRole(['super_admin', 'admin'])) {
            return;
        }

        if ($user?->hasRole('teacher')) {
            $teacherId = $user->teacherProfile?->id;

            abort_unless(
                $teacherId && (int) $murajaahRecord->student?->teacher_id === (int) $teacherId,
                403
            );

            return;
        }

        abort(403);
    }
}