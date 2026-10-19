<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Setting;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\UmmiProsemOverride;
use App\Services\StudentProgressService;
use App\Services\UmmiProgressService;
use App\Services\UmmiProsemService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Alur Pembelajaran Ummi (Kelas 10): timer per sesi supaya musyrif tidak lewat waktu, dan target pertemuan
 * hari ini dari Prosem (UmmiProsemService::meetingFor). Durasi sesi bisa diatur koordinator/admin.
 */
class UmmiFlowController extends Controller
{
    public const SETTING_KEY = 'ummi_flow_minutes';

    /** Sesi bawaan: Pembukaan 5, Hafalan 20 (Muraja'ah 10 + Ziyadah 10), Klasikal 40, Penutup 5. */
    public const SESSIONS = [
        ['key' => 'pembukaan', 'label' => 'Pembukaan', 'group' => null, 'minutes' => 5],
        ['key' => 'murojaah', 'label' => "Muraja'ah", 'group' => 'Hafalan', 'minutes' => 10],
        ['key' => 'ziyadah', 'label' => 'Ziyadah', 'group' => 'Hafalan', 'minutes' => 10],
        ['key' => 'klasikal', 'label' => 'Klasikal peraga & baca simak', 'group' => null, 'minutes' => 40],
        ['key' => 'penutup', 'label' => 'Penutup', 'group' => null, 'minutes' => 5],
    ];

    public function __construct(
        private readonly StudentProgressService $access,
        private readonly UmmiProsemService $prosem,
    ) {}

    /** Sesi dengan durasi dari pengaturan. */
    public static function sessions(): array
    {
        $saved = json_decode((string) Setting::get(self::SETTING_KEY, '{}'), true) ?: [];

        return array_map(fn ($s) => ['minutes' => max(1, (int) ($saved[$s['key']] ?? $s['minutes']))] + $s, self::SESSIONS);
    }

    public static function canConfigure($user): bool
    {
        return (bool) $user?->hasAnyRole(['super_admin', 'admin', 'coordinator_tahfizh']);
    }

    public function index(Request $request): View
    {
        $classIds = $this->access->visibleStudentQuery($request->user())->where('status', 'active')->pluck('class_room_id')->unique();
        $classes = ClassRoom::query()->with('program')->whereIn('id', $classIds)->orderBy('name')->get()
            ->filter(fn (ClassRoom $c) => $c->isGradeTen())->values();
        $class = $classes->firstWhere('id', $request->integer('class_room_id')) ?? $classes->first();
        $today = Carbon::today();

        // Halaqoh di kelas itu (yang terlihat oleh user); bawaan = halaqoh guru yang login.
        $teachers = $class
            ? TeacherProfile::query()->with('user')
                ->whereIn('id', $this->access->visibleStudentQuery($request->user())->where('class_room_id', $class->id)->where('status', 'active')->select('teacher_id'))
                ->get()->sortBy(fn ($t) => $t->user?->name)->values()
            : collect();
        $teacher = $teachers->firstWhere('id', $request->integer('teacher_id'))
            ?? $teachers->firstWhere('user_id', $request->user()->id)
            ?? $teachers->first();

        // Target per murid dari jurnal Ummi (UmmiProsemService::studentProgress); sesi hari ini memakai
        // target mayoritas murid halaqoh, murid lain ditandai tertinggal / surplus.
        [$year, $semester] = UmmiProsemService::semesterOf($today);
        $students = $class
            ? $this->access->visibleStudentQuery($request->user())->where('class_room_id', $class->id)->where('status', 'active')
                ->when($teacher, fn ($q) => $q->where('teacher_id', $teacher->id))->orderBy('name')->get()
                ->each(fn (Student $s) => $s->setRelation('classRoom', $class))
                ->filter(fn (Student $s) => $s->usesUmmi())->values()
            : collect();
        $progress = $students->map(fn (Student $s) => ['student' => $s] + $this->prosem->studentProgress($s, $year, $semester));
        $plan = $progress->first()['plan'] ?? [];
        $majorityNo = $progress->pluck('next.no')->filter()->countBy()->sortKeys()->sortDesc()->keys()->first();
        $majority = collect($plan)->firstWhere('no', $majorityNo);
        $weekly = $class?->program?->meeting_frequency === 'seminggu sekali';

        return view('ummi-flow.index', [
            'classes' => $classes,
            'class' => $class,
            'teachers' => $teachers,
            'teacher' => $teacher,
            'today' => $today,
            'hasPlan' => $plan !== [],
            'target' => $majority ? UmmiProsemService::sessionTarget($plan, $majority, $weekly) : null,
            'majorityNo' => $majorityNo,
            'weekly' => $weekly,
            'students' => $progress->map(fn ($p) => [
                'id' => $p['student']->id,
                'name' => $p['student']->name,
                'position' => UmmiProgressService::pageLabel($p['position']),
                'next' => $p['next'],
                'gap' => ($p['next']['no'] ?? null) && $majorityNo ? $p['next']['no'] - $majorityNo : 0,
                'override' => $p['override'],
                'last_session' => $p['last_session'],
            ])->all(),
            'schedule' => $class ? $this->prosem->meetingFor($class, $today, $teacher?->id) : null,
            'sessions' => self::sessions(),
            'storageKey' => 'ummiFlow:'.$request->user()->id.':'.($class?->id ?? 0).':'.($teacher?->id ?? 0).':'.$today->toDateString(),
            'canConfigure' => self::canConfigure($request->user()),
        ]);
    }

    /**
     * "Samakan": target murid yang tertinggal dimajukan ke TM mayoritas halaqoh (semua murid tertinggal
     * bila student_id kosong). Target tetap maju bila jurnal murid sudah melewatinya.
     */
    public function align(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tm_no' => ['required', 'integer', 'min:1'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer'],
        ]);
        [$year, $semester] = UmmiProsemService::semesterOf(Carbon::today());
        $students = $this->access->visibleStudentQuery($request->user())->whereIn('id', $validated['student_ids'])->get();
        abort_if($students->isEmpty(), 403);

        foreach ($students as $student) {
            UmmiProsemOverride::updateOrCreate(
                ['student_id' => $student->id, 'academic_year' => $year, 'semester' => $semester],
                ['tm_no' => $validated['tm_no'], 'created_by' => $request->user()->id]
            );
        }

        return back()->with('success', $students->count().' murid disamakan ke TM '.$validated['tm_no'].'.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        abort_unless(self::canConfigure($request->user()), 403);
        $rules = collect(self::SESSIONS)->mapWithKeys(fn ($s) => ["minutes.{$s['key']}" => ['required', 'integer', 'between:1,120']])->all();
        $validated = $request->validate($rules);
        Setting::set(self::SETTING_KEY, json_encode(array_map('intval', $validated['minutes'])));

        return back()->with('success', 'Durasi sesi disimpan (total '.array_sum($validated['minutes']).' menit).');
    }
}
