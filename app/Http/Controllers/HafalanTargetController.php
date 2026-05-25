<?php

namespace App\Http\Controllers;

use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\Surah;
use App\Services\UserAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HafalanTargetController extends Controller
{
    public function index(Request $request, UserAccessService $accessService): View
    {
        Gate::authorize('viewAny', HafalanTarget::class);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $targets = HafalanTarget::query()
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
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('target_date')
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

        return view('hafalan-targets.index', compact('targets', 'students', 'surahs'));
    }

    public function create(Request $request, UserAccessService $accessService): View
    {
        Gate::authorize('create', HafalanTarget::class);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $students = Student::query()
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        return view('hafalan-targets.create', compact('students', 'surahs'));
    }

    public function store(Request $request, UserAccessService $accessService): RedirectResponse
    {
        Gate::authorize('create', HafalanTarget::class);

        $validated = $this->validateTarget($request, $accessService);

        $student = Student::findOrFail($validated['student_id']);

        HafalanTarget::create([
            'student_id' => $student->id,
            'teacher_id' => $this->resolveTeacherId($request, $student),
            'surah_id' => $validated['surah_id'],
            'ayah_start' => $validated['ayah_start'],
            'ayah_end' => $validated['ayah_end'],
            'target_date' => $validated['target_date'],
            'status' => $validated['status'] ?? 'active',
            'completed_at' => ($validated['status'] ?? 'active') === 'completed'
                ? ($validated['completed_at'] ?? now())
                : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('hafalan-targets.index')
            ->with('success', 'Target hafalan berhasil ditambahkan.');
    }

    public function show(HafalanTarget $hafalanTarget): View
    {
        Gate::authorize('view', $hafalanTarget);

        $hafalanTarget->load(['student.classRoom', 'student.program', 'surah', 'teacher.user']);

        return view('hafalan-targets.show', [
            'hafalanTarget' => $hafalanTarget,
            'target' => $hafalanTarget,
        ]);
    }

    public function edit(Request $request, HafalanTarget $hafalanTarget, UserAccessService $accessService): View
    {
        Gate::authorize('update', $hafalanTarget);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $students = Student::query()
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        return view('hafalan-targets.edit', [
            'hafalanTarget' => $hafalanTarget,
            'target' => $hafalanTarget,
            'students' => $students,
            'surahs' => $surahs,
        ]);
    }

    public function update(Request $request, HafalanTarget $hafalanTarget, UserAccessService $accessService): RedirectResponse
    {
        Gate::authorize('update', $hafalanTarget);

        $validated = $this->validateTarget($request, $accessService);

        $student = Student::findOrFail($validated['student_id']);

        $status = $validated['status'] ?? $hafalanTarget->status;

        $hafalanTarget->update([
            'student_id' => $student->id,
            'teacher_id' => $this->resolveTeacherId($request, $student),
            'surah_id' => $validated['surah_id'],
            'ayah_start' => $validated['ayah_start'],
            'ayah_end' => $validated['ayah_end'],
            'target_date' => $validated['target_date'],
            'status' => $status,
            'completed_at' => $status === 'completed'
                ? ($validated['completed_at'] ?? $hafalanTarget->completed_at ?? now())
                : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('hafalan-targets.index')
            ->with('success', 'Target hafalan berhasil diperbarui.');
    }

    public function complete(HafalanTarget $hafalanTarget): RedirectResponse
    {
        Gate::authorize('update', $hafalanTarget);

        $hafalanTarget->update([
            'status' => 'completed',
            'completed_at' => $hafalanTarget->completed_at ?? now(),
        ]);

        return redirect()
            ->route('hafalan-targets.index')
            ->with('success', 'Target hafalan berhasil ditandai selesai.');
    }

    public function destroy(HafalanTarget $hafalanTarget): RedirectResponse
    {
        Gate::authorize('delete', $hafalanTarget);

        $hafalanTarget->delete();

        return redirect()
            ->route('hafalan-targets.index')
            ->with('success', 'Target hafalan berhasil dihapus.');
    }

    private function validateTarget(Request $request, UserAccessService $accessService): array
    {
        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'surah_id' => ['required', 'integer', 'exists:surahs,id'],
            'ayah_start' => ['required', 'integer', 'min:1'],
            'ayah_end' => ['required', 'integer', 'min:1', 'gte:ayah_start'],
            'target_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in(['active', 'completed', 'cancelled'])],
            'completed_at' => ['nullable', 'date'],
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