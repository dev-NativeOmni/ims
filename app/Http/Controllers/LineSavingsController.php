<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\TeacherProfile;
use App\Services\AcademicCalendarService;
use App\Services\HafalanProgressService;
use App\Services\StudentProgressService;
use App\Support\AcademicYear;
use App\Support\TargetRules;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Tabungan & Hutang Baris (Kelas 11 & 12): per murid, baris capaian vs target tiap bulan (selisih
 * lebih/kurang) dan per triwulan (saldo tabungan awal, dipakai, ditabung, hutang, saldo akhir).
 * Angka dari HafalanProgressService::termBreakdown + LineSavingsService, sama dengan rapor & Laporan
 * Triwulan (docs/tabungan-baris.md). Filter kelas & halaqoh (musyrif); guru hanya melihat muridnya.
 */
class LineSavingsController extends Controller
{
    public function __construct(
        private readonly HafalanProgressService $progress,
        private readonly AcademicCalendarService $calendar,
        private readonly StudentProgressService $access,
    ) {}

    public function index(Request $request): View
    {
        $year = AcademicYear::isValid($request->input('academic_year')) ? $request->input('academic_year') : AcademicYear::forDate(now());
        $term = in_array($request->integer('term'), [1, 2, 3, 4], true) ? $request->integer('term')
            : ($year === AcademicYear::forDate(now()) ? AcademicYear::termOf(now()) : 1);
        [$start, $end] = AcademicYear::termRange($year, $term);
        $classDate = StudentClassHistory::referenceDate($end);
        $visible = fn () => $this->access->visibleStudentQuery($request->user());

        // Kelas 11 & 12 yang punya murid terlihat oleh user ini pada triwulan itu.
        $classes = ClassRoom::query()->with('program')->orderBy('name')->get()
            ->filter(fn (ClassRoom $c) => $c->isGradeElevenOrTwelve() && $visible()->inClassOn($c->id, $classDate)->exists())
            ->values();
        $selectedClass = $classes->firstWhere('id', $request->integer('class_room_id'));
        $teacherId = $request->integer('teacher_id') ?: null;
        if (! $selectedClass && ! $teacherId) {
            $selectedClass = $classes->first();
        }

        $teachers = TeacherProfile::query()->with('user')
            ->whereIn('id', Student::query()->where('status', 'active')
                ->when($selectedClass, fn ($q) => $q->inClassOn($selectedClass->id, $classDate))
                ->whereIn('id', $visible()->select('id'))->select('teacher_id'))
            ->get()->sortBy(fn ($t) => $t->user?->name)->values();

        $months = $this->calendar->termMonths($start);
        $rows = $this->rows($request, $selectedClass ? collect([$selectedClass]) : $classes, $teacherId, $classDate, $start, $end, $months);

        return view('line-savings.index', [
            'years' => AcademicYear::options($year),
            'year' => $year,
            'term' => $term,
            'termLabel' => "Triwulan {$term} ({$start->locale('id')->translatedFormat('M')} - {$end->locale('id')->translatedFormat('M Y')})",
            'classes' => $classes,
            'selectedClass' => $selectedClass,
            'teachers' => $teachers,
            'teacherId' => $teacherId,
            'months' => collect($months)->map(fn ($m) => [
                'label' => $m['start']->locale('id')->translatedFormat('F'),
                'future' => $m['start']->gt(now()),
            ])->all(),
            'rows' => $rows,
            'summary' => [
                'students' => count($rows),
                'in_debt' => collect($rows)->where('debt', '>', 0)->count(),
                'debt' => round(collect($rows)->sum('debt'), 1),
                'savings' => round(collect($rows)->sum('balance_after'), 1),
                'helped' => collect($rows)->where('used', '>', 0)->count(),
            ],
        ]);
    }

    /**
     * @param  Collection<int, ClassRoom>  $classes
     */
    private function rows(Request $request, Collection $classes, ?int $teacherId, Carbon $classDate, Carbon $start, Carbon $end, array $months): array
    {
        $rows = [];
        foreach ($classes as $class) {
            $students = $this->access->visibleStudentQuery($request->user())
                ->inClassOn($class->id, $classDate)->where('status', 'active')
                ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
                ->with('teacher.user')->orderBy('name')->get()
                ->each(fn (Student $s) => $s->setRelation('classRoom', $class->loadMissing('program')))
                ->reject(fn (Student $s) => $s->usesUmmi() || TargetRules::linesForLevel($s->tahfizh_level) === null);

            foreach ($students as $student) {
                $targets = HafalanTarget::with('surah')->where('student_id', $student->id)
                    ->whereBetween('target_date', [$start->toDateString(), $end->copy()->endOfDay()])
                    ->get()->filter(fn ($t) => $t->surah);
                $breakdown = $this->progress->termBreakdown($student, $targets, $months, now()->min($end->copy()->endOfDay()));
                $evaluation = $breakdown['evaluation'];
                $savings = $evaluation['savings'] ?? ['before' => 0.0, 'used' => 0.0, 'deposit' => 0.0, 'after' => 0.0];

                $rows[] = [
                    'name' => $student->name,
                    'class' => $class->name,
                    'program' => $class->isTahfizhProgram() ? 'Tahfizh' : 'Reguler',
                    'level' => $student->tahfizh_level_label,
                    'musyrif' => $student->teacher?->user?->name ?? '-',
                    'months' => collect($breakdown['months'])->map(fn ($m) => [
                        'target' => (float) $m['target_lines'],
                        'achieved' => (float) $m['achieved_lines'],
                        'diff' => round((float) $m['achieved_lines'] - (float) $m['target_lines'], 1),
                    ])->values()->all(),
                    'target' => (float) $evaluation['target_lines'],
                    'achieved' => round((float) $evaluation['achieved_lines'], 1),
                    'balance_before' => $savings['before'],
                    'used' => $savings['used'],
                    'deposit' => $savings['deposit'],
                    'debt' => $evaluation['reached'] ? 0.0 : round((float) $evaluation['target_lines'] - (float) $evaluation['achieved_lines'], 1),
                    'balance_after' => $savings['after'],
                    'reached' => (bool) $evaluation['reached'],
                ];
            }
        }

        return $rows;
    }
}
