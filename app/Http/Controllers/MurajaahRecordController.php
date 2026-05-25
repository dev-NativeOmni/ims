<?php

namespace App\Http\Controllers;

use App\Models\MurajaahRecord;
use App\Models\Student;
use App\Models\Surah;
use App\Services\UserAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MurajaahRecordController extends Controller
{
    public function index(Request $request, UserAccessService $accessService): View
    {
        Gate::authorize('viewAny', MurajaahRecord::class);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $records = MurajaahRecord::query()
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
            ->latest('reviewed_at')
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

        return view('murajaah-records.index', compact('records', 'students', 'surahs'));
    }

    public function create(Request $request, UserAccessService $accessService): View
    {
        Gate::authorize('create', MurajaahRecord::class);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $students = Student::query()
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        return view('murajaah-records.create', compact('students', 'surahs'));
    }

    public function store(Request $request, UserAccessService $accessService): RedirectResponse
    {
        Gate::authorize('create', MurajaahRecord::class);

        $validated = $this->validateRecord($request, $accessService);

        $student = Student::findOrFail($validated['student_id']);

        MurajaahRecord::create([
            'student_id' => $student->id,
            'teacher_id' => $this->resolveTeacherId($request, $student),
            'surah_id' => $validated['surah_id'],
            'ayah_start' => $validated['ayah_start'],
            'ayah_end' => $validated['ayah_end'],
            'fluency_score' => $validated['fluency_score'] ?? null,
            'tajwid_score' => $validated['tajwid_score'] ?? null,
            'makhraj_score' => $validated['makhraj_score'] ?? null,
            'overall_score' => $validated['overall_score'] ?? null,
            'status' => $validated['status'],
            'reviewed_at' => $validated['reviewed_at'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('murajaah-records.index')
            ->with('success', 'Data murajaah berhasil ditambahkan.');
    }

    public function show(MurajaahRecord $murajaahRecord): View
    {
        Gate::authorize('view', $murajaahRecord);

        $murajaahRecord->load(['student.classRoom', 'student.program', 'surah', 'teacher.user']);

        return view('murajaah-records.show', [
            'murajaahRecord' => $murajaahRecord,
            'record' => $murajaahRecord,
        ]);
    }

    public function edit(Request $request, MurajaahRecord $murajaahRecord, UserAccessService $accessService): View
    {
        Gate::authorize('update', $murajaahRecord);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $students = Student::query()
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        return view('murajaah-records.edit', [
            'murajaahRecord' => $murajaahRecord,
            'record' => $murajaahRecord,
            'students' => $students,
            'surahs' => $surahs,
        ]);
    }

    public function update(Request $request, MurajaahRecord $murajaahRecord, UserAccessService $accessService): RedirectResponse
    {
        Gate::authorize('update', $murajaahRecord);

        $validated = $this->validateRecord($request, $accessService);

        $student = Student::findOrFail($validated['student_id']);

        $murajaahRecord->update([
            'student_id' => $student->id,
            'teacher_id' => $this->resolveTeacherId($request, $student),
            'surah_id' => $validated['surah_id'],
            'ayah_start' => $validated['ayah_start'],
            'ayah_end' => $validated['ayah_end'],
            'fluency_score' => $validated['fluency_score'] ?? null,
            'tajwid_score' => $validated['tajwid_score'] ?? null,
            'makhraj_score' => $validated['makhraj_score'] ?? null,
            'overall_score' => $validated['overall_score'] ?? null,
            'status' => $validated['status'],
            'reviewed_at' => $validated['reviewed_at'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('murajaah-records.index')
            ->with('success', 'Data murajaah berhasil diperbarui.');
    }

    public function destroy(MurajaahRecord $murajaahRecord): RedirectResponse
    {
        Gate::authorize('delete', $murajaahRecord);

        $murajaahRecord->delete();

        return redirect()
            ->route('murajaah-records.index')
            ->with('success', 'Data murajaah berhasil dihapus.');
    }

    private function validateRecord(Request $request, UserAccessService $accessService): array
    {
        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'surah_id' => ['required', 'integer', 'exists:surahs,id'],
            'ayah_start' => ['required', 'integer', 'min:1'],
            'ayah_end' => ['required', 'integer', 'min:1', 'gte:ayah_start'],
            'fluency_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'tajwid_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'makhraj_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'overall_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['required', Rule::in(['passed', 'repeat', 'needs_improvement'])],
            'reviewed_at' => ['required', 'date'],
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