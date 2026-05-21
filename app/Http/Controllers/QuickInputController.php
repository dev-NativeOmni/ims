<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHafalanRecordRequest;
use App\Http\Requests\StoreMurajaahRecordRequest;
use App\Models\HafalanRecord;
use App\Models\MurajaahRecord;
use App\Models\Student;
use App\Models\Surah;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class QuickInputController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $students = $this->accessibleStudents($user);
        $studentIds = $students->pluck('id');

        return view('quick-inputs.index', [
            'students' => $students,
            'surahs' => Surah::query()
                ->orderBy('number')
                ->get([
                    'id',
                    'number',
                    'name_latin',
                    'total_ayah',
                ]),

            'latestHafalanRecords' => HafalanRecord::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surah',
                ])
                ->when(
                    $studentIds->isNotEmpty(),
                    fn ($query) => $query->whereIn('student_id', $studentIds),
                    fn ($query) => $query->whereRaw('1 = 0')
                )
                ->latest('submitted_at')
                ->latest()
                ->limit(8)
                ->get(),

            'latestMurajaahRecords' => MurajaahRecord::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surah',
                ])
                ->when(
                    $studentIds->isNotEmpty(),
                    fn ($query) => $query->whereIn('student_id', $studentIds),
                    fn ($query) => $query->whereRaw('1 = 0')
                )
                ->latest('reviewed_at')
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }

    public function storeHafalan(StoreHafalanRecordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $student = Student::query()->findOrFail($validated['student_id']);
        $teacherId = $this->resolveTeacherId($request->user(), $student);

        if (! $teacherId) {
            return back()
                ->withInput()
                ->with('error', 'Santri ini belum memiliki guru pembimbing.');
        }

        HafalanRecord::create([
            'student_id' => $student->id,
            'teacher_id' => $teacherId,
            'surah_id' => $validated['surah_id'],
            'ayah_start' => $validated['ayah_start'],
            'ayah_end' => $validated['ayah_end'],
            'submission_type' => $validated['submission_type'],
            'score' => $validated['score'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'submitted_at' => $validated['submitted_at'] ?? now()->toDateString(),
        ]);

        return redirect()
            ->route('quick-inputs.index')
            ->with('success', 'Setoran hafalan berhasil disimpan.');
    }

    public function storeMurajaah(StoreMurajaahRecordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $student = Student::query()->findOrFail($validated['student_id']);
        $teacherId = $this->resolveTeacherId($request->user(), $student);

        if (! $teacherId) {
            return back()
                ->withInput()
                ->with('error', 'Santri ini belum memiliki guru pembimbing.');
        }

        MurajaahRecord::create([
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
            'notes' => $validated['notes'] ?? null,
            'reviewed_at' => $validated['reviewed_at'] ?? now()->toDateString(),
        ]);

        return redirect()
            ->route('quick-inputs.index')
            ->with('success', 'Murajaah berhasil disimpan.');
    }

    private function accessibleStudents(User $user): Collection
    {
        if ($user->hasRole('teacher')) {
            $teacherId = $user->teacherProfile?->id;

            if (! $teacherId) {
                return collect();
            }

            return Student::query()
                ->with([
                    'classRoom.program',
                    'teacher.user',
                ])
                ->where('teacher_id', $teacherId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
        }

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return Student::query()
                ->with([
                    'classRoom.program',
                    'teacher.user',
                ])
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
        }

        return collect();
    }

    private function resolveTeacherId(User $user, Student $student): ?int
    {
        if ($user->hasRole('teacher')) {
            return $user->teacherProfile?->id;
        }

        return $student->teacher_id;
    }
}