<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\HafalanRecord;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Spreadsheet: presensi wajib di setiap pertemuan & "hadir tanpa setoran" (H kuning).
 */
class SpreadsheetMandatoryAttendanceTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private Student $other;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-10 10:00');
        $this->setUpHafizPlusData();
        $this->other = Student::create([
            'class_room_id' => $this->student->class_room_id, 'teacher_id' => $this->teacherProfile->id, 'name' => 'Murid Kedua',
            'student_number' => 'TEST-SNT-555', 'gender' => 'male', 'birth_date' => '2010-01-01', 'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function save(array $records)
    {
        return $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), [
            'class_room_id' => $this->student->class_room_id, 'month' => '2026-08', 'type' => 'hafalan', 'records' => $records,
        ]);
    }

    private function setoran(): array
    {
        return ['attendance' => 'hadir', 'attendance_original' => '', 'hafalans' => [[
            'id' => null, 'surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 5, 'score' => '', 'status' => 'passed', 'submission_type' => 'new',
        ]]];
    }

    #[Test]
    public function saving_a_meeting_requires_attendance_for_every_student(): void
    {
        $this->save([$this->student->id => ['dates' => ['2026-08-03' => $this->setoran()]]])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Presensi wajib') && str_contains($m, 'Murid Kedua (03/08/2026)'));
        $this->assertSame(0, HafalanRecord::count(), 'Tidak ada yang tersimpan.');

        $this->save([
            $this->student->id => ['dates' => ['2026-08-03' => $this->setoran()]],
            $this->other->id => ['dates' => ['2026-08-03' => ['attendance' => 'sakit', 'attendance_original' => '']]],
        ])->assertSessionHas('success');
        $this->assertSame(1, HafalanRecord::count());

        // Presensi yang sudah tersimpan sebelumnya juga dihitung.
        $this->save([$this->student->id => ['dates' => ['2026-08-03' => ['attendance' => 'hadir', 'attendance_original' => 'hadir'] + $this->setoran()]]])
            ->assertSessionHas('success');
    }

    #[Test]
    public function present_without_setoran_keeps_hadir_and_removes_the_setoran(): void
    {
        $this->save([
            $this->student->id => ['dates' => ['2026-08-03' => $this->setoran()]],
            $this->other->id => ['dates' => ['2026-08-03' => ['attendance' => 'izin', 'attendance_original' => '']]],
        ])->assertSessionHas('success');
        $lineId = HafalanRecord::first()->surahs()->value('id');

        // H diklik lagi: surah dikosongkan, presensi tetap Hadir.
        $this->save([$this->student->id => ['dates' => ['2026-08-03' => [
            'attendance' => 'hadir', 'attendance_original' => 'hadir',
            'hafalans' => [['id' => $lineId, 'surah_id' => '', 'ayah_start' => '', 'ayah_end' => '', 'score' => '', 'status' => 'passed', 'submission_type' => 'new']],
        ]]]])->assertSessionHas('success');

        $this->assertSame(0, HafalanRecord::count());
        $this->assertSame('hadir', Attendance::where('student_id', $this->student->id)->value('status'));

        $this->actingAs($this->teacherUser)->get(route('spreadsheet-input.index', ['class_room_id' => $this->student->class_room_id, 'month' => '2026-08']))
            ->assertOk()->assertSee('isHadirTanpaSetoran(cell)', false)->assertSee('Presensi wajib');
    }
}
