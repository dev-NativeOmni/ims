<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentPriorHafalan;
use App\Models\Surah;
use App\Models\TargetLock;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\AcademicCalendarService;
use App\Services\AutoHafalanTargetService;
use App\Services\HafalanProgressService;
use App\Services\HafalanTargetAutoCompletionService;
use App\Services\StudentProgressService;
use App\Services\TargetDeadlineService;
use App\Services\UmmiProgressService;
use App\Support\AcademicYear;
use App\Support\AyahCoverage;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class HafalanTargetController extends Controller
{
    public function index(Request $request, AutoHafalanTargetService $autoTargets, AcademicCalendarService $calendar): View
    {
        $visibleStudentIds = $this->visibleStudentIds($request->user());
        $activeProgram = $request->input('program', 'reguler');
        // Program Reguler (Kelas 11 & 12): isian per bulan memakai tabel yang sama dengan Target Triwulan.
        $gridMonth = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month')) ? $request->input('month') : now()->format('Y-m');
        $grid = $activeProgram === 'reguler' ? $this->targetGrid($request, $autoTargets, $calendar, $gridMonth) : null;

        $query = HafalanTarget::query()
            ->with([
                'student.classRoom.program',
                'surah',
                'teacher.user',
            ])
            ->whereIn('student_id', $visibleStudentIds)
            ->when($activeProgram === 'ummi', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('ummi_jilid')
                        ->orWhereHas('student.classRoom', function ($c) {
                            $c->where('name', 'like', 'X %')
                                ->orWhere('name', 'like', 'X-%')
                                ->orWhere('name', 'X');
                        });
                });
            })
            ->when($activeProgram === 'reguler', function ($q) {
                $q->whereNull('ummi_jilid');
            })
            ->when($request->filled('class_room_id'), function ($query) use ($request) {
                $query->whereHas('student', function ($q) use ($request) {
                    $q->where('class_room_id', $request->integer('class_room_id'));
                });
            })
            ->when($request->filled('teacher_id'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('teacher_id', $request->integer('teacher_id'))
                        ->orWhereHas('student', fn ($sq) => $sq->where('teacher_id', $request->integer('teacher_id')));
                });
            })
            ->when($request->filled('student_id'), function ($query) use ($request, $visibleStudentIds) {
                $studentId = (int) $request->input('student_id');

                if ($visibleStudentIds->contains($studentId)) {
                    $query->where('student_id', $studentId);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('surah_id'), function ($query) use ($request) {
                $query->where('surah_id', $request->input('surah_id'));
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('target_date', '>=', $request->input('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('target_date', '<=', $request->input('date_to'));
            })
            // Filter bulan deadline (Y-m).
            ->when(preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month')), function ($query) use ($request) {
                $month = Carbon::createFromFormat('Y-m-d', $request->input('month').'-01');
                $query->whereBetween('target_date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString().' 23:59:59']);
            });

        $targets = (clone $query)
            ->orderBy('target_date')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $activeStatuses = $this->activeTargetStatuses();

        $summary = [
            'total' => (clone $query)->count(),

            'active' => (clone $query)
                ->whereIn('status', $activeStatuses)
                ->count(),

            'planned' => (clone $query)
                ->where('status', 'planned')
                ->count(),

            'in_progress' => (clone $query)
                ->where('status', 'in_progress')
                ->count(),

            'completed' => (clone $query)
                ->where('status', 'completed')
                ->count(),

            'missed' => (clone $query)
                ->where('status', 'missed')
                ->count(),

            'cancelled' => (clone $query)
                ->where('status', 'cancelled')
                ->count(),

            'overdue' => (clone $query)
                ->whereIn('status', $activeStatuses)
                ->whereDate('target_date', '<', today())
                ->count(),
        ];

        $allVisibleStudents = Student::query()
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->get();

        $classRoomIds = $allVisibleStudents->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        $grade10ClassRooms = $classRooms->filter(function ($c) {
            $name = $c->name;
            $level = $c->level ?? '';

            return ((preg_match('/\bX\b/i', $name) && ! preg_match('/\b(XI|XII)\b/i', $name))
                || preg_match('/\b10\b/i', $name)
                || preg_match('/^X[-_\s]?E/i', $name)
                || preg_match('/kelas\s*(X|10)/i', $name)
                || (preg_match('/\bX\b/i', $level) && ! preg_match('/\b(XI|XII)\b/i', $level))
                || preg_match('/\b10\b/i', $level))
                && ! preg_match('/\b(XI|XII|11|12)\b/i', $name);
        })->values();

        $regulerClassRooms = $classRooms->reject(function ($c) use ($grade10ClassRooms) {
            return $grade10ClassRooms->contains('id', $c->id);
        })->values();

        $user = $request->user();
        $isTeacherOnly = $user?->hasRole('teacher') && ! $user?->hasAnyRole(['super_admin', 'admin']);

        if ($isTeacherOnly && $user->teacherProfile) {
            $teachers = TeacherProfile::query()
                ->with('user')
                ->where('id', $user->teacherProfile->id)
                ->get();
            $currentTeacherId = $user->teacherProfile->id;
        } else {
            $teachers = TeacherProfile::query()
                ->with('user')
                ->whereHas('user')
                ->orderBy('id')
                ->get()
                ->sortBy(fn ($t) => $t->user?->name)
                ->values();
            $currentTeacherId = $request->filled('teacher_id') ? (int) $request->input('teacher_id') : null;
        }

        $studentsQuery = Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->when($request->filled('class_room_id'), function ($q) use ($request) {
                $q->where('class_room_id', $request->integer('class_room_id'));
            })
            ->when($currentTeacherId, function ($q) use ($currentTeacherId) {
                $q->where('teacher_id', $currentTeacherId);
            });

        if ($activeProgram === 'ummi' && ! $request->filled('class_room_id')) {
            $grade10Ids = $grade10ClassRooms->pluck('id')->all();
            if (! empty($grade10Ids)) {
                $studentsQuery->whereIn('class_room_id', $grade10Ids);
            }
        }

        $students = $studentsQuery->orderBy('name')->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $statusOptions = $this->targetStatuses();

        // Pilihan filter bulan: 12 bulan ke belakang s.d. 2 bulan ke depan (terbaru di atas).
        $monthOptions = collect(range(2, -12))
            ->mapWithKeys(function ($offset) {
                $month = now()->startOfMonth()->addMonthsNoOverflow($offset);

                return [$month->format('Y-m') => $month->locale('id')->translatedFormat('F Y')];
            })
            ->all();

        return view('hafalan-targets.index', compact(
            'grid',
            'targets',
            'students',
            'classRooms',
            'grade10ClassRooms',
            'regulerClassRooms',
            'teachers',
            'surahs',
            'summary',
            'statusOptions',
            'activeProgram',
            'currentTeacherId',
            'isTeacherOnly',
            'monthOptions'
        ));
    }

    public function storeBulkReguler(Request $request): RedirectResponse
    {
        $this->authorize('create', HafalanTarget::class);
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $validated = $request->validate([
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'targets' => ['required', 'array'],
            'targets.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'targets.*.surah_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'targets.*.ayah' => ['nullable', 'integer', 'min:1'],
            'targets.*.target_date' => ['nullable', 'date'],
            'targets.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $count = 0;
        $locked = [];
        foreach ($validated['targets'] as $row) {
            if (empty($row['surah_id']) || empty($row['ayah'])) {
                continue;
            }

            $studentId = (int) $row['student_id'];
            if (! $visibleStudentIds->contains($studentId)) {
                continue;
            }

            $student = Student::find($studentId);
            if (! $student) {
                continue;
            }

            $targetDate = $row['target_date'] ?: now()->addWeeks(2)->toDateString();
            if (TargetLock::blocksStudent($student, $targetDate)) {
                $locked[] = $student->name;

                continue;
            }

            $teacherId = $this->resolveTeacherId($request, $student);

            HafalanTarget::create([
                'student_id' => $student->id,
                'teacher_id' => $teacherId,
                'surah_id' => $row['surah_id'],
                'ayah' => $row['ayah'],
                'target_date' => $targetDate,
                'notes' => $row['notes'] ?? null,
                'status' => $this->defaultOpenTargetStatus(),
            ]);
            $count++;
        }

        return redirect()
            ->route('hafalan-targets.index', ['program' => 'reguler', 'class_room_id' => $validated['class_room_id']])
            ->with('success', "Berhasil menyimpan {$count} target hafalan reguler.".($locked ? ' Dilewati (bulan terkunci): '.implode(', ', $locked).'.' : ''));
    }

    public function storeBulkUmmi(Request $request): RedirectResponse
    {
        $this->authorize('create', HafalanTarget::class);

        $validated = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teacher_profiles,id'],
            'class_room_id' => ['nullable', 'integer', 'exists:class_rooms,id'],
            'ummi_jilid' => ['required', 'string', 'max:100'],
            'halaman_peraga' => ['nullable', 'string', 'max:100'],
            'halaman_buku' => ['nullable', 'string', 'max:100'],
            'surah_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'ayah' => ['nullable', 'integer', 'min:1'],
            'target_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Ayat target hafalan Ummi tidak boleh melebihi jumlah ayat surah (kosong = sampai akhir surah).
        if (! empty($validated['ayah']) && ! empty($validated['surah_id'])
            && (int) $validated['ayah'] > (int) Surah::query()->whereKey($validated['surah_id'])->value('total_ayah')) {
            return back()->withInput()->withErrors(['ayah' => 'Ayat tidak boleh melebihi jumlah ayat surah.']);
        }

        $user = $request->user();
        if ($user?->hasRole('teacher') && ! $user?->hasAnyRole(['super_admin', 'admin']) && $user->teacherProfile) {
            $teacherProfile = $user->teacherProfile;
        } else {
            $teacherProfile = TeacherProfile::findOrFail($validated['teacher_id']);
        }

        $studentsQuery = Student::query()
            ->where('teacher_id', $teacherProfile->id)
            ->where('status', 'active');

        if (! empty($validated['class_room_id'])) {
            $studentsQuery->where('class_room_id', (int) $validated['class_room_id']);
        } else {
            // Apply only to Grade 10 students (Never apply to Grade 11 / Grade 12)
            $studentsQuery->whereHas('classRoom', function ($q) {
                $q->where(function ($sq) {
                    $sq->where('name', 'like', '%X%')
                        ->orWhere('name', 'like', '%10%')
                        ->orWhere('level', 'like', '%X%')
                        ->orWhere('level', 'like', '%10%');
                })->where('name', 'not like', '%XI%')
                    ->where('name', 'not like', '%XII%')
                    ->where('name', 'not like', '%11%')
                    ->where('name', 'not like', '%12%');
            });
        }

        $students = $studentsQuery->get();

        $count = 0;
        $locked = [];
        foreach ($students as $student) {
            if (TargetLock::blocksStudent($student, $validated['target_date'])) {
                $locked[] = $student->name;

                continue;
            }
            HafalanTarget::create([
                'student_id' => $student->id,
                'teacher_id' => $teacherProfile->id,
                'ummi_jilid' => $validated['ummi_jilid'],
                'halaman_peraga' => $validated['halaman_peraga'] ?? null,
                'halaman_buku' => $validated['halaman_buku'] ?? null,
                'surah_id' => $validated['surah_id'] ?? null,
                'ayah' => ! empty($validated['surah_id']) ? ($validated['ayah'] ?? null) : null,
                'target_date' => $validated['target_date'],
                'notes' => $validated['notes'] ?? null,
                'status' => $this->defaultOpenTargetStatus(),
            ]);
            $count++;
        }

        $redirectParams = ['program' => 'ummi', 'teacher_id' => $validated['teacher_id']];
        if (! empty($validated['class_room_id'])) {
            $redirectParams['class_room_id'] = $validated['class_room_id'];
        }

        return redirect()
            ->route('hafalan-targets.index', $redirectParams)
            ->with('success', "Berhasil menyimpan target Ummi serentak untuk {$count} murid Kelas 10 di Halaqah Musyrif.".($locked ? ' Dilewati (bulan terkunci): '.implode(', ', $locked).'.' : ''));
    }

    public function bulkComplete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'target_ids' => ['required', 'array'],
            'target_ids.*' => ['required', 'integer', 'exists:hafalan_targets,id'],
        ]);

        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $targets = HafalanTarget::query()
            ->whereIn('id', $validated['target_ids'])
            ->whereIn('student_id', $visibleStudentIds)
            ->get();

        $count = 0;
        foreach ($targets as $target) {
            $this->authorize('update', $target);
            $target->update(['status' => 'completed', 'completed_at' => $target->completed_at ?? now()]
                + ($target->ummi_jilid ? $this->ummiPartStatuses('completed', (bool) $target->surah_id) : []));
            $count++;
        }

        return redirect()
            ->back()
            ->with('success', "Berhasil menandai {$count} target hafalan sebagai selesai.");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'target_ids' => ['required', 'array'],
            'target_ids.*' => ['required', 'integer', 'exists:hafalan_targets,id'],
        ]);

        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $targets = HafalanTarget::query()
            ->whereIn('id', $validated['target_ids'])
            ->whereIn('student_id', $visibleStudentIds)
            ->get();

        $count = 0;
        $locked = 0;
        foreach ($targets as $target) {
            $this->authorize('delete', $target);
            if ($target->student && TargetLock::blocksStudent($target->student, $target->target_date)) {
                $locked++;

                continue;
            }
            $target->delete();
            $count++;
        }

        return redirect()
            ->back()
            ->with('success', "Berhasil menghapus {$count} target hafalan terpilih.".($locked ? " {$locked} target di bulan terkunci tidak dihapus." : ''));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', HafalanTarget::class);
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $students = Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $statusOptions = $this->targetStatuses();

        return view('hafalan-targets.create', compact(
            'students',
            'classRooms',
            'surahs',
            'statusOptions'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', HafalanTarget::class);
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $validated = $this->validateTarget($request, $visibleStudentIds);

        $student = Student::query()->findOrFail($validated['student_id']);

        $data = $this->targetPayload($validated);
        $data['student_id'] = $student->id;

        if (! empty($data['target_date']) && TargetLock::blocksStudent($student, $data['target_date'])) {
            return back()->withInput()->withErrors(['target_date' => TargetLock::message($student, $data['target_date'])]);
        }

        $data['teacher_id'] = $this->resolveTeacherId($request, $student);

        if (empty($data['status'])) {
            $data['status'] = $this->defaultOpenTargetStatus();
        }

        HafalanTarget::query()->create($data);

        return redirect()
            ->route('hafalan-targets.index')
            ->with('success', 'Target hafalan berhasil ditambahkan.');
    }

    public function show(Request $request, HafalanTarget $hafalanTarget): View
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);

        $hafalanTarget->load([
            'student.classRoom.program',
            'surah',
            'teacher.user',
        ]);

        $target = $hafalanTarget;

        return view('hafalan-targets.show', compact(
            'hafalanTarget',
            'target'
        ));
    }

    public function edit(Request $request, HafalanTarget $hafalanTarget): View
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);
        $this->authorize('update', $hafalanTarget);

        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $students = Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->orderBy('name')
            ->get();

        $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $statusOptions = $this->targetStatuses();

        $target = $hafalanTarget;

        return view('hafalan-targets.edit', compact(
            'hafalanTarget',
            'target',
            'students',
            'classRooms',
            'surahs',
            'statusOptions'
        ));
    }

    public function update(Request $request, HafalanTarget $hafalanTarget): RedirectResponse
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);
        $this->authorize('update', $hafalanTarget);

        // Bulan terkunci: isian beku (status tetap bisa lewat tombol Selesai/Terlewat & otomatis).
        $lockedDate = collect([$hafalanTarget->target_date, $request->input('target_date')])->filter()
            ->first(fn ($date) => $hafalanTarget->student && TargetLock::blocksStudent($hafalanTarget->student, $date));
        if ($lockedDate) {
            return back()->withInput()->withErrors(['target_date' => TargetLock::message($hafalanTarget->student, $lockedDate)]);
        }

        $visibleStudentIds = $this->visibleStudentIds($request->user());

        if ($hafalanTarget->ummi_jilid) {
            // Target Ummi (Jilid & Halaman + surah/ayat opsional); murid tetap.
            $data = $this->validateUmmiTarget($request);
        } else {
            $validated = $this->validateTarget($request, $visibleStudentIds);
            $student = Student::query()->findOrFail($validated['student_id']);

            $data = $this->targetPayload($validated);
            $data['student_id'] = $student->id;
            $data['teacher_id'] = $this->resolveTeacherId($request, $student);
        }

        // Target otomatis yang diedit guru menjadi target guru: tidak ditimpa lagi oleh perhitungan otomatis.
        $data['auto_month'] = null;

        // Deadline: sama dengan hari aktif terakhir bulannya = otomatis; tanggal lain = manual (lebih tinggi).
        if (! empty($data['target_date'])) {
            $date = Carbon::parse($data['target_date'])->toDateString();
            $data['target_date'] = $date;
            $data['deadline_manual'] = $date !== app(TargetDeadlineService::class)->forMonth(Carbon::parse($date))->toDateString();
        }

        // Tanggal selesai mengikuti status.
        if (($data['status'] ?? $hafalanTarget->status) === 'completed') {
            $data['completed_at'] = $hafalanTarget->completed_at ?? now();
        } elseif (isset($data['status'])) {
            $data['completed_at'] = null;
        }

        // Target Ummi: status Buku & Hafalan mengikuti status yang dipilih; Aktif = dinilai ulang.
        if ($hafalanTarget->ummi_jilid) {
            $data += $this->ummiPartStatuses($data['status'] ?? $hafalanTarget->status, ! empty($data['surah_id']));
        }

        $hafalanTarget->update($data);

        if ($hafalanTarget->ummi_jilid && $hafalanTarget->status === 'active') {
            app(HafalanTargetAutoCompletionService::class)->refresh($hafalanTarget->fresh());
        }

        // Kembali ke daftar dengan filter yang sama (hanya URL aplikasi ini).
        $back = (string) $request->input('back');

        return redirect()
            ->to(str_starts_with($back, url('/hafalan-targets')) ? $back : route('hafalan-targets.index'))
            ->with('success', 'Target hafalan berhasil diperbarui.');
    }

    public function destroy(Request $request, HafalanTarget $hafalanTarget): RedirectResponse
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);
        $this->authorize('delete', $hafalanTarget);

        if ($hafalanTarget->student && TargetLock::blocksStudent($hafalanTarget->student, $hafalanTarget->target_date)) {
            return back()->with('error', TargetLock::message($hafalanTarget->student, $hafalanTarget->target_date));
        }

        $hafalanTarget->delete();

        return redirect()
            ->route('hafalan-targets.index')
            ->with('success', 'Target hafalan berhasil dihapus.');
    }

    public function complete(Request $request, HafalanTarget $hafalanTarget): RedirectResponse
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);
        $this->authorize('update', $hafalanTarget);

        $data = ['status' => 'completed']
            + ($hafalanTarget->ummi_jilid ? $this->ummiPartStatuses('completed', (bool) $hafalanTarget->surah_id) : []);

        if (Schema::hasColumn('hafalan_targets', 'completed_at')) {
            $data['completed_at'] = now();
        }

        $hafalanTarget->update($data);

        return redirect()
            ->back()
            ->with('success', 'Target hafalan ditandai selesai.');
    }

    public function markMissed(Request $request, HafalanTarget $hafalanTarget): RedirectResponse
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);
        $this->authorize('update', $hafalanTarget);

        $hafalanTarget->update(['status' => 'missed']
            + ($hafalanTarget->ummi_jilid ? $this->ummiPartStatuses('missed', (bool) $hafalanTarget->surah_id) : []));

        return redirect()
            ->back()
            ->with('success', 'Target hafalan ditandai terlewat.');
    }

    private function validateTarget(Request $request, Collection $visibleStudentIds): array
    {
        $statuses = $this->targetStatuses();

        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'surah_id' => ['required', 'integer', 'exists:surahs,id'],
            'ayah' => ['required', 'integer', 'min:1'],
            'target_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in($statuses)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validator->after(function ($validator) use ($request, $visibleStudentIds) {
            $studentId = (int) $request->input('student_id');

            if (! $visibleStudentIds->contains($studentId)) {
                $validator->errors()->add(
                    'student_id',
                    'Murid tidak boleh diakses oleh akun ini.'
                );
            }

            $surah = Surah::query()->find($request->input('surah_id'));

            if ($surah && isset($surah->total_ayah)) {
                if ((int) $request->input('ayah') > (int) $surah->total_ayah) {
                    $validator->errors()->add(
                        'ayah',
                        'Ayat tidak boleh melebihi jumlah ayat surah.'
                    );
                }
            }
        });

        return $validator->validate();
    }

    /**
     * Validasi edit target Ummi: Jilid wajib, halaman peraga/buku & surah/ayat opsional
     * (ayat tidak boleh melebihi jumlah ayat surah; kosong = sampai akhir surah).
     */
    private function validateUmmiTarget(Request $request): array
    {
        $validated = $request->validate([
            'ummi_jilid' => ['required', 'string', 'max:100'],
            'halaman_peraga' => ['nullable', 'string', 'max:100'],
            'halaman_buku' => ['nullable', 'string', 'max:100'],
            'surah_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'ayah' => ['nullable', 'integer', 'min:1'],
            'target_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in($this->targetStatuses())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! empty($validated['ayah']) && ! empty($validated['surah_id'])
            && (int) $validated['ayah'] > (int) Surah::query()->whereKey($validated['surah_id'])->value('total_ayah')) {
            throw ValidationException::withMessages(['ayah' => 'Ayat tidak boleh melebihi jumlah ayat surah.']);
        }

        $validated['ayah'] = ! empty($validated['surah_id']) ? ($validated['ayah'] ?? null) : null;
        $validated['surah_id'] = $validated['surah_id'] ?? null;

        return $validated;
    }

    private function targetPayload(array $validated): array
    {
        $allowedColumns = Schema::getColumnListing('hafalan_targets');

        $payload = [];

        foreach ($validated as $key => $value) {
            if (in_array($key, $allowedColumns, true)) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }

    private function authorizeTargetAccess(Request $request, HafalanTarget $target): void
    {
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        abort_unless(
            $visibleStudentIds->contains((int) $target->student_id),
            403,
            'Target hafalan tidak boleh diakses oleh akun ini.'
        );
    }

    /**
     * Target Triwulan: per murid kelas 11/12, target manual tiap bulan (surah & ayat) dengan
     * deadline = pertemuan aktif terakhir bulan itu. Target triwulan = bulan terakhir yang terisi.
     */
    public function term(Request $request, AutoHafalanTargetService $targets, AcademicCalendarService $calendar): View
    {
        $grid = $this->targetGrid($request, $targets, $calendar);

        return view('hafalan-targets.term', $grid + ['grid' => $grid]);
    }

    /**
     * Data tabel isian target per kelas (Target Triwulan, dan Target Bulanan untuk Kelas 11 & 12).
     * $month ("Y-m") = mode bulanan: hanya kolom bulan itu yang tampil & disimpan; ringkasan &
     * kolom capaian tetap dihitung per triwulan bulan tersebut.
     */
    private function targetGrid(Request $request, AutoHafalanTargetService $targets, AcademicCalendarService $calendar, ?string $month = null): array
    {
        $user = $request->user();
        $visibleStudentIds = $this->visibleStudentIds($user);
        [$periods, $period] = $this->termPeriods(
            $month ? $calendar->termStartDate(Carbon::createFromFormat('Y-m-d', $month.'-01'))->toDateString() : $request->input('period'),
            $calendar
        );

        $gradeElevenTwelveStudents = Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->get()
            ->reject(fn (Student $student) => $student->classRoom?->isGradeTen())
            ->values();

        $isTeacherOnly = $user?->hasRole('teacher') && ! $user?->hasAnyRole(['super_admin', 'admin']);
        $teachers = TeacherProfile::query()
            ->with('user')
            ->whereIn('id', $gradeElevenTwelveStudents->pluck('teacher_id')->filter()->unique())
            ->get()
            ->sortBy(fn ($teacher) => $teacher->user?->name)
            ->values();

        $currentTeacherId = $isTeacherOnly
            ? $user->teacherProfile?->id
            : ($request->filled('teacher_id') ? (int) $request->input('teacher_id') : null);

        $classDate = $this->termClassDate($period);
        $classRooms = $this->termClassRooms($visibleStudentIds, $currentTeacherId, $classDate);
        $selectedClass = $classRooms->firstWhere('id', (int) $request->input('class_room_id')) ?? $classRooms->first();

        $months = $selectedClass ? $targets->termMonths($selectedClass, Carbon::parse($period)) : [];
        $visibleMonths = $month && isset($months[$month]) ? [$month => $months[$month]] : $months;
        $rows = collect();
        if ($selectedClass) {
            $rows = $this->termStudents($selectedClass, $visibleStudentIds, $currentTeacherId, $classDate)
                ->map(fn (Student $student) => [
                    'student' => $student,
                    'plan' => $targets->termPlan($student, Carbon::parse($period), $selectedClass, $months),
                ]);
        }

        if ($month && isset($months[$month])) {
            // Ringkasan bulan itu saja.
            $cells = $rows->map(fn ($row) => $row['plan']['months'][$month] ?? []);
            $summary = [
                'students' => $rows->count(),
                'with_target' => $cells->filter(fn ($cell) => ($cell['target'] ?? null) !== null)->count(),
                'reached' => $cells->filter(fn ($cell) => ($cell['target_lines'] ?? 0) > 0 && ($cell['reached'] ?? false))->count(),
                'avg_progress' => $cells->isEmpty() ? 0 : (int) round($cells->avg(fn ($cell) => ($cell['target_lines'] ?? 0) > 0
                    ? min(100, ($cell['achieved_lines'] ?? 0) / $cell['target_lines'] * 100) : 0)),
            ];
        } else {
            $summary = [
                'students' => $rows->count(),
                'with_target' => $rows->filter(fn ($row) => $row['plan']['target'] !== null)->count(),
                'reached' => $rows->where('plan.reached', true)->count(),
                'avg_progress' => $rows->isEmpty() ? 0 : (int) round($rows->avg('plan.progress')),
            ];
        }

        // Pilihan bulan (mode bulanan): semua bulan dari triwulan yang tersedia, terbaru dulu.
        $monthChoices = collect($periods->keys())
            ->flatMap(fn ($start) => collect(range(0, 2))->map(fn ($i) => Carbon::parse($start)->addMonthsNoOverflow($i)))
            ->unique(fn ($date) => $date->format('Y-m'))
            ->sortByDesc(fn ($date) => $date->format('Y-m'))
            ->mapWithKeys(fn ($date) => [$date->format('Y-m') => $date->locale('id')->translatedFormat('F Y')]);

        return [
            'locks' => $selectedClass ? TargetLock::forMonths($selectedClass->id, array_keys($months)) : collect(),
            'canLock' => TargetLock::canLock($request->user()),
            'canUnlock' => TargetLock::canUnlock($request->user()),
            'periods' => $periods,
            'period' => $period,
            'month' => $month && isset($months[$month]) ? $month : null,
            'monthChoices' => $monthChoices,
            'teachers' => $isTeacherOnly && $currentTeacherId ? $teachers->where('id', $currentTeacherId)->values() : $teachers,
            'currentTeacherId' => $currentTeacherId,
            'isTeacherOnly' => $isTeacherOnly,
            'classRooms' => $classRooms,
            'selectedClass' => $selectedClass,
            'months' => $months,
            'visibleMonths' => $visibleMonths,
            'rows' => $rows,
            'summary' => $summary,
            'surahs' => Surah::query()->orderBy('number')->get(['id', 'number', 'name_latin', 'total_ayah']),
            'canEdit' => $request->user()->can('create', HafalanTarget::class),
        ];
    }

    /**
     * Simpan target 3 bulan untuk satu kelas sekaligus. Satu target per murid per bulan:
     * target yang sudah ada di bulan itu diperbarui, dikosongkan = dihapus.
     */
    public function storeTerm(Request $request, AutoHafalanTargetService $targets, AcademicCalendarService $calendar): RedirectResponse
    {
        $this->authorize('create', HafalanTarget::class);

        $user = $request->user();
        $isTeacherOnly = $user?->hasRole('teacher') && ! $user?->hasAnyRole(['super_admin', 'admin']);
        $currentTeacherId = $isTeacherOnly
            ? $user->teacherProfile?->id
            : ($request->filled('teacher_id') ? (int) $request->input('teacher_id') : null);

        $visibleStudentIds = $this->visibleStudentIds($user);
        [, $period] = $this->termPeriods($request->input('period'), $calendar);
        $classDate = $this->termClassDate($period);
        $selectedClass = $this->termClassRooms($visibleStudentIds, $currentTeacherId, $classDate)->firstWhere('id', (int) $request->input('class_room_id'))
            ?? $this->termClassRooms($visibleStudentIds, null, $classDate)->firstWhere('id', (int) $request->input('class_room_id'));
        abort_unless($selectedClass, 403, 'Kelas tidak boleh diakses oleh akun ini.');

        $months = $targets->termMonths($selectedClass, Carbon::parse($period));
        // Bulan terkunci untuk kelas ini tidak diubah sama sekali (isiannya juga tidak dikirim form).
        $lockedMonths = TargetLock::forMonths($selectedClass->id, array_keys($months));
        $termStart = reset($months)['start'];
        $termEnd = end($months)['end'];
        $months = array_diff_key($months, $lockedMonths->all());
        // Dari Target Bulanan: hanya bulan itu yang disimpan; bulan lain di triwulan tidak disentuh.
        if ($request->filled('month')) {
            $months = array_intersect_key($months, [(string) $request->input('month') => true]);
        }
        $students = $this->termStudents($selectedClass, $visibleStudentIds, $currentTeacherId, $classDate)->keyBy('id');
        $surahs = Surah::query()->get(['id', 'name_latin', 'total_ayah'])->keyBy('id');
        $input = $request->input('targets', []);

        // Validasi dulu seluruh isian, simpan hanya bila semuanya benar.
        $errors = [];
        $entries = [];
        foreach ($students as $student) {
            foreach ($months as $monthKey => $month) {
                $cell = $input[$student->id][$monthKey] ?? [];
                $surahId = (int) ($cell['surah_id'] ?? 0);
                $ayah = (int) ($cell['ayah'] ?? 0);
                $field = "targets.{$student->id}.{$monthKey}";

                if (! $surahId && ! $ayah) {
                    $entries[] = [$student, $monthKey, null, null];

                    continue;
                }
                $surah = $surahs->get($surahId);
                if (! $surah) {
                    $errors[$field] = "{$student->name} ({$month['label']}): pilih surah target.";
                } elseif ($ayah < 1 || $ayah > (int) $surah->total_ayah) {
                    $errors[$field] = "{$student->name} ({$month['label']}): ayat {$surah->name_latin} harus 1–{$surah->total_ayah}.";
                } else {
                    $entries[] = [$student, $monthKey, $surah, $ayah];
                }
            }
        }
        if ($errors) {
            return back()->withInput()->withErrors($errors);
        }

        $saved = 0;
        DB::transaction(function () use ($entries, $months, $request, $targets, $termStart, $termEnd, &$saved) {
            foreach ($entries as [$student, $monthKey, $surah, $ayah]) {
                $month = $months[$monthKey];
                $existing = HafalanTarget::query()
                    ->where('student_id', $student->id)
                    ->whereBetween('target_date', [$month['start']->toDateString(), $month['end']->copy()->endOfDay()->toDateTimeString()])
                    ->orderBy('target_date')
                    ->orderBy('id')
                    ->get();
                $current = $existing->last();

                if (! $surah) {
                    if ($current) {
                        $current->delete();
                        $saved++;
                    }

                    continue;
                }

                // Deadline manual yang diatur guru dipertahankan.
                $deadline = $current?->deadline_manual ? $current->target_date->toDateString() : $month['deadline']->toDateString();
                if ($current
                    && (int) $current->surah_id === $surah->id
                    && (int) $current->ayah === $ayah
                    && $current->target_date?->toDateString() === $deadline
                    && $current->auto_month === null) {
                    continue;
                }

                $attributes = ['surah_id' => $surah->id, 'ayah' => $ayah, 'target_date' => $deadline, 'auto_month' => null];
                if ($current) {
                    $current->update($attributes);
                } else {
                    $current = HafalanTarget::create($attributes + [
                        'student_id' => $student->id,
                        'teacher_id' => $this->resolveTeacherId($request, $student),
                        'status' => 'active',
                    ]);
                }
                $targets->refreshStatus($current, $termStart, $termEnd);
                $saved++;
            }
        });

        return redirect()
            ->route('hafalan-targets.term', array_filter(['period' => $period, 'class_room_id' => $selectedClass->id, 'teacher_id' => $request->input('teacher_id')]))
            ->with('success', $saved > 0 ? "Target triwulan {$selectedClass->name} disimpan ({$saved} perubahan)." : 'Tidak ada perubahan target.');
    }

    /**
     * Kunci target satu kelas untuk satu bulan (App\Models\TargetLock).
     */
    public function lockMonth(Request $request): RedirectResponse
    {
        abort_unless(TargetLock::canLock($request->user()), 403, 'Akun ini tidak boleh mengunci target.');
        $validated = $request->validate([
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'month' => ['required', 'date_format:Y-m'],
        ]);

        TargetLock::query()->firstOrCreate(
            ['class_room_id' => (int) $validated['class_room_id'], 'month' => $validated['month'].'-01'],
            ['locked_by' => $request->user()->id, 'locked_at' => now()]
        );

        $label = Carbon::parse($validated['month'].'-01')->locale('id')->translatedFormat('F Y');
        $class = ClassRoom::query()->whereKey($validated['class_room_id'])->value('name');

        return back()->with('success', "Target {$label} kelas {$class} dikunci.");
    }

    /**
     * Buka kunci target (khusus Super Admin).
     */
    public function unlockMonth(Request $request): RedirectResponse
    {
        abort_unless(TargetLock::canUnlock($request->user()), 403, 'Hanya Super Admin yang dapat membuka kunci target.');
        $validated = $request->validate([
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'month' => ['required', 'date_format:Y-m'],
        ]);

        TargetLock::query()->where('class_room_id', (int) $validated['class_room_id'])->whereDate('month', $validated['month'].'-01')->delete();

        $label = Carbon::parse($validated['month'].'-01')->locale('id')->translatedFormat('F Y');
        $class = ClassRoom::query()->whereKey($validated['class_room_id'])->value('name');

        return back()->with('success', "Kunci target {$label} kelas {$class} dibuka.");
    }

    /**
     * Pilihan triwulan: triwulan berikutnya, yang berjalan, dan 5 sebelumnya -- tetapi tidak sebelum
     * triwulan pertama AcademicYear::FIRST (2026/2027), tahun ajaran sebelumnya tidak dipakai.
     *
     * @return array{0: Collection<string, string>, 1: string}
     */
    private function termPeriods(?string $requested, AcademicCalendarService $calendar): array
    {
        $currentStart = $calendar->termStartDate(today());
        $firstStart = Carbon::create(AcademicYear::startYear(AcademicYear::FIRST), 7, 1)->startOfDay();
        $periods = collect(range(-1, 5))
            ->reject(fn ($i) => $currentStart->copy()->subMonthsNoOverflow($i * 3)->lt($firstStart))
            ->mapWithKeys(function ($i) use ($currentStart) {
                $start = $currentStart->copy()->subMonthsNoOverflow($i * 3);
                $termNumber = [7 => 1, 10 => 2, 1 => 3, 4 => 4][$start->month];
                $academicYear = $start->month >= 7 ? $start->year.'/'.($start->year + 1) : ($start->year - 1).'/'.$start->year;

                return [$start->toDateString() => "Triwulan {$termNumber} · {$academicYear} ({$start->locale('id')->translatedFormat('M')} – {$start->copy()->addMonths(2)->locale('id')->translatedFormat('M Y')})"];
            });

        $fallback = $periods->has($currentStart->toDateString()) ? $currentStart->toDateString() : $periods->keys()->last();

        return [$periods, $periods->has($requested) ? $requested : $fallback];
    }

    /**
     * Tanggal acuan kelas untuk triwulan yang dimulai $period (akhir triwulan, atau hari ini bila berjalan).
     */
    private function termClassDate(string $period): Carbon
    {
        return StudentClassHistory::referenceDate(Carbon::parse($period)->addMonthsNoOverflow(3)->subDay());
    }

    /**
     * Kelas (non-Kelas 10) yang ditempati murid terlihat pada tanggal acuan triwulan (riwayat kelas).
     */
    private function termClassRooms(Collection $visibleStudentIds, ?int $teacherId, Carbon $classDate): Collection
    {
        $studentsQuery = Student::query()
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active');

        if ($teacherId) {
            $studentsQuery->where('teacher_id', $teacherId);
        }

        return ClassRoom::query()
            ->with('program')
            ->whereIn('id', StudentClassHistory::query()->activeOn($classDate)->whereIn('student_id', $studentsQuery->select('students.id'))->select('class_room_id'))
            ->orderBy('name')
            ->get()
            ->reject(fn (ClassRoom $class) => $class->isGradeTen())
            ->values();
    }

    private function termStudents(ClassRoom $classRoom, Collection $visibleStudentIds, ?int $teacherId, Carbon $classDate): Collection
    {
        // Murid kelas ini pada triwulan itu (riwayat kelas, docs/riwayat-kelas.md), bukan kelas saat ini.
        return Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->inClassOn($classRoom->id, $classDate)
            ->where('status', 'active')
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->orderBy('name')
            ->get()
            ->each(fn (Student $student) => $student->setRelation('classRoom', $classRoom));
    }

    /**
     * Ubah arah hafalan murid (lanjut ke belakang / pindah ke depan setelah Juz 29, 28, atau 27);
     * dipakai untuk menilai ketuntasan target.
     */
    public function updateDirection(Request $request, Student $student): RedirectResponse
    {
        abort_unless($this->visibleStudentIds($request->user())->contains($student->id), 403);

        $validated = $request->validate([
            'hafalan_direction' => ['required', Rule::in(array_keys(HafalanOrder::directionOptions()))],
            'period' => ['nullable', 'date'],
        ]);

        $student->update(['hafalan_direction' => $validated['hafalan_direction']]);

        return back()->with('success', "Arah hafalan {$student->name} diperbarui.");
    }

    /**
     * Koreksi urutan di dalam satu juz (dari awal / dari akhir) untuk satu murid, atau
     * kembalikan ke deteksi otomatis.
     */
    public function updateJuzOrder(Request $request, Student $student): RedirectResponse
    {
        abort_unless($this->visibleStudentIds($request->user())->contains($student->id), 403);

        $validated = $request->validate([
            'juz' => ['required', 'integer', 'between:1,30'],
            'order' => ['required', Rule::in(['auto', HafalanOrder::ASC, HafalanOrder::DESC])],
            'period' => ['nullable', 'date'],
        ]);

        $orders = collect($student->juz_orders ?? [])->except((string) $validated['juz']);
        if ($validated['order'] !== 'auto') {
            $orders->put((string) $validated['juz'], $validated['order']);
        }
        $student->update(['juz_orders' => $orders->isEmpty() ? null : $orders->all()]);

        return back()->with('success', "Urutan Juz {$validated['juz']} untuk {$student->name} diperbarui.");
    }

    /**
     * Urutan hafalan satu murid: 30 juz dalam urutan arah murid, cakupan ayat tiap juz,
     * urutan di dalam juz (terdeteksi / diatur guru) yang bisa dikoreksi.
     */
    public function juzOrders(Request $request, Student $student, HafalanProgressService $progress): View
    {
        abort_unless($this->visibleStudentIds($request->user())->contains($student->id), 403);

        $records = $progress->records($student);
        $coverage = $progress->coverage($records);
        $detected = $progress->detectedJuzOrders($records);
        $effective = $progress->juzOrders($student, $records);
        $manual = array_map('intval', array_keys($student->juz_orders ?? []));

        $student->loadMissing('classRoom');
        $gradeRule = HafalanProgressService::juz30FromNaba($student);
        $juzRows = collect(HafalanOrder::juzSequence($student->hafalan_direction))->map(function (int $juz) use ($coverage, $detected, $effective, $manual, $records, $gradeRule) {
            $totalAyat = 0;
            $coveredAyat = 0;
            foreach (HafalanOrder::JUZ_RANGES[$juz] as $range) {
                $totalAyat += $range['end'] - $range['start'] + 1;
                foreach (AyahCoverage::covered($coverage[$range['surah']] ?? [], $range['start'], $range['end']) as [$a, $b]) {
                    $coveredAyat += $b - $a + 1;
                }
            }
            $surahsInJuz = collect(HafalanOrder::JUZ_RANGES[$juz])->pluck('surah')->all();

            return [
                'juz' => $juz,
                'surah_range' => [reset($surahsInJuz), end($surahsInJuz)],
                'covered_percent' => $totalAyat > 0 ? (int) round($coveredAyat / $totalAyat * 100) : 0,
                'setoran_count' => $records->where('status', 'passed')->reject(fn ($r) => $r->is_prior)->filter(fn ($r) => in_array((int) $r->surah_number, $surahsInJuz, true))->count(),
                'prior_count' => $records->filter(fn ($r) => $r->is_prior && in_array((int) $r->surah_number, $surahsInJuz, true))->count(),
                'order' => $effective[$juz] ?? HafalanOrder::defaultJuzOrder($juz),
                'source' => match (true) {
                    in_array($juz, $manual, true) => 'manual',
                    $gradeRule => 'grade',
                    isset($detected[$juz]) => 'auto',
                    default => 'default',
                },
            ];
        });

        return view('hafalan-targets.juz-orders', [
            'student' => $student->load('classRoom'),
            'juzRows' => $juzRows,
            'surahNames' => $progress->surahs()->map->name_latin,
            'surahs' => $progress->surahs()->sortBy('number')->values(),
            'priorGroups' => $this->priorHafalanGroups($student),
            'canEditPrior' => self::canEditPriorHafalan($request->user()),
        ]);
    }

    /**
     * Hafalan sebelum aplikasi per juz untuk ditampilkan: juz yang tercakup penuh jadi satu baris
     * ("Juz 30 (penuh)"), selain itu daftar rentangnya.
     *
     * @return array<int, array{juz: int, full: bool, entries: Collection}>
     */
    private function priorHafalanGroups(Student $student): array
    {
        $groups = [];
        $entries = $student->priorHafalans()->with('surah')->get()
            ->sortBy(fn ($p) => $p->surah->number * 1000 + $p->ayah_start)
            ->groupBy(fn ($p) => HafalanOrder::juzOf((int) $p->surah->number, (int) $p->ayah_start));
        foreach ($entries as $juz => $items) {
            $covered = AyahCoverage::fromRanges($items->map(fn ($p) => [(int) $p->surah->number, (int) $p->ayah_start, (int) $p->ayah_end]));
            $full = collect(HafalanOrder::JUZ_RANGES[$juz])->every(fn ($r) => AyahCoverage::uncovered($covered[$r['surah']] ?? [], $r['start'], $r['end']) === []);
            $groups[] = ['juz' => (int) $juz, 'full' => $full, 'entries' => $items->values()];
        }

        return collect($groups)->sortBy('juz', descending: true)->values()->all();
    }

    /** Pengisi hafalan sebelum aplikasi: guru halaqoh (murid bimbingannya), Koordinator Tahfizh, Admin. */
    private static function canEditPriorHafalan($user): bool
    {
        return (bool) $user?->hasAnyRole(['super_admin', 'admin', 'coordinator_tahfizh', 'teacher']);
    }

    /**
     * Catat hafalan sebelum aplikasi: satu juz penuh ('juz') atau satu rentang surah:ayat.
     */
    public function storePriorHafalan(Request $request, Student $student): RedirectResponse
    {
        abort_unless($this->visibleStudentIds($request->user())->contains($student->id) && self::canEditPriorHafalan($request->user()), 403);

        if ($request->filled('juz')) {
            $juz = (int) $request->validate(['juz' => ['required', 'integer', 'between:1,30']])['juz'];
            $surahIds = Surah::query()->pluck('id', 'number');
            foreach (HafalanOrder::JUZ_RANGES[$juz] as $range) {
                StudentPriorHafalan::firstOrCreate(
                    ['student_id' => $student->id, 'surah_id' => $surahIds[$range['surah']], 'ayah_start' => $range['start'], 'ayah_end' => $range['end']],
                    ['created_by' => $request->user()->id]
                );
            }

            return back()->with('success', "Juz {$juz} dicatat sebagai hafalan sebelum aplikasi untuk {$student->name}.");
        }

        $validated = $request->validate([
            'surah_id' => ['required', 'integer', 'exists:surahs,id'],
            'ayah_start' => ['required', 'integer', 'min:1'],
            'ayah_end' => ['required', 'integer', 'gte:ayah_start'],
        ], ['ayah_end.gte' => 'Ayat akhir harus sama atau setelah ayat awal.']);
        $surah = Surah::findOrFail($validated['surah_id']);
        if ($validated['ayah_end'] > $surah->total_ayah) {
            return back()->withErrors(['ayah_end' => "Surah {$surah->name_latin} hanya {$surah->total_ayah} ayat."])->withInput();
        }

        StudentPriorHafalan::firstOrCreate(
            ['student_id' => $student->id, 'surah_id' => $surah->id, 'ayah_start' => $validated['ayah_start'], 'ayah_end' => $validated['ayah_end']],
            ['created_by' => $request->user()->id]
        );

        return back()->with('success', "{$surah->name_latin} {$validated['ayah_start']}-{$validated['ayah_end']} dicatat sebagai hafalan sebelum aplikasi.");
    }

    /**
     * Hapus hafalan sebelum aplikasi: satu rentang ('id') atau semua rentang di satu juz ('juz').
     */
    public function destroyPriorHafalan(Request $request, Student $student): RedirectResponse
    {
        abort_unless($this->visibleStudentIds($request->user())->contains($student->id) && self::canEditPriorHafalan($request->user()), 403);

        $entries = $student->priorHafalans()->with('surah')->get();
        if ($request->filled('juz')) {
            $juz = $request->integer('juz');
            $entries = $entries->filter(fn ($p) => HafalanOrder::juzOf((int) $p->surah->number, (int) $p->ayah_start) === $juz);
        } else {
            $entries = $entries->where('id', $request->integer('id'));
        }
        $entries->each->delete();

        return back()->with('success', 'Hafalan sebelum aplikasi dihapus.');
    }

    /**
     * Target Ummi (Kelas 10) per bulan: tabel per murid dengan isi serentak. Satu target per murid
     * per bulan (Jilid + Halaman Buku, Surah + Ayat opsional), deadline = hari aktif terakhir
     * bulan itu (TargetDeadlineService). Status Buku & Hafalan dinilai terpisah (HafalanTargetAutoCompletionService).
     */
    /**
     * Target Ummi (Kelas 10) per triwulan: satu tabel, 3 kolom bulan (Jilid & Halaman Buku, Surah &
     * Ayat opsional) -- sama dengan Target Triwulan Kelas 11/12. Bulan terkunci (TargetLock) tidak
     * bisa diubah.
     */
    public function ummi(Request $request, AcademicCalendarService $calendar, UmmiProgressService $ummi): View
    {
        [$periods, $period] = $this->termPeriods($this->ummiRequestedPeriod($request, $calendar), $calendar);
        [, , $teachers, $currentTeacherId, $classRooms, $students] = $this->ummiContext($request);

        $targets = $this->ummiTermTargets($students->pluck('id'), $period, $calendar);
        $months = $this->ummiMonths($period, $calendar, $targets->flatten());
        $locks = $classRooms->mapWithKeys(fn (ClassRoom $class) => [$class->id => TargetLock::forMonths($class->id, array_keys($months))]);

        return view('hafalan-targets.ummi', [
            'periods' => $periods,
            'period' => $period,
            'months' => $months,
            'teachers' => $teachers,
            'currentTeacherId' => $currentTeacherId,
            'classRooms' => $classRooms,
            'selectedClassId' => $classRooms->contains('id', (int) $request->input('class_room_id')) ? (int) $request->input('class_room_id') : null,
            'students' => $students,
            'targets' => $targets,
            'positions' => $ummi->positionsFor($students->pluck('id')->all(), now()),
            'locks' => $locks,
            'surahs' => Surah::query()->orderBy('number')->get(['id', 'number', 'name_latin', 'total_ayah']),
            'canEdit' => $request->user()->can('create', HafalanTarget::class),
            'canLock' => TargetLock::canLock($request->user()),
            'canUnlock' => TargetLock::canUnlock($request->user()),
        ]);
    }

    public function storeUmmi(Request $request, AcademicCalendarService $calendar, HafalanTargetAutoCompletionService $status): RedirectResponse
    {
        $this->authorize('create', HafalanTarget::class);

        [, $period] = $this->termPeriods($this->ummiRequestedPeriod($request, $calendar), $calendar);
        [, , , , , $students] = $this->ummiContext($request);
        $students = $students->keyBy('id');
        $existing = $this->ummiTermTargets($students->keys(), $period, $calendar);
        $months = $this->ummiMonths($period, $calendar, $existing->flatten());
        $surahs = Surah::query()->get(['id', 'name_latin', 'total_ayah'])->keyBy('id');
        $input = (array) $request->input('targets', []);

        // Deadline per bulan: sama dengan otomatis = ikut otomatis; tanggal lain (di bulan itu) = manual.
        $errors = [];
        $deadlines = [];
        foreach ($months as $monthKey => $month) {
            $value = $request->input("deadlines.{$monthKey}");
            $date = $value ? Carbon::parse($value) : $month['deadline'];
            if ($date->lt($month['start']) || $date->gt($month['end'])) {
                $errors["deadlines.{$monthKey}"] = "Deadline {$month['label']} harus di bulan itu.";
            }
            $deadlines[$monthKey] = [$date->toDateString(), $date->toDateString() !== $month['auto_deadline']->toDateString()];
        }

        // Validasi semua sel dulu; simpan hanya bila semuanya benar. Sel yang tidak dikirim (bulan
        // terkunci / dinonaktifkan) = tidak diubah.
        $rows = [];
        foreach ($students as $student) {
            foreach ($months as $monthKey => $month) {
                if (! array_key_exists($monthKey, (array) ($input[$student->id] ?? []))) {
                    continue;
                }
                if (TargetLock::blocksStudent($student, $month['start'])) {
                    continue;
                }
                $cell = (array) $input[$student->id][$monthKey];
                $jilid = trim((string) ($cell['jilid'] ?? ''));
                $page = trim((string) ($cell['halaman'] ?? ''));
                $surah = $surahs->get((int) ($cell['surah_id'] ?? 0));
                $ayah = (int) ($cell['ayah'] ?? 0);
                $field = "targets.{$student->id}.{$monthKey}";

                if ($jilid === '' && $page === '' && ! $surah) {
                    $rows[] = [$student, $monthKey, null];

                    continue;
                }
                if (! in_array($jilid, self::UMMI_JILID, true) || ! ctype_digit($page) || (int) $page < 1 || (int) $page > UmmiProgressService::PAGES_PER_JILID) {
                    $errors[$field] = "{$student->name} ({$month['label']}): isi Jilid dan Halaman Buku (1–".UmmiProgressService::PAGES_PER_JILID.').';
                } elseif ($surah && $ayah > (int) $surah->total_ayah) {
                    $errors[$field] = "{$student->name} ({$month['label']}): ayat {$surah->name_latin} maksimal {$surah->total_ayah}.";
                } else {
                    $rows[] = [$student, $monthKey, ['ummi_jilid' => $jilid, 'halaman_buku' => $page, 'surah_id' => $surah?->id, 'ayah' => $surah && $ayah > 0 ? $ayah : null]];
                }
            }
        }
        if ($errors) {
            return back()->withInput()->withErrors($errors);
        }

        $saved = 0;
        DB::transaction(function () use ($rows, $existing, $deadlines, $request, $status, &$saved) {
            foreach ($rows as [$student, $monthKey, $values]) {
                $current = $existing->get($student->id)?->get($monthKey);

                if ($values === null) {
                    if ($current) {
                        $current->delete();
                        $saved++;
                    }

                    continue;
                }

                [$deadline, $manual] = $deadlines[$monthKey];
                $values += ['target_date' => $deadline, 'deadline_manual' => $manual, 'halaman_peraga' => null, 'auto_month' => null];

                if ($current && collect($values)->every(fn ($value, $key) => (string) ($key === 'target_date' ? $current->target_date?->toDateString() : $current->{$key}) === (string) $value)) {
                    continue;
                }

                // Target baru/berubah: status dinilai ulang dari awal.
                $values += [
                    'status' => 'active', 'completed_at' => null,
                    'book_status' => 'active', 'surah_status' => $values['surah_id'] ? 'active' : null,
                ];
                $target = $current
                    ? tap($current)->update($values)
                    : HafalanTarget::create($values + ['student_id' => $student->id, 'teacher_id' => $this->resolveTeacherId($request, $student)]);
                $status->refresh($target->fresh());
                $saved++;
            }
        });

        return redirect()
            ->route('hafalan-targets.ummi', array_filter(['period' => $period, 'teacher_id' => $request->input('teacher_id'), 'class_room_id' => $request->input('class_room_id')]))
            ->with('success', $saved > 0 ? "Target Ummi disimpan ({$saved} perubahan)." : 'Tidak ada perubahan target.');
    }

    /**
     * Triwulan yang diminta: `period`, atau triwulan yang memuat `month` (tautan lama per bulan).
     */
    private function ummiRequestedPeriod(Request $request, AcademicCalendarService $calendar): ?string
    {
        if ($request->filled('period')) {
            return (string) $request->input('period');
        }

        return $request->filled('month') && preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month'))
            ? $calendar->termStartDate(Carbon::parse($request->input('month').'-01'))->toDateString()
            : null;
    }

    /**
     * Target Ummi murid-murid di triwulan ini: [student_id => ["Y-m" => target terakhir bulan itu]].
     *
     * @return Collection<int, Collection<string, HafalanTarget>>
     */
    private function ummiTermTargets(Collection $studentIds, string $period, AcademicCalendarService $calendar): Collection
    {
        $termMonths = $calendar->termMonths(Carbon::parse($period));

        return HafalanTarget::query()
            ->with('surah')
            ->whereIn('student_id', $studentIds)
            ->whereNotNull('ummi_jilid')
            ->whereBetween('target_date', [reset($termMonths)['start']->toDateString(), end($termMonths)['end']->toDateString().' 23:59:59'])
            ->orderBy('target_date')
            ->orderBy('id')
            ->get()
            ->groupBy('student_id')
            ->map(fn ($targets) => $targets->groupBy(fn (HafalanTarget $t) => $t->target_date->format('Y-m'))->map->last());
    }

    /**
     * Bulan-bulan triwulan: label, rentang, deadline otomatis, dan deadline tampil (manual terbanyak
     * dari target tersimpan bulan itu, atau otomatis).
     *
     * @return array<string, array{label: string, start: Carbon, end: Carbon, auto_deadline: Carbon, deadline: Carbon, manual: bool}>
     */
    private function ummiMonths(string $period, AcademicCalendarService $calendar, Collection $storedTargets): array
    {
        $months = [];
        foreach ($calendar->termMonths(Carbon::parse($period)) as $monthKey => $range) {
            $auto = app(TargetDeadlineService::class)->forMonth($range['start']);
            $manual = $storedTargets
                ->filter(fn (HafalanTarget $t) => $t->deadline_manual && $t->target_date?->format('Y-m') === $monthKey)
                ->map(fn (HafalanTarget $t) => $t->target_date->toDateString())
                ->countBy()->sortDesc()->keys()->first();
            $months[$monthKey] = [
                'label' => $range['start']->locale('id')->translatedFormat('F Y'),
                'start' => $range['start'],
                'end' => $range['end'],
                'auto_deadline' => $auto,
                'deadline' => $manual ? Carbon::parse($manual) : $auto,
                'manual' => $manual !== null,
            ];
        }

        return $months;
    }

    /**
     * Status bagian Buku & Hafalan target Ummi saat status keseluruhan diubah manual:
     * Selesai/Terlewat berlaku ke kedua bagian, Aktif mengulang penilaian otomatis.
     *
     * @return array{book_status: ?string, surah_status: ?string}
     */
    private function ummiPartStatuses(string $status, bool $hasSurah): array
    {
        $part = in_array($status, ['completed', 'missed'], true) ? $status : ($status === 'active' ? 'active' : null);

        return ['book_status' => $part, 'surah_status' => $hasSurah ? $part : null];
    }

    /** Jilid Ummi Dewasa. */
    private const UMMI_JILID = ['Jilid 1', 'Jilid 2', 'Jilid 3'];

    /**
     * Konteks halaman Target Ummi: bulan, pilihan bulan, halaqah (guru), kelas 10, dan murid.
     *
     * @return array{0: string, 1: array<string, string>, 2: Collection, 3: ?int, 4: Collection, 5: Collection}
     */
    private function ummiContext(Request $request): array
    {
        $user = $request->user();
        $visibleStudentIds = $this->visibleStudentIds($user);

        $monthOptions = collect(range(3, -6))
            ->mapWithKeys(function ($offset) {
                $date = now()->startOfMonth()->addMonthsNoOverflow($offset);

                return [$date->format('Y-m') => $date->locale('id')->translatedFormat('F Y')];
            })
            ->all();
        $month = array_key_exists((string) $request->input('month'), $monthOptions) ? $request->input('month') : now()->format('Y-m');

        $gradeTenStudents = Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->filter(fn (Student $student) => $student->classRoom?->isGradeTen())
            ->values();

        $isTeacherOnly = $user->hasRole('teacher') && ! $user->hasAnyRole(['super_admin', 'admin']);
        $teachers = TeacherProfile::query()
            ->with('user')
            ->whereIn('id', $gradeTenStudents->pluck('teacher_id')->filter()->unique())
            ->get()
            ->sortBy(fn ($teacher) => $teacher->user?->name)
            ->values();
        $currentTeacherId = $isTeacherOnly
            ? $user->teacherProfile?->id
            : ($teachers->firstWhere('id', (int) $request->input('teacher_id'))?->id ?? $teachers->first()?->id);

        $students = $gradeTenStudents->where('teacher_id', $currentTeacherId)->values();
        $classRooms = $students->pluck('classRoom')->unique('id')->sortBy('name')->values();
        if ($request->filled('class_room_id') && $classRooms->contains('id', (int) $request->input('class_room_id'))) {
            $students = $students->where('class_room_id', (int) $request->input('class_room_id'))->values();
        }

        return [$month, $monthOptions, $isTeacherOnly ? $teachers->where('id', $currentTeacherId)->values() : $teachers, $currentTeacherId, $classRooms, $students];
    }

    private function visibleStudentIds(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        return app(StudentProgressService::class)
            ->visibleStudentQuery($user)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
    }

    private function resolveTeacherId(Request $request, Student $student): ?int
    {
        $user = $request->user();
        $teacherId = null;

        if ($user?->teacherProfile?->id) {
            $teacherId = (int) $user->teacherProfile->id;
        } elseif ($user?->hasRole('teacher')) {
            $tId = TeacherProfile::query()->where('user_id', $user->id)->value('id');
            if ($tId) {
                $teacherId = (int) $tId;
            }
        }

        if (! $teacherId && $student->teacher_id) {
            $teacherId = (int) $student->teacher_id;
        }

        if (! $teacherId) {
            $teacherId = (int) TeacherProfile::query()->value('id');
        }

        if ($teacherId && ! $student->teacher_id) {
            $student->update(['teacher_id' => $teacherId]);
        }

        return $teacherId;
    }

    private function targetStatuses(): array
    {
        try {
            $column = DB::selectOne("SHOW COLUMNS FROM hafalan_targets LIKE 'status'");

            if ($column && isset($column->Type)) {
                preg_match_all("/'([^']+)'/", (string) $column->Type, $matches);

                if (! empty($matches[1])) {
                    return $matches[1];
                }
            }
        } catch (Throwable) {
            // Fallback di bawah sengaja dibiarkan.
        }

        return [
            'active',
            'planned',
            'in_progress',
            'completed',
            'missed',
            'cancelled',
        ];
    }

    private function activeTargetStatuses(): array
    {
        $statuses = $this->targetStatuses();

        $activeStatuses = array_values(array_intersect($statuses, [
            'active',
            'planned',
            'in_progress',
        ]));

        return ! empty($activeStatuses)
            ? $activeStatuses
            : [$this->defaultOpenTargetStatus()];
    }

    private function defaultOpenTargetStatus(): string
    {
        $statuses = $this->targetStatuses();

        if (in_array('active', $statuses, true)) {
            return 'active';
        }

        if (in_array('planned', $statuses, true)) {
            return 'planned';
        }

        if (in_array('in_progress', $statuses, true)) {
            return 'in_progress';
        }

        return $statuses[0] ?? 'active';
    }
}
