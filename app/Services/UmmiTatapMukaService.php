<?php

namespace App\Services;

use App\Models\Student;
use App\Models\UmmiRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Nomor Tatap Muka (TM) Ummi = urutan pertemuan Ummi yang benar-benar terjadi di triwulan itu,
 * per halaqoh (kelas + guru pengampu): tanggal pertemuan Ummi pertama di triwulan = TM 1,
 * berikutnya TM 2, dst. Satu tanggal = satu pertemuan untuk semua murid halaqoh itu.
 *
 * Dipanggil ulang setiap setoran Ummi disimpan/diubah/dihapus, supaya pertemuan susulan yang
 * diinput belakangan ikut menggeser nomor pertemuan sesudahnya.
 */
class UmmiTatapMukaService
{
    public function __construct(private readonly AcademicCalendarService $calendar) {}

    /**
     * TM untuk pertemuan baru di tanggal ini bagi murid-murid halaqoh ini (belum tersimpan).
     *
     * @param  int[]  $studentIds
     */
    public function numberFor(array $studentIds, Carbon $date): int
    {
        $halaqahIds = $this->halaqahStudentIds($studentIds);
        [$start] = $this->termRange($date);

        return UmmiRecord::query()
            ->whereIn('student_id', $halaqahIds)
            ->whereDate('tanggal', '>=', $start->toDateString())
            ->whereDate('tanggal', '<', $date->toDateString())
            ->pluck('tanggal')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->count() + 1;
    }

    /**
     * Nomori ulang TM halaqoh-halaqoh & triwulan yang tersentuh pasangan (murid, tanggal) ini.
     *
     * @param  iterable<array{0: int, 1: string|Carbon}>  $studentDates
     * @return Collection<int, array{record: UmmiRecord, old: int, new: int}> perubahan (dry-run tidak menyimpan)
     */
    public function renumber(iterable $studentDates, bool $dryRun = false): Collection
    {
        $students = Student::query()
            ->whereIn('id', collect($studentDates)->pluck(0)->unique()->all())
            ->get(['id', 'class_room_id', 'teacher_id'])
            ->keyBy('id');

        $groups = [];
        foreach ($studentDates as [$studentId, $date]) {
            $student = $students->get($studentId);
            if (! $student) {
                continue;
            }
            [$start] = $this->termRange(Carbon::parse($date));
            $groups[$student->class_room_id.'|'.$student->teacher_id.'|'.$start->toDateString()] = [$student, $start];
        }

        $changes = collect();
        foreach ($groups as [$student, $start]) {
            $changes = $changes->merge($this->renumberGroup($student, $start, $dryRun));
        }

        return $changes;
    }

    /**
     * Semua halaqoh yang punya setoran Ummi di triwulan yang memuat tanggal ini.
     *
     * @return Collection<int, array{record: UmmiRecord, old: int, new: int}>
     */
    public function renumberTerm(Carbon $date, bool $dryRun = false): Collection
    {
        [$start, $end] = $this->termRange($date);

        $pairs = UmmiRecord::query()
            ->whereDate('tanggal', '>=', $start->toDateString())
            ->whereDate('tanggal', '<=', $end->toDateString())
            ->get(['student_id', 'tanggal'])
            ->unique('student_id')
            ->map(fn ($r) => [$r->student_id, $start->toDateString()])
            ->values()
            ->all();

        return $this->renumber($pairs, $dryRun);
    }

    private function renumberGroup(Student $student, Carbon $start, bool $dryRun): Collection
    {
        $end = $start->copy()->addMonthsNoOverflow(3)->subDay();
        $records = UmmiRecord::query()
            ->with('student:id,name,class_room_id,teacher_id', 'student.classRoom:id,name', 'student.teacher.user:id,name')
            ->whereIn('student_id', $this->halaqahStudentIds([$student->id]))
            ->whereDate('tanggal', '>=', $start->toDateString())
            ->whereDate('tanggal', '<=', $end->toDateString())
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $numbers = $records->map(fn ($r) => $r->tanggal->toDateString())->unique()->values()->flip()->map(fn ($i) => $i + 1);

        $changes = $records
            ->filter(fn ($r) => (int) $r->tatap_muka !== $numbers[$r->tanggal->toDateString()])
            ->map(fn ($r) => ['record' => $r, 'old' => (int) $r->tatap_muka, 'new' => $numbers[$r->tanggal->toDateString()]])
            ->values();

        if (! $dryRun) {
            // Update massal tanpa event model: nomor TM saja yang berubah.
            $changes->groupBy('new')->each(fn ($items, $new) => UmmiRecord::query()
                ->whereIn('id', $items->pluck('record.id'))
                ->update(['tatap_muka' => (int) $new]));
        }

        return $changes;
    }

    /** Murid satu halaqoh (kelas & guru pengampu sama) dengan murid-murid ini. */
    private function halaqahStudentIds(array $studentIds): array
    {
        $pairs = Student::query()->whereIn('id', $studentIds)->get(['class_room_id', 'teacher_id']);
        if ($pairs->isEmpty()) {
            return [];
        }

        return Student::query()
            ->where(function ($q) use ($pairs) {
                foreach ($pairs->unique(fn ($s) => $s->class_room_id.'|'.$s->teacher_id) as $s) {
                    $q->orWhere(fn ($w) => $w->where('class_room_id', $s->class_room_id)
                        ->when($s->teacher_id === null, fn ($t) => $t->whereNull('teacher_id'), fn ($t) => $t->where('teacher_id', $s->teacher_id)));
                }
            })
            ->pluck('id')
            ->all();
    }

    /** @return array{0: Carbon, 1: Carbon} awal & akhir triwulan yang memuat tanggal ini */
    private function termRange(Carbon $date): array
    {
        $start = $this->calendar->termStartDate($date);

        return [$start, $start->copy()->addMonthsNoOverflow(3)->subDay()];
    }
}
