<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Surah;
use App\Services\HafalanProgressService;
use App\Services\QuranLineTargetService;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Kelas 11 & 12: Juz 30 dihitung dari An-Naba (78) ke An-Nas (114) untuk target & capaian rapor,
 * walau setoran lamanya dari An-Nas. Koreksi guru per murid tetap menang; Kelas 10/Ummi tidak berubah.
 */
class Juz30OrderGrade11And12Test extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private HafalanProgressService $progress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        $this->progress = app(HafalanProgressService::class);
        foreach ([67 => ['Al-Mulk', 30, 29], 71 => ['Nuh', 28, 29], 77 => ['Al-Mursalat', 50, 29], 78 => ['An-Naba', 40, 30], 79 => ["An-Nazi'at", 46, 30], 113 => ['Al-Falaq', 5, 30], 114 => ['An-Nas', 6, 30]] as $number => [$name, $ayahs, $juz]) {
            Surah::firstOrCreate(['number' => $number], ['name_ar' => $name, 'name_latin' => $name, 'total_ayah' => $ayahs, 'juz_start' => $juz, 'juz_end' => $juz]);
        }
    }

    private function moveToClass(string $name): void
    {
        $class = ClassRoom::create(['program_id' => $this->student->classRoom->program_id, 'name' => $name, 'level' => '']);
        $this->student->update(['class_room_id' => $class->id, 'tahfizh_level' => 'reguler']);
        $this->student->refresh()->load('classRoom');
    }

    private function setor(int $surahNumber, int $start, int $end, string $date): void
    {
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $header->surahs()->create(['surah_id' => Surah::where('number', $surahNumber)->value('id'), 'ayah_start' => $start, 'ayah_end' => $end, 'status' => 'passed', 'submission_type' => 'new']);
    }

    #[Test]
    public function grade_12_juz_30_runs_from_an_naba_even_if_old_setoran_started_from_an_nas(): void
    {
        $this->moveToClass('XII F3');
        // Setoran lama dari An-Nas mundur (deteksi = dari akhir).
        $this->setor(114, 1, 6, '2026-07-10');
        $this->setor(113, 1, 5, '2026-07-11');

        $records = $this->progress->records($this->student);
        $this->assertSame(HafalanOrder::DESC, $this->progress->detectedJuzOrders($records)[30]);
        $this->assertSame(HafalanOrder::ASC, $this->progress->juzOrders($this->student, $records)[30]);
    }

    #[Test]
    public function grade_11_new_student_starts_target_at_an_naba(): void
    {
        $this->moveToClass('XI F1');

        $start = $this->progress->startPoint($this->student, collect(), Carbon::parse('2026-07-01'), Carbon::parse('2026-09-30'));

        $this->assertSame([78, 1], [$start['surah'], $start['ayah']]);
    }

    #[Test]
    public function same_day_capaian_picks_the_furthest_surah_from_an_naba(): void
    {
        $this->moveToClass('XII F2');
        $this->setor(78, 1, 40, '2026-09-17');
        $this->setor(79, 1, 10, '2026-09-17');

        $lines = HafalanRecord::flattenSurahs(HafalanRecord::with('surahs.surah')->where('student_id', $this->student->id)->get())
            ->each(fn ($line) => $line->setRelation('surah', $line->surah));
        $capaian = app(QuranLineTargetService::class)->latestByPosition($lines, $this->student->hafalan_direction, $this->progress->juzOrdersFor($this->student));

        $this->assertSame(79, $capaian->surah->number, "An-Nazi'at lebih jauh dari An-Naba bila mulai dari An-Naba.");
    }

    #[Test]
    public function teacher_correction_and_grade_10_keep_their_order(): void
    {
        $this->moveToClass('XII F1');
        $this->student->update(['juz_orders' => [30 => HafalanOrder::DESC]]);
        $this->assertSame(HafalanOrder::DESC, $this->progress->juzOrdersFor($this->student->refresh()->load('classRoom'))[30], 'Koreksi guru menang.');

        $this->moveToClass('X E1');
        $this->student->update(['juz_orders' => null]);
        $this->assertArrayNotHasKey(30, $this->progress->juzOrdersFor($this->student->refresh()->load('classRoom')), 'Kelas 10 tetap bawaan.');
    }

    #[Test]
    public function grade_11_and_12_memorise_every_juz_from_its_start_unless_the_teacher_says_otherwise(): void
    {
        $this->moveToClass('XI F2');
        // Setoran Juz 29 dari belakang (Al-Mursalat lalu Nuh): tetap dihitung dari awal juz (aturan sekolah).
        $this->setor(77, 1, 50, '2026-09-01');
        $this->setor(71, 1, 28, '2026-09-03');
        $this->assertSame(HafalanOrder::DESC, $this->progress->detectedJuzOrders($this->progress->records($this->student))[29]);
        $this->assertSame(HafalanOrder::ASC, $this->progress->juzOrdersFor($this->student)[29]);

        // Pengecualian per murid (mis. Vano): diatur guru di halaman Urutan.
        $this->student->update(['juz_orders' => [29 => HafalanOrder::DESC]]);
        $this->assertSame(HafalanOrder::DESC, $this->progress->juzOrdersFor($this->student->refresh()->load('classRoom'))[29]);
    }

    #[Test]
    public function other_classes_still_detect_the_juz_direction_from_setoran(): void
    {
        $this->moveToClass('Kelas Tahfizh Sore');
        $this->setor(77, 1, 10, '2026-09-01');
        $this->assertSame(HafalanOrder::DESC, $this->progress->juzOrdersFor($this->student)[29], 'Mulai dari surah terakhir juz = dari belakang.');

        $this->moveToClass('Kelas Tahfizh Pagi');
        HafalanRecord::query()->delete();
        $this->setor(71, 1, 10, '2026-09-01');
        $this->assertArrayNotHasKey(29, $this->progress->juzOrdersFor($this->student), 'Surah tengah: pakai default / koreksi guru.');
    }
}
