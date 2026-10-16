<?php

namespace App\Http\Controllers;

use App\Models\Surah;
use App\Models\UmmiProsemRow;
use App\Services\UmmiProsemService;
use App\Support\AcademicYear;
use App\Support\UmmiBook;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $rows = $this->prosem->rows($year, $semester);
        $plan = $this->prosem->plan($year, $semester);

        return view('hafalan-targets.prosem', [
            'year' => $year,
            'semester' => $semester,
            'years' => AcademicYear::options($year),
            'months' => collect(UmmiProsemService::SEMESTER_MONTHS[$semester])->mapWithKeys(fn ($m) => [$m => self::MONTH_LABELS[$m]])->all(),
            'rows' => $rows,
            'plan' => $plan,
            'monthTargets' => collect(UmmiProsemService::SEMESTER_MONTHS[$semester])
                ->mapWithKeys(fn ($m) => [$m => $this->prosem->monthTarget($year, $semester, $m)])->all(),
            'surahs' => Surah::query()->orderBy('number')->get(['id', 'number', 'name_latin']),
            'books' => UmmiBook::BOOKS,
            'canEdit' => self::canEdit($request->user()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(self::canEdit($request->user()), 403);
        [$year, $semester] = $this->period($request);
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
                'academic_year' => $year, 'semester' => $semester, 'position' => $i + 1,
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

        DB::transaction(function () use ($rows, $year, $semester) {
            UmmiProsemRow::query()->where('academic_year', $year)->where('semester', $semester)->delete();
            foreach ($rows as $row) {
                UmmiProsemRow::create($row);
            }
        });
        $this->prosem->forgetPlans();
        $synced = $this->prosem->syncTargets($year, $semester);

        return redirect()->route('prosem-ummi.index', ['academic_year' => $year, 'semester' => $semester])
            ->with('success', 'Prosem disimpan ('.count($rows)." baris). Target Ummi otomatis diperbarui: {$synced}.");
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
