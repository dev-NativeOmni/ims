<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\UmmiRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Data Ummi kelas 10 konsisten di Grafik Tahfizh, Laporan Triwulan, dan rapor:
 * - murid kelas 10 = murid Ummi walau levelnya bukan "ummi" (Student::usesUmmi);
 * - posisi Jilid/Halaman = catatan Ummi terakhir yang berisi jilid.
 */
class UmmiConsistencyTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classX;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-15 09:00');
        $this->setUpHafizPlusData();
        $program = Program::create(['name' => 'Program Tahfizh', 'status' => 'active']);
        $this->classX = ClassRoom::create(['program_id' => $program->id, 'name' => 'X E1', 'level' => 'X', 'tahfizh_days' => [1, 2, 3, 4]]);
        // Kelas 10 tetapi level masih "reguler" (mis. dari impor data).
        $this->student->update(['class_room_id' => $this->classX->id, 'tahfizh_level' => 'reguler', 'teacher_id' => $this->teacherProfile->id]);

        $record = UmmiRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'tanggal' => '2026-09-10', 'tatap_muka' => 1, 'ummi_jilid' => 'Jilid 2', 'ummi_halaman' => '24-25']);
        $record->surahs()->create(['surah_id' => $this->surah->id, 'hafalan_ayah' => '1-5']);
        // Pertemuan terakhir tanpa jilid (hanya materi).
        UmmiRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'tanggal' => '2026-09-20', 'tatap_muka' => 2, 'materi' => 'Sukun']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function grade_ten_student_is_read_as_ummi_everywhere(): void
    {
        $this->assertTrue($this->student->fresh()->usesUmmi());

        // Rapor Tengah Semester I: tabel Ummi dengan posisi buku terakhir yang berisi jilid.
        $this->actingAs($this->admin)->get(route('digital-reports.print', [$this->student, 'academic_year' => '2026/2027', 'term' => 1]))
            ->assertOk()->assertSee('NILAI')->assertSeeInOrder(['CAPAIAN AKHIR', 'Jilid 2', '25']);

        // Laporan Triwulan: kolom Jilid/Halaman & capaian Jilid 2 hal 25.
        $this->actingAs($this->admin)->get(route('reports.quarterly', ['academic_year' => '2026/2027', 'term' => '1', 'class_room_id' => $this->classX->id]))
            ->assertOk()->assertSee('>Halaman</th>', false)->assertSee('Jilid 2');

        // Grafik Tahfizh (kartu UMMI): Jilid/Halaman tidak kosong karena pertemuan terakhir tanpa jilid.
        $row = $this->actingAs($this->admin)->get(route('reports.periodic', ['class_room_id' => $this->classX->id, 'period_type' => 'quarterly', 'quarter' => 1, 'year' => 2026]))
            ->assertOk()->viewData('studentReports')[0];
        $this->assertSame(['2', '25'], [(string) $row['ummi_jilid'], (string) $row['ummi_halaman']]);
    }
}
