<?php

namespace App\Http\Controllers;

use App\Models\HafalanRecord;
use App\Models\MurajaahRecord;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Services\UserAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class QuickInputController extends Controller
{
    public function index(Request $request, UserAccessService $accessService): View
    {
        Gate::authorize('create', HafalanRecord::class);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $students = Student::query()
            ->with([
                'user',
                'classRoom.program',
                'teacher.user',
            ])
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $latestHafalanRecords = HafalanRecord::query()
            ->with([
                'student.classRoom.program',
                'teacher.user',
                'surah',
            ])
            ->whereIn('student_id', $visibleStudentIds)
            ->latest('submitted_at')
            ->latest()
            ->limit(5)
            ->get();

        $latestMurajaahRecords = MurajaahRecord::query()
            ->with([
                'student.classRoom.program',
                'teacher.user',
                'surah',
            ])
            ->whereIn('student_id', $visibleStudentIds)
            ->latest('reviewed_at')
            ->latest()
            ->limit(5)
            ->get();

        $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = \App\Models\ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        return view('quick-inputs.index', [
            'students' => $students,
            'classRooms' => $classRooms,
            'surahs' => $surahs,

            // Nama variable utama yang dipakai view.
            'latestHafalanRecords' => $latestHafalanRecords,
            'latestMurajaahRecords' => $latestMurajaahRecords,

            // Alias pengaman kalau ada bagian view lama yang masih pakai nama ini.
            'recentHafalanRecords' => $latestHafalanRecords,
            'recentMurajaahRecords' => $latestMurajaahRecords,
        ]);
    }

    public function storeHafalan(Request $request, UserAccessService $accessService): RedirectResponse
    {
        Gate::authorize('create', HafalanRecord::class);

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

            $surah = Surah::query()->find($request->input('surah_id'));

            if ($surah && (int) $request->input('ayah_end') > (int) $surah->total_ayah) {
                $validator->errors()->add('ayah_end', "Ayat akhir tidak boleh melebihi {$surah->total_ayah}.");
            }
        });

        $validated = $validator->validate();

        $student = Student::query()->findOrFail($validated['student_id']);

        $teacherId = $this->resolveTeacherId($request, $student);

        if (! $teacherId) {
            return back()
                ->withInput()
                ->with('error', 'Santri ini belum memiliki guru pembimbing. Isi dulu guru pembimbing pada data santri.');
        }

        HafalanRecord::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacherId,
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
            ->route('quick-inputs.index')
            ->with('success', 'Setoran hafalan berhasil disimpan.');
    }

    public function storeMurajaah(Request $request, UserAccessService $accessService): RedirectResponse
    {
        Gate::authorize('create', MurajaahRecord::class);

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

            $surah = Surah::query()->find($request->input('surah_id'));

            if ($surah && (int) $request->input('ayah_end') > (int) $surah->total_ayah) {
                $validator->errors()->add('ayah_end', "Ayat akhir tidak boleh melebihi {$surah->total_ayah}.");
            }
        });

        $validated = $validator->validate();

        $student = Student::query()->findOrFail($validated['student_id']);

        $teacherId = $this->resolveTeacherId($request, $student);

        if (! $teacherId) {
            return back()
                ->withInput()
                ->with('error', 'Santri ini belum memiliki guru pembimbing. Isi dulu guru pembimbing pada data santri.');
        }

        MurajaahRecord::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacherId,
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
            ->route('quick-inputs.index')
            ->with('success', 'Data murajaah berhasil disimpan.');
    }

    private function resolveTeacherId(Request $request, Student $student): ?int
    {
        $user = $request->user();

        if ($user?->hasRole('teacher')) {
            return TeacherProfile::query()
                ->where('user_id', $user->id)
                ->value('id');
        }

        return $student->teacher_id;
    }
}