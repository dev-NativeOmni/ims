<?php

namespace App\Http\Controllers;

use App\Models\HafalanRecord;
use App\Models\MurajaahRecord;
use App\Models\UmmiRecord;
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

        $latestUmmiRecords = UmmiRecord::query()
            ->with([
                'student.classRoom.program',
                'teacher.user',
                'surah',
            ])
            ->whereIn('student_id', $visibleStudentIds)
            ->latest('tanggal')
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
            'latestUmmiRecords' => $latestUmmiRecords,

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
            'surah_end_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'ayah_start' => ['required', 'integer', 'min:1'],
            'ayah_end' => ['required', 'integer', 'min:1'],
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

            $surahStart = Surah::query()->find($request->input('surah_id'));
            $surahEndId = $request->input('surah_end_id') ?: $request->input('surah_id');
            $surahEnd = Surah::query()->find($surahEndId);

            if ($surahStart && $surahEnd) {
                if ($surahEnd->number < $surahStart->number) {
                    $validator->errors()->add('surah_end_id', 'Surah akhir tidak boleh mendahului surah mulai.');
                }

                if ((int) $surahEndId === (int) $request->input('surah_id')) {
                    if ((int) $request->input('ayah_end') < (int) $request->input('ayah_start')) {
                        $validator->errors()->add('ayah_end', 'Ayat akhir harus lebih besar atau sama dengan ayat mulai.');
                    }
                }

                if ((int) $request->input('ayah_end') > (int) $surahEnd->total_ayah) {
                    $validator->errors()->add('ayah_end', "Ayat akhir tidak boleh melebihi {$surahEnd->total_ayah}.");
                }
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

        $surahStartId = (int) $validated['surah_id'];
        $surahEndId = (int) ($validated['surah_end_id'] ?? $surahStartId);

        if ($surahStartId === $surahEndId) {
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
        } else {
            $surahStart = Surah::findOrFail($surahStartId);
            $surahEnd = Surah::findOrFail($surahEndId);

            $surahs = Surah::whereBetween('number', [$surahStart->number, $surahEnd->number])
                ->orderBy('number')
                ->get();

            \Illuminate\Support\Facades\DB::transaction(function () use ($surahs, $surahStart, $surahEnd, $validated, $student, $teacherId) {
                foreach ($surahs as $surah) {
                    $recordData = [
                        'student_id' => $student->id,
                        'teacher_id' => $teacherId,
                        'surah_id' => $surah->id,
                        'submission_type' => $validated['submission_type'],
                        'score' => $validated['score'] ?? null,
                        'status' => $validated['status'],
                        'submitted_at' => $validated['submitted_at'],
                        'notes' => $validated['notes'] ?? null,
                    ];

                    if ($surah->id === $surahStart->id) {
                        $recordData['ayah_start'] = $validated['ayah_start'];
                        $recordData['ayah_end'] = $surah->total_ayah;
                    } elseif ($surah->id === $surahEnd->id) {
                        $recordData['ayah_start'] = 1;
                        $recordData['ayah_end'] = $validated['ayah_end'];
                    } else {
                        $recordData['ayah_start'] = 1;
                        $recordData['ayah_end'] = $surah->total_ayah;
                    }

                    HafalanRecord::query()->create($recordData);
                }
            });
        }

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
            'surah_end_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'ayah_start' => ['required', 'integer', 'min:1'],
            'ayah_end' => ['required', 'integer', 'min:1'],
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

            $surahStart = Surah::query()->find($request->input('surah_id'));
            $surahEndId = $request->input('surah_end_id') ?: $request->input('surah_id');
            $surahEnd = Surah::query()->find($surahEndId);

            if ($surahStart && $surahEnd) {
                if ($surahEnd->number < $surahStart->number) {
                    $validator->errors()->add('surah_end_id', 'Surah akhir tidak boleh mendahului surah mulai.');
                }

                if ((int) $surahEndId === (int) $request->input('surah_id')) {
                    if ((int) $request->input('ayah_end') < (int) $request->input('ayah_start')) {
                        $validator->errors()->add('ayah_end', 'Ayat akhir harus lebih besar atau sama dengan ayat mulai.');
                    }
                }

                if ((int) $request->input('ayah_end') > (int) $surahEnd->total_ayah) {
                    $validator->errors()->add('ayah_end', "Ayat akhir tidak boleh melebihi {$surahEnd->total_ayah}.");
                }
            }
        });

        $validated = $validator->validate();

        if (
            blank($validated['overall_score'] ?? null)
            && filled($validated['fluency_score'] ?? null)
            && filled($validated['tajwid_score'] ?? null)
            && filled($validated['makhraj_score'] ?? null)
        ) {
            $validated['overall_score'] = (int) round((
                (float) $validated['fluency_score']
                + (float) $validated['tajwid_score']
                + (float) $validated['makhraj_score']
            ) / 3);
        }

        $student = Student::query()->findOrFail($validated['student_id']);

        $teacherId = $this->resolveTeacherId($request, $student);

        if (! $teacherId) {
            return back()
                ->withInput()
                ->with('error', 'Santri ini belum memiliki guru pembimbing. Isi dulu guru pembimbing pada data santri.');
        }

        $surahStartId = (int) $validated['surah_id'];
        $surahEndId = (int) ($validated['surah_end_id'] ?? $surahStartId);

        if ($surahStartId === $surahEndId) {
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
        } else {
            $surahStart = Surah::findOrFail($surahStartId);
            $surahEnd = Surah::findOrFail($surahEndId);

            $surahs = Surah::whereBetween('number', [$surahStart->number, $surahEnd->number])
                ->orderBy('number')
                ->get();

            \Illuminate\Support\Facades\DB::transaction(function () use ($surahs, $surahStart, $surahEnd, $validated, $student, $teacherId) {
                foreach ($surahs as $surah) {
                    $recordData = [
                        'student_id' => $student->id,
                        'teacher_id' => $teacherId,
                        'surah_id' => $surah->id,
                        'fluency_score' => $validated['fluency_score'] ?? null,
                        'tajwid_score' => $validated['tajwid_score'] ?? null,
                        'makhraj_score' => $validated['makhraj_score'] ?? null,
                        'overall_score' => $validated['overall_score'] ?? null,
                        'status' => $validated['status'],
                        'reviewed_at' => $validated['reviewed_at'],
                        'notes' => $validated['notes'] ?? null,
                    ];

                    if ($surah->id === $surahStart->id) {
                        $recordData['ayah_start'] = $validated['ayah_start'];
                        $recordData['ayah_end'] = $surah->total_ayah;
                    } elseif ($surah->id === $surahEnd->id) {
                        $recordData['ayah_start'] = 1;
                        $recordData['ayah_end'] = $validated['ayah_end'];
                    } else {
                        $recordData['ayah_start'] = 1;
                        $recordData['ayah_end'] = $surah->total_ayah;
                    }

                    MurajaahRecord::query()->create($recordData);
                }
            });
        }

        return redirect()
            ->route('quick-inputs.index')
            ->with('success', 'Data murajaah berhasil disimpan.');
    }

    public function storeUmmi(Request $request, UserAccessService $accessService): RedirectResponse
    {
        Gate::authorize('create', HafalanRecord::class);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'tatap_muka' => ['required', 'integer', 'min:1'],
            'tanggal' => ['required', 'date'],
            'hafalan_surah_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'hafalan_ayah' => ['nullable', 'string', 'max:100'],
            'hafalan_surah_ids' => ['nullable', 'array'],
            'hafalan_surah_ids.*' => ['nullable', 'integer', 'exists:surahs,id'],
            'hafalan_ayahs' => ['nullable', 'array'],
            'hafalan_ayahs.*' => ['nullable', 'string', 'max:100'],
            'ummi_jilid' => ['nullable', 'string', 'max:150'],
            'ummi_halaman' => ['nullable', 'string', 'max:100'],
            'materi' => ['nullable', 'string', 'max:255'],
            'nilai' => ['nullable', 'string', 'max:50'],
            'disimak_guru' => ['required', Rule::in(['Ya', 'Tidak'])],
            'disimak_ortu' => ['required', Rule::in(['Ya', 'Tidak'])],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ]);

        $validator->after(function ($validator) use ($request, $visibleStudentIds) {
            if (! $visibleStudentIds->contains((int) $request->input('student_id'))) {
                $validator->errors()->add('student_id', 'Santri tidak boleh diakses oleh akun ini.');
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

        $hafalans = [];
        if ($request->has('hafalan_surah_ids')) {
            $surahIds = $request->input('hafalan_surah_ids');
            $ayahs = $request->input('hafalan_ayahs');
            foreach ($surahIds as $idx => $sid) {
                if (!empty($sid)) {
                    $hafalans[] = [
                        'surah_id' => (int) $sid,
                        'ayah' => $ayahs[$idx] ?? null
                    ];
                }
            }
        } elseif ($request->filled('hafalan_surah_id')) {
            $hafalans[] = [
                'surah_id' => (int) $request->input('hafalan_surah_id'),
                'ayah' => $request->input('hafalan_ayah')
            ];
        }

        if (empty($hafalans)) {
            UmmiRecord::query()->create([
                'student_id' => $student->id,
                'teacher_id' => $teacherId,
                'tatap_muka' => $validated['tatap_muka'],
                'tanggal' => $validated['tanggal'],
                'hafalan_surah_id' => null,
                'hafalan_ayah' => null,
                'ummi_jilid' => $validated['ummi_jilid'] ?? null,
                'ummi_halaman' => $validated['ummi_halaman'] ?? null,
                'materi' => $validated['materi'] ?? null,
                'nilai' => $validated['nilai'] ?? null,
                'disimak_guru' => $validated['disimak_guru'],
                'disimak_ortu' => $validated['disimak_ortu'],
                'keterangan' => $validated['keterangan'] ?? null,
            ]);
        } else {
            foreach ($hafalans as $hafalan) {
                UmmiRecord::query()->create([
                    'student_id' => $student->id,
                    'teacher_id' => $teacherId,
                    'tatap_muka' => $validated['tatap_muka'],
                    'tanggal' => $validated['tanggal'],
                    'hafalan_surah_id' => $hafalan['surah_id'],
                    'hafalan_ayah' => $hafalan['ayah'],
                    'ummi_jilid' => $validated['ummi_jilid'] ?? null,
                    'ummi_halaman' => $validated['ummi_halaman'] ?? null,
                    'materi' => $validated['materi'] ?? null,
                    'nilai' => $validated['nilai'] ?? null,
                    'disimak_guru' => $validated['disimak_guru'],
                    'disimak_ortu' => $validated['disimak_ortu'],
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);
            }
        }

        return redirect()
            ->route('quick-inputs.index')
            ->with('success', 'Catatan Tahsin UMMI berhasil disimpan.');
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