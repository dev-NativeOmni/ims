<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\StudentPriorHafalan;
use App\Models\Surah;
use App\Services\HafalanProgressService;
use App\Support\HafalanOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Hafalan & Arah Juz (khusus Super Admin): peta 30 juz per murid satu kelas.
 * - Klik juz = tandai/batalkan "sudah hafal" sebagai Hafalan Sebelum Aplikasi (StudentPriorHafalan).
 * - Panah ↑/↓ di juz yang sedang dihafal = arah di dalam juz (dari awal / dari akhir), disimpan
 *   sebagai koreksi guru (Student::juz_orders).
 * Persen & juz berjalan dari HafalanProgressService, sama dengan halaman Urutan murid.
 */
class JuzMapController extends Controller
{
    public function __construct(private readonly HafalanProgressService $progress) {}

    public function index(Request $request): View
    {
        $classRooms = ClassRoom::query()->with('program')->orderBy('name')->get();
        $selectedClass = $classRooms->firstWhere('id', $request->integer('class_room_id'))
            ?? $classRooms->first(fn (ClassRoom $c) => $c->isGradeElevenOrTwelve())
            ?? $classRooms->first();

        $rows = $selectedClass
            ? Student::query()->with('classRoom.program')->where('class_room_id', $selectedClass->id)
                ->where('status', 'active')->orderBy('name')->get()
                ->map(fn (Student $student) => $this->row($student))
            : collect();

        return view('juz-map.index', [
            'classRooms' => $classRooms,
            'selectedClass' => $selectedClass,
            'rows' => $rows->values(),
        ]);
    }

    /** Tandai satu juz sudah hafal (sebelum aplikasi), atau batalkan bila sudah ditandai. */
    public function toggleJuz(Request $request, Student $student): JsonResponse
    {
        $juz = (int) $request->validate(['juz' => ['required', 'integer', 'between:1,30']])['juz'];
        $priorInJuz = $student->priorHafalans()->with('surah')->get()
            ->filter(fn (StudentPriorHafalan $p) => HafalanOrder::juzOf((int) $p->surah->number, (int) $p->ayah_start) === $juz);

        if ($this->priorPercentages($student)[$juz] >= 100) {
            $priorInJuz->each->delete();
        } else {
            $surahIds = Surah::query()->pluck('id', 'number');
            foreach (HafalanOrder::JUZ_RANGES[$juz] as $range) {
                StudentPriorHafalan::firstOrCreate(
                    ['student_id' => $student->id, 'surah_id' => $surahIds[$range['surah']], 'ayah_start' => $range['start'], 'ayah_end' => $range['end']],
                    ['created_by' => $request->user()->id]
                );
            }
        }

        return response()->json($this->row($student->fresh()->load('classRoom.program')));
    }

    /** Arah di dalam juz: 'asc' (↑ maju, dari awal juz) atau 'desc' (↓ mundur, dari akhir juz). */
    public function setDirection(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'juz' => ['required', 'integer', 'between:1,30'],
            'order' => ['required', Rule::in([HafalanOrder::ASC, HafalanOrder::DESC])],
        ]);

        $orders = collect($student->juz_orders ?? [])->put((string) $validated['juz'], $validated['order']);
        $student->update(['juz_orders' => $orders->all()]);

        return response()->json($this->row($student->fresh()->load('classRoom.program')));
    }

    /**
     * Data satu baris murid untuk halaman: persen tiap juz, apakah juz ditandai hafal sebelum aplikasi,
     * juz yang sedang dihafal & arahnya.
     */
    private function row(Student $student): array
    {
        $records = $this->progress->records($student);
        $percent = $this->progress->juzPercentages($this->progress->coverage($records));
        $prior = $this->progress->juzPercentages($this->progress->coverage($records->filter(fn ($r) => $r->is_prior)));
        $orders = $this->progress->juzOrders($student, $records);
        $manual = array_map('intval', array_keys($student->juz_orders ?? []));
        $current = $student->usesUmmi() ? null : $this->progress->currentJuz($student, $records);

        return [
            'id' => $student->id,
            'name' => $student->name,
            'level' => $student->tahfizh_level ?? 'reguler',
            'ummi' => $student->usesUmmi(),
            'current' => $current,
            'juz' => collect(range(30, 1))->map(fn (int $juz) => [
                'juz' => $juz,
                'percent' => $percent[$juz],
                'prior' => $prior[$juz] >= 100,
                'order' => $orders[$juz] ?? HafalanOrder::defaultJuzOrder($juz),
                'manual' => in_array($juz, $manual, true),
            ])->all(),
            'toggle_url' => route('juz-map.juz', $student),
            'direction_url' => route('juz-map.direction', $student),
        ];
    }

    /** @return array<int, int> juz => persen yang tercakup hafalan sebelum aplikasi saja */
    private function priorPercentages(Student $student): array
    {
        $records = $this->progress->records($student)->filter(fn ($r) => $r->is_prior);

        return $this->progress->juzPercentages($this->progress->coverage($records));
    }
}
