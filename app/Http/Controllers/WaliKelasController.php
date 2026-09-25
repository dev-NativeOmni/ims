<?php

namespace App\Http\Controllers;

use App\Models\AdabRecord;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Services\AcademicCalendarService;
use App\Services\AutoHafalanTargetService;
use App\Services\HafalanProgressService;
use App\Services\QuranLineTargetService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Dashboard pemantauan untuk Wali Kelas: murid yang belum tuntas hafalan (per bulan &
 * per triwulan), murid yang belum mengisi kuisioner adab hari ini, dan rekap
 * kedisiplinan (poin pelanggaran) bulan berjalan -- khusus kelas yang diampu, murni
 * pemantauan (tidak ada input/edit data dari halaman ini).
 */
class WaliKelasController extends Controller
{
    private const MONTH_NAMES = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
    ];

    public function index(Request $request, AcademicCalendarService $calendar, QuranLineTargetService $positionCheck): View
    {
        $user = $request->user();

        $classRoom = ClassRoom::query()
            ->with('program')
            ->where('wali_kelas_user_id', $user->id)
            ->first();

        if (! $classRoom) {
            return view('wali-kelas.index', [
                'classRoom' => null,
                'students' => collect(),
                'monthlyTuntas' => [],
                'termTuntas' => null,
                'adabToday' => null,
                'discipline' => null,
            ]);
        }

        $students = Student::query()
            ->where('class_room_id', $classRoom->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');
        $today = Carbon::today();

        [$monthlyTuntas, $termTuntas] = $this->buildHafalanTuntas($classRoom, $students, $studentIds, $today, $calendar, $positionCheck);

        return view('wali-kelas.index', [
            'classRoom' => $classRoom,
            'students' => $students,
            'monthlyTuntas' => $monthlyTuntas,
            'termTuntas' => $termTuntas,
            'adabToday' => $this->buildAdabToday($students, $studentIds, $today),
            'discipline' => $this->buildDiscipline($students, $studentIds, $today),
        ]);
    }

    /**
     * @return array{0: array<int, array{label: string, rows: Collection}>, 1: array{label: string, rows: Collection}}
     */
    private function buildHafalanTuntas(ClassRoom $classRoom, Collection $students, Collection $studentIds, Carbon $today, AcademicCalendarService $calendar, QuranLineTargetService $positionCheck): array
    {
        $months = $calendar->termMonths($today);
        $termStart = reset($months)['start'];
        $termEnd = end($months)['end'];

        $termHafalan = HafalanRecord::flattenSurahs(
            HafalanRecord::query()
                ->with(['surahs' => fn ($q) => $q->where('status', 'passed')->with('surah')])
                ->whereIn('student_id', $studentIds)
                ->whereHas('surahs', fn ($q) => $q->where('status', 'passed'))
                ->whereBetween('submitted_at', [$termStart->toDateString(), $termEnd->toDateString()])
                ->orderBy('submitted_at')
                ->get()
        );

        $targetsByStudent = HafalanTarget::query()
            ->with('surah')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('target_date', [$termStart->toDateString(), $termEnd->toDateString()])
            ->orderBy('target_date', 'desc')
            ->get()
            ->groupBy('student_id');

        // Kelas 11 & 12: target baris = pertemuan aktif x level, capaian = baris setoran lulus
        // (HafalanProgressService::termBreakdown). Kelas 10/Ummi: seperti semula.
        $progress = app(HafalanProgressService::class);
        $breakdowns = $classRoom->isGradeTen() ? [] : $students
            ->filter(fn (Student $student) => AutoHafalanTargetService::levelBaris($student->tahfizh_level) !== null)
            ->each(fn (Student $student) => $student->setRelation('classRoom', $classRoom))
            ->mapWithKeys(fn (Student $student) => [$student->id => $progress->termBreakdown(
                $student, $targetsByStudent->get($student->id, collect()), $months, $today->copy()->endOfDay()
            )])
            ->all();

        $monthly = [];
        $termCapaian = [];
        $termTarget = [];

        foreach ($months as $monthKey => $range) {
            $rows = collect();

            foreach ($students as $student) {
                $levelBaris = AutoHafalanTargetService::levelBaris($student->tahfizh_level);
                $cell = $breakdowns[$student->id]['months'][$monthKey] ?? null;

                if ($cell !== null) {
                    $capaian = $cell['achieved_lines'];
                    $target = $cell['target_lines'];
                    $isTuntas = $cell['reached'];
                } else {
                    $capaian = $termHafalan->where('student_id', $student->id)
                        ->filter(fn ($h) => Carbon::parse($h->submitted_at)->between($range['start'], $range['end']))
                        ->sum('lines_count');
                    $target = $levelBaris === null ? 0 : $levelBaris * $calendar->scheduledMeetings($classRoom, $range['start'], $range['end']);
                    $isTuntas = $levelBaris === null || $capaian >= $target;
                }

                $termCapaian[$student->id] = ($termCapaian[$student->id] ?? 0) + $capaian;
                $termTarget[$student->id] = ($termTarget[$student->id] ?? 0) + $target;

                if (! $isTuntas) {
                    $rows->push([
                        'student' => $student,
                        'capaian_baris' => $capaian,
                        'target_baris' => $target,
                    ]);
                }
            }

            $monthly[$monthKey] = [
                'label' => self::MONTH_NAMES[$range['start']->format('m')] ?? $monthKey,
                'rows' => $rows,
            ];
        }

        $termRows = collect();
        foreach ($students as $student) {
            $levelBaris = AutoHafalanTargetService::levelBaris($student->tahfizh_level);
            $evaluation = $breakdowns[$student->id]['evaluation'] ?? null;

            if (isset($breakdowns[$student->id])) {
                $capaian = $evaluation['achieved_lines'] ?? $termCapaian[$student->id] ?? 0;
                $target = $evaluation['target_lines'] ?? 0;
                $isTuntas = $evaluation['reached'] ?? false;
            } else {
                $capaian = $termCapaian[$student->id] ?? 0;
                $target = $termTarget[$student->id] ?? 0;
                $isTuntas = $levelBaris === null || $capaian >= $target;
            }

            if (! $isTuntas) {
                $termRows->push([
                    'student' => $student,
                    'capaian_baris' => $capaian,
                    'target_baris' => $target,
                ]);
            }
        }

        $firstMonth = reset($months);
        $lastMonth = end($months);
        $termLabel = (self::MONTH_NAMES[$firstMonth['start']->format('m')] ?? '')
            .' - '.(self::MONTH_NAMES[$lastMonth['start']->format('m')] ?? '');

        return [$monthly, ['label' => $termLabel, 'rows' => $termRows]];
    }

    private function buildAdabToday(Collection $students, Collection $studentIds, Carbon $today): array
    {
        $isEffectiveDay = Setting::isEffectiveAdabDay($today);

        // whereDate() dipakai (bukan where() biasa) supaya perbandingan tanggal benar
        // walau kolom assessment_date tersimpan dengan komponen jam di beberapa driver DB.
        $submittedIds = AdabRecord::query()
            ->whereIn('student_id', $studentIds)
            ->whereDate('assessment_date', $today->toDateString())
            ->pluck('student_id')
            ->all();

        $missing = $students->reject(fn (Student $s) => in_array($s->id, $submittedIds, true))->values();

        return [
            'is_effective_day' => $isEffectiveDay,
            'submitted_count' => count($submittedIds),
            'total_count' => $students->count(),
            'missing' => $missing,
        ];
    }

    private function buildDiscipline(Collection $students, Collection $studentIds, Carbon $today): array
    {
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $violations = StudentPoint::violations()
            ->whereIn('student_id', $studentIds)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderByDesc('date')
            ->get()
            ->groupBy('student_id');

        $rows = $students->map(function (Student $student) use ($violations) {
            $studentViolations = $violations->get($student->id, collect());
            $totalPoints = $studentViolations->sum('points');
            $score = max(0, 100 - $totalPoints);
            $grade = match (true) {
                $score >= 90 => 'A',
                $score >= 80 => 'B',
                $score >= 70 => 'C',
                $score >= 60 => 'D',
                default => 'E',
            };

            return [
                'student' => $student,
                'count' => $studentViolations->count(),
                'total_points' => $totalPoints,
                'grade' => $grade,
                'items' => $studentViolations->take(5),
            ];
        })->sortByDesc('total_points')->values();

        return [
            'label' => (self::MONTH_NAMES[$monthStart->format('m')] ?? '').' '.$monthStart->format('Y'),
            'rows' => $rows,
        ];
    }
}
