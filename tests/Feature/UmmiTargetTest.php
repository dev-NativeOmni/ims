<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Student;
use App\Models\Surah;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Target Ummi per bulan: tabel per murid (isi serentak + penyesuaian per murid), satu target
 * per murid per bulan, status Buku & Hafalan terpisah dan otomatis.
 */
class UmmiTargetTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classRoom;

    private Student $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        Carbon::setTestNow('2026-09-10 10:00:00');

        foreach (HafalanOrder::JUZ_RANGES[30] as $range) {
            Surah::updateOrCreate(['number' => $range['surah']], ['name_ar' => "S{$range['surah']}", 'name_latin' => "Surah {$range['surah']}", 'total_ayah' => $range['end']]);
        }

        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $this->classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'X E1', 'level' => 'X', 'tahfizh_days' => [1, 2, 3, 4]]);
        $this->student->update(['class_room_id' => $this->classRoom->id, 'tahfizh_level' => 'ummi', 'teacher_id' => $this->teacherProfile->id]);
        $this->other = Student::create([
            'class_room_id' => $this->classRoom->id, 'teacher_id' => $this->teacherProfile->id, 'name' => 'Murid Kedua',
            'student_number' => 'U-2', 'gender' => 'male', 'birth_date' => '2010-01-01', 'status' => 'active', 'tahfizh_level' => 'ummi',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function surahId(int $number): int
    {
        return (int) Surah::where('number', $number)->value('id');
    }

    private function save(array $targets)
    {
        return $this->actingAs($this->teacherUser)->post(route('hafalan-targets.ummi.store'), [
            'month' => '2026-09', 'teacher_id' => $this->teacherProfile->id, 'targets' => $targets,
        ]);
    }

    #[Test]
    public function the_table_lists_the_halaqah_with_current_positions(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('hafalan-targets.ummi', ['month' => '2026-09']));

        $response->assertOk()->assertSee('Murid Kedua')->assertSee('Terapkan ke semua baris')->assertDontSee('Halaman Peraga');
        $this->assertCount(2, $response->viewData('students'));
        $this->assertSame('2026-09-30', $response->viewData('deadlines')[$this->classRoom->id]->toDateString(), 'Pertemuan Ummi (Senin-Kamis) terakhir September.');
    }

    #[Test]
    public function one_target_per_student_per_month_with_individual_values(): void
    {
        $this->save([
            $this->student->id => ['jilid' => 'Jilid 2', 'halaman' => '30', 'surah_id' => $this->surahId(86), 'ayah' => '17'],
            $this->other->id => ['jilid' => 'Jilid 2', 'halaman' => '25', 'surah_id' => '', 'ayah' => ''],
        ])->assertSessionHas('success');

        $target = HafalanTarget::where('student_id', $this->student->id)->first();
        $this->assertSame(['Jilid 2', '30', 17, '2026-09-30', null], [$target->ummi_jilid, $target->halaman_buku, $target->ayah, $target->target_date->toDateString(), $target->halaman_peraga]);
        $this->assertSame(['active', 'active'], [$target->book_status, $target->surah_status]);
        $this->assertNull(HafalanTarget::where('student_id', $this->other->id)->value('surah_status'), 'Tanpa target surah.');

        // Simpan ulang memperbarui, bukan menggandakan; baris kosong menghapus.
        $this->save([
            $this->student->id => ['jilid' => 'Jilid 2', 'halaman' => '32', 'surah_id' => $this->surahId(86), 'ayah' => '17'],
            $this->other->id => ['jilid' => '', 'halaman' => '', 'surah_id' => '', 'ayah' => ''],
        ]);
        $this->assertSame(1, HafalanTarget::where('student_id', $this->student->id)->count());
        $this->assertSame('32', $target->fresh()->halaman_buku);
        $this->assertSame(0, HafalanTarget::where('student_id', $this->other->id)->count());
    }

    #[Test]
    public function invalid_rows_reject_the_whole_table(): void
    {
        $this->save([
            $this->student->id => ['jilid' => 'Jilid 2', 'halaman' => '30'],
            $this->other->id => ['jilid' => 'Jilid 2', 'halaman' => '55'],
        ])->assertSessionHasErrors("targets.{$this->other->id}");

        $this->assertSame(0, HafalanTarget::count());
    }

    #[Test]
    public function book_and_hafalan_statuses_are_set_separately_and_automatically(): void
    {
        $this->save([$this->student->id => ['jilid' => 'Jilid 2', 'halaman' => '10', 'surah_id' => $this->surahId(113), 'ayah' => '5']]);
        $target = HafalanTarget::where('student_id', $this->student->id)->first();

        // Setoran Ummi lewat spreadsheet: buku sudah J2 h.12, hafalan baru An-Nas.
        $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), [
            'class_room_id' => $this->classRoom->id, 'month' => '2026-09', 'type' => 'ummi',
            'records' => [$this->student->id => ['dates' => ['2026-09-09' => [
                'attendance' => 'hadir', 'ummi_jilid' => 'Jilid 2', 'ummi_halaman' => '12',
                'hafalans' => [['id' => null, 'surah_id' => $this->surahId(114), 'ayah' => '1-6']],
            ]]]],
        ])->assertSessionHas('success');

        $target->refresh();
        $this->assertSame('completed', $target->book_status, 'J2 h.12 >= target J2 h.10, langsung saat disimpan.');
        $this->assertSame('active', $target->surah_status, 'Al-Falaq 5 belum tercapai.');
        $this->assertSame('active', $target->status);

        // Lewat deadline: hafalan Terlewat, keseluruhan Terlewat.
        Carbon::setTestNow('2026-10-02 01:00:00');
        $this->artisan('tad:sync-completed-targets')->assertSuccessful();
        $target->refresh();
        $this->assertSame(['completed', 'missed', 'missed'], [$target->book_status, $target->surah_status, $target->status]);
    }

    #[Test]
    public function other_teachers_students_are_not_saved(): void
    {
        $this->other->update(['teacher_id' => null]);

        $this->save([$this->other->id => ['jilid' => 'Jilid 1', 'halaman' => '5']]);

        $this->assertSame(0, HafalanTarget::count());
    }
}
