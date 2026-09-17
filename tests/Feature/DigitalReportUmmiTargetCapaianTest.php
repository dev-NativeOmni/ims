<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\UmmiRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Kolom Target & Capaian di rapor cetak murid UMMI (Kelas 10) harus
 * menampilkan dua baris terpisah "Ummi : Jilid X Hal Y" dan
 * "Tahfizh : Surah X Ayat Y", bukan format "QS. X (Ayat Y)" tunggal yang
 * dipakai untuk target Reguler murni.
 */
class DigitalReportUmmiTargetCapaianTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function ummi_target_row_shows_separate_ummi_and_tahfizh_lines(): void
    {
        $program = Program::create(['name' => 'Tahfizh Kelas 10', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X E1',
            'level' => 'X',
        ]);
        $this->student->update([
            'class_room_id' => $classRoom->id,
            'tahfizh_level' => 'ummi',
        ]);

        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'ummi_jilid' => 'Jilid 2',
            'halaman_buku' => '24-25',
            'surah_id' => $this->surah->id,
            'ayah' => null,
            'target_date' => now(),
            'status' => 'active',
        ]);

        UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => now(),
            'tatap_muka' => 1,
            'ummi_jilid' => 'Jilid 2',
            'ummi_halaman' => '24-25',
            'nilai' => 'B',
        ]);

        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => now(),
        ]);
        $record->surahs()->create([
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 9,
            'submission_type' => 'new',
            'status' => 'passed',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('digital-reports.print', $this->student));

        $response->assertStatus(200);
        $response->assertSee('Ummi : Jilid 2 Hal 24-25', false);
        $response->assertSee('Tahfizh : Surah '.$this->surah->name_latin, false);
    }

    #[Test]
    public function reguler_only_target_row_keeps_the_single_line_format(): void
    {
        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah' => 10,
            'target_date' => now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('digital-reports.print', $this->student));

        $response->assertStatus(200);
        $response->assertSee('QS. '.$this->surah->name_latin.' (Ayat 1 - 10)', false);
        $response->assertDontSee('Ummi : Jilid');
    }
}
