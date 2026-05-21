<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $classRooms = ClassRoom::query()
            ->with('program')
            ->orderBy('name')
            ->get();

        $students = Student::query()
            ->with([
                'user',
                'classRoom.program',
                'teacher.user',
                'parents.user',
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('student_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('class_room_id'), function ($query) use ($request) {
                $query->where('class_room_id', $request->integer('class_room_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status')->toString());
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('students.index', compact('students', 'classRooms'));
    }

    public function create(): View
    {
        return view('students.create', $this->formData());
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $student = Student::create($this->studentPayload($validated));

        $this->syncParents($student, $validated);

        return redirect()
            ->route('students.index')
            ->with('success', 'Data santri berhasil ditambahkan.');
    }

    public function show(Student $student): View
    {
        $student->load([
            'user',
            'classRoom.program',
            'teacher.user',
            'parents.user',
        ]);

        $hafalanRecords = $student->hafalanRecords()
            ->with([
                'surah',
                'teacher.user',
            ])
            ->latest('submitted_at')
            ->latest()
            ->paginate(10);

        return view('students.show', compact('student', 'hafalanRecords'));
    }

    public function edit(Student $student): View
    {
        $student->load('parents');

        return view('students.edit', array_merge(
            ['student' => $student],
            $this->formData($student)
        ));
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $validated = $request->validated();

        $student->update($this->studentPayload($validated));

        $this->syncParents($student, $validated);

        return redirect()
            ->route('students.index')
            ->with('success', 'Data santri berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->parents()->detach();
        $student->delete();

        return redirect()
            ->route('students.index')
            ->with('success', 'Data santri berhasil dihapus.');
    }

    private function formData(?Student $student = null): array
    {
        $classRooms = ClassRoom::query()
            ->with('program')
            ->orderBy('name')
            ->get();

        $teachers = TeacherProfile::query()
            ->with('user')
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })
            ->get()
            ->sortBy(fn (TeacherProfile $teacher) => $teacher->user?->name);

        $parents = ParentProfile::query()
            ->with('user')
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })
            ->get()
            ->sortBy(fn (ParentProfile $parent) => $parent->user?->name);

        $studentUsers = User::query()
            ->with('role')
            ->whereHas('role', function ($query) {
                $query->where('name', 'student');
            })
            ->where('status', 'active')
            ->where(function ($query) use ($student) {
                $query->whereDoesntHave('studentProfile');

                if ($student?->user_id) {
                    $query->orWhere('id', $student->user_id);
                }
            })
            ->orderBy('name')
            ->get();

        return compact('classRooms', 'teachers', 'parents', 'studentUsers');
    }

    private function studentPayload(array $validated): array
    {
        return Arr::only($validated, [
            'user_id',
            'class_room_id',
            'teacher_id',
            'name',
            'student_number',
            'gender',
            'birth_date',
            'status',
        ]);
    }

    private function syncParents(Student $student, array $validated): void
    {
        $parentIds = collect($validated['parent_ids'] ?? [])
            ->filter()
            ->unique()
            ->values();

        $parentRelations = $validated['parent_relations'] ?? [];

        $syncData = $parentIds
            ->mapWithKeys(function ($parentId) use ($parentRelations) {
                return [
                    (int) $parentId => [
                        'relation' => $parentRelations[$parentId] ?? null,
                    ],
                ];
            })
            ->all();

        $student->parents()->sync($syncData);
    }
}