<?php

namespace App\Http\Controllers;

use App\Models\HafalanRecord;
use App\Models\Student;
use App\Models\Surah;
use App\Services\UserAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HafalanRecordController extends Controller
{
    public function index(Request $request, UserAccessService $accessService): View
    {
        Gate::authorize('viewAny', HafalanRecord::class);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $records = HafalanRecord::query()
            ->with(['student.classRoom', 'surah', 'teacher.user'])
            ->whereIn('student_id', $visibleStudentIds)
            ->when($request->filled('student_id'), function ($query) use ($request, $visibleStudentIds) {
                if ($visibleStudentIds->contains((int) $request->student_id)) {
                    $query->where('student_id', $request->student_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('surah_id'), fn ($query) => $query->where('surah_id', $request->surah_id))
            ->latest('submitted_at')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $students = Student::query()
            ->whereIn('id', $visibleStudentIds)
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        return view('hafalan-records.index', compact('records', 'students', 'surahs'));
    }

    public function create(Request $request, UserAccessService $accessService): View
    {
        Gate::authorize('create', HafalanRecord::class);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $students = Student::query()
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        return view('hafalan-records.create', compact('students', 'surahs'));
    }

    public function store(Request $request, UserAccessService $accessService): RedirectResponse
    {
        Gate::authorize('create', HafalanRecord::class);

        $validated = $this->validateRecord($request, $accessService);

        $student = Student::findOrFail($validated['student_id']);

        HafalanRecord::create([
            'student_id' => $student->id,
            'teacher_id' => $this->resolveTeacherId($request, $student),
            'surah_id' => $validated['surah_id'],
            'ayah_start' => $validated['ayah_start'],
            'ayah_end' => $validated['ayah_end'],
            'submission_type' => $validated['submission_type'],
            'score' => $validated['score'] ?? null,
            'status' => $validated['status'],
            'submitted_at' => $validated['submitted_at'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('hafalan-records.index')
            ->with('success', 'Data hafalan berhasil ditambahkan.');
    }

    public function show(HafalanRecord $hafalanRecord): View
    {
        Gate::authorize('view', $hafalanRecord);

        $hafalanRecord->load(['student.classRoom', 'student.program', 'surah', 'teacher.user']);

        return view('hafalan-records.show', [
            'hafalanRecord' => $hafalanRecord,
            'record' => $hafalanRecord,
        ]);
    }

    public function edit(Request $request, HafalanRecord $hafalanRecord, UserAccessService $accessService): View
    {
        Gate::authorize('update', $hafalanRecord);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $students = Student::query()
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        return view('hafalan-records.edit', [
            'hafalanRecord' => $hafalanRecord,
            'record' => $hafalanRecord,
            'students' => $students,
            'surahs' => $surahs,
        ]);
    }

    public function update(Request $request, HafalanRecord $hafalanRecord, UserAccessService $accessService): RedirectResponse
    {
        Gate::authorize('update', $hafalanRecord);

        $validated = $this->validateRecord($request, $accessService);

        $student = Student::findOrFail($validated['student_id']);

        $hafalanRecord->update([
            'student_id' => $student->id,
            'teacher_id' => $this->resolveTeacherId($request, $student),
            'surah_id' => $validated['surah_id'],
            'ayah_start' => $validated['ayah_start'],
            'ayah_end' => $validated['ayah_end'],
            'submission_type' => $validated['submission_type'],
            'score' => $validated['score'] ?? null,
            'status' => $validated['status'],
            'submitted_at' => $validated['submitted_at'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('hafalan-records.index')
            ->with('success', 'Data hafalan berhasil diperbarui.');
    }

    public function destroy(HafalanRecord $hafalanRecord): RedirectResponse
    {
        Gate::authorize('delete', $hafalanRecord);

        $hafalanRecord->delete();

        return redirect()
            ->route('hafalan-records.index')
            ->with('success', 'Data hafalan berhasil dihapus.');
    }

    private function validateRecord(Request $request, UserAccessService $accessService): array
    {
        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'surah_id' => ['required', 'integer', 'exists:surahs,id'],
            'ayah_start' => ['required', 'integer', 'min:1'],
            'ayah_end' => ['required', 'integer', 'min:1', 'gte:ayah_start'],
            'submission_type' => ['required', Rule::in(['new', 'continuation', 'revision'])],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['required', Rule::in(['passed', 'repeat', 'needs_improvement'])],
            'submitted_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validator->after(function ($validator) use ($request, $visibleStudentIds) {
            if (! $visibleStudentIds->contains((int) $request->input('student_id'))) {
                $validator->errors()->add('student_id', 'Santri tidak boleh diakses oleh akun ini.');
            }

            $surah = Surah::find($request->input('surah_id'));

            if ($surah && (int) $request->input('ayah_end') > (int) $surah->total_ayah) {
                $validator->errors()->add('ayah_end', "Ayat akhir tidak boleh melebihi {$surah->total_ayah}.");
            }
        });

        return $validator->validate();
    }

    private function resolveTeacherId(Request $request, Student $student): ?int
    {
        $user = $request->user();

        if ($user?->hasRole('teacher')) {
            return $user->teacherProfile?->id;
        }

        return $student->teacher_id;
    }
}