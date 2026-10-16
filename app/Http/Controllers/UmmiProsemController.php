<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\UmmiProsemRow;
use App\Services\UmmiProsemService;
use App\Support\AcademicYear;
use App\Support\UmmiBook;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Prosem Ummi (Kelas 10): tabel Program Semester yang bisa diedit koordinator/admin, rincian target per
 * pertemuan & target bulanan otomatis (UmmiProsemService, docs/prosem-ummi.md).
 */
class UmmiProsemController extends Controller
{
    public const MONTH_LABELS = [7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni'];

    public function __construct(private readonly UmmiProsemService $prosem) {}

    public static function canEdit($user): bool
    {
        return (bool) $user?->hasAnyRole(['super_admin', 'admin', 'coordinator_tahfizh']);
    }

    public function index(Request $request): View
    {
        [$year, $semester] = $this->period($request);
        [$classes, $class, $teachers, $teacher] = $this->scope($request);
        $rows = $this->prosem->rows($year, $semester, $class?->id, $teacher?->id);
        $plan = $this->prosem->planFromRows($rows, $semester);
        $scopeLabel = fn (?int $classId, ?int $teacherId) => ($classId ? (ClassRoom::find($classId)?->name ?? '?') : 'Umum')
            .($teacherId ? ' · '.(TeacherProfile::with('user')->find($teacherId)?->user?->name ?? '?') : ($classId ? ' · semua halaqoh' : ''));

        return view('hafalan-targets.prosem', [
            'year' => $year,
            'semester' => $semester,
            'years' => AcademicYear::options($year),
            'classes' => $classes,
            'class' => $class,
            'teachers' => $teachers,
            'teacher' => $teacher,
            'existing' => $this->prosem->scopes($year, $semester)
                ->map(fn ($s) => ['class_room_id' => $s[0], 'teacher_id' => $s[1], 'label' => $scopeLabel($s[0], $s[1])])
                ->sortBy('label')->values(),
            'months' => collect(UmmiProsemService::SEMESTER_MONTHS[$semester])->mapWithKeys(fn ($m) => [$m => self::MONTH_LABELS[$m]])->all(),
            'rows' => $rows,
            'plan' => $plan,
            'monthTargets' => collect(UmmiProsemService::SEMESTER_MONTHS[$semester])
                ->mapWithKeys(fn ($m) => [$m => $this->monthTargetFromPlan($plan, $semester, $m)])->all(),
            'surahs' => Surah::query()->orderBy('number')->get(['id', 'number', 'name_latin']),
            'books' => UmmiBook::BOOKS,
            'canEdit' => self::canEdit($request->user()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(self::canEdit($request->user()), 403);
        [$year, $semester] = $this->period($request);
        [, $class, , $teacher] = $this->scope($request);
        $months = UmmiProsemService::SEMESTER_MONTHS[$semester];

        $validated = $request->validate([
            'rows' => ['array'],
            'rows.*.jilid' => ['nullable', Rule::in(UmmiBook::BOOKS)],
            'rows.*.peraga_start' => ['nullable', 'integer', 'between:1,200'],
            'rows.*.peraga_end' => ['nullable', 'integer', 'between:1,200'],
            'rows.*.buku_start' => ['nullable', 'integer', 'between:1,200'],
            'rows.*.buku_end' => ['nullable', 'integer', 'between:1,200'],
            'rows.*.hafalan_start_surah_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'rows.*.hafalan_end_surah_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'rows.*.drill_start_surah_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'rows.*.drill_end_surah_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'rows.*.weeks' => ['nullable', 'array'],
            'rows.*.weeks.*.*' => ['nullable', 'integer', 'between:0,10'],
            'rows.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $rows = [];
        foreach (array_values($validated['rows'] ?? []) as $i => $row) {
            $weeks = [];
            foreach ($months as $month) {
                foreach (range(1, 5) as $week) {
                    $count = (int) ($row['weeks'][$month][$week] ?? 0);
                    if ($count > 0) {
                        $weeks[(string) $month][(string) $week] = $count;
                    }
                }
            }
            $rows[] = [
                'academic_year' => $year, 'semester' => $semester, 'class_room_id' => $class?->id, 'teacher_id' => $teacher?->id, 'position' => $i + 1,
                'jilid' => $row['jilid'] ?? null,
                'peraga_start' => $row['peraga_start'] ?? null, 'peraga_end' => $row['peraga_end'] ?? ($row['peraga_start'] ?? null),
                'buku_start' => $row['buku_start'] ?? null, 'buku_end' => $row['buku_end'] ?? ($row['buku_start'] ?? null),
                'hafalan_start_surah_id' => $row['hafalan_start_surah_id'] ?? null,
                'hafalan_end_surah_id' => $row['hafalan_end_surah_id'] ?? ($row['hafalan_start_surah_id'] ?? null),
                'drill_start_surah_id' => $row['drill_start_surah_id'] ?? null,
                'drill_end_surah_id' => $row['drill_end_surah_id'] ?? ($row['drill_start_surah_id'] ?? null),
                'weeks' => $weeks,
                'notes' => $row['notes'] ?? null,
            ];
        }

        DB::transaction(function () use ($rows, $year, $semester, $class, $teacher) {
            UmmiProsemRow::query()->where('academic_year', $year)->where('semester', $semester)
                ->where('class_room_id', $class?->id)->where('teacher_id', $teacher?->id)->delete();
            foreach ($rows as $row) {
                UmmiProsemRow::create($row);
            }
        });
        $this->prosem->forgetPlans();
        $synced = $this->prosem->syncTargets($year, $semester);

        return redirect()->route('prosem-ummi.index', array_filter(['academic_year' => $year, 'semester' => $semester, 'class_room_id' => $class?->id, 'teacher_id' => $teacher?->id]))
            ->with('success', 'Prosem disimpan ('.count($rows)." baris). Target Ummi otomatis diperbarui: {$synced}.");
    }

    /**
     * Cakupan yang dipilih: kelas 10 (kosong = umum) & halaqoh di kelas itu (kosong = semua halaqoh).
     *
     * @return array{0: Collection, 1: ?ClassRoom, 2: Collection, 3: ?TeacherProfile}
     */
    private function scope(Request $request): array
    {
        $classes = ClassRoom::query()->with('program')->orderBy('name')->get()->filter(fn (ClassRoom $c) => $c->isGradeTen())->values();
        $class = $classes->firstWhere('id', $request->integer('class_room_id'));
        $teachers = $class
            ? TeacherProfile::query()->with('user')
                ->whereIn('id', Student::query()->where('class_room_id', $class->id)->where('status', 'active')->select('teacher_id'))
                ->get()->sortBy(fn ($t) => $t->user?->name)->values()
            : collect();
        $teacher = $teachers->firstWhere('id', $request->integer('teacher_id'));

        return [$classes, $class, $teachers, $teacher];
    }

    /** Target bulanan dari urutan TM yang sedang ditampilkan (sama dengan UmmiProsemService::monthTarget). */
    private function monthTargetFromPlan(array $plan, int $semester, int $month): ?array
    {
        $order = array_flip(UmmiProsemService::SEMESTER_MONTHS[$semester]);
        $upTo = collect($plan)->filter(fn ($e) => $order[$e['month']] <= $order[$month]);
        $book = $upTo->reverse()->first(fn ($e) => $e['jilid'] && $e['buku']);
        $hafalan = $upTo->reverse()->first(fn ($e) => $e['hafalan']);

        return $book ? [
            'ummi_jilid' => $book['jilid'],
            'halaman_buku' => (string) max($book['buku']),
            'surah_id' => $hafalan ? UmmiProsemService::furthest($hafalan['hafalan'])->id : null,
        ] : null;
    }

    /** @return array{0: string, 1: int} */
    private function period(Request $request): array
    {
        [$currentYear, $currentSemester] = UmmiProsemService::semesterOf(Carbon::today());
        $year = AcademicYear::isValid($request->input('academic_year')) ? $request->input('academic_year') : $currentYear;
        $semester = in_array($request->integer('semester'), [1, 2], true) ? $request->integer('semester') : $currentSemester;

        return [$year, $semester];
    }
}
