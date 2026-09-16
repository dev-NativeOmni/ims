<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Regresi untuk bug: kolom presensi mingguan di Laporan Triwulan (Halaqoh)
 * selalu menampilkan "Hadir" walau tidak ada pertemuan nyata di pekan itu
 * (bug ternary `$hasSetoran ? 'Hadir' : 'Hadir'`), dan target baris bulanan
 * memakai pengali pertemuan tetap (4 atau 20) alih-alih jumlah pertemuan
 * aktif sungguhan bulan tsb, sehingga capaian vs target tidak sinkron.
 */
class QuarterlyReportPresensiTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function weekly_presensi_only_marks_hadir_on_weeks_with_real_attendance_or_setoran(): void
    {
        $program = Program::create([
            'name' => 'Program Qiroati Reguler',
            'status' => 'active',
        ]);

        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas Reguler Test',
            'level' => 'Menengah',
        ]);

        $this->student->update([
            'class_room_id' => $classRoom->id,
            'tahfizh_level' => 'reguler',
        ]);

        // Hanya 2 pertemuan nyata bulan ini: 24 Sept (pekan 4) & 29 Sept (pekan 5).
        Attendance::create([
            'student_id' => $this->student->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => '2026-09-24',
            'status' => 'hadir',
        ]);
        Attendance::create([
            'student_id' => $this->student->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => '2026-09-29',
            'status' => 'hadir',
        ]);

        foreach (['2026-09-24', '2026-09-29'] as $date) {
            $record = HafalanRecord::create([
                'student_id' => $this->student->id,
                'teacher_id' => $this->teacherProfile->id,
                'submitted_at' => $date,
            ]);
            $record->surahs()->create([
                'surah_id' => $this->surah->id,
                'ayah_start' => 1,
                'ayah_end' => 5,
                'submission_type' => 'new',
                'status' => 'passed',
                'score' => 90,
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('reports.quarterly', [
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
            'month' => '09',
        ]));

        $response->assertStatus(200);

        $response->assertViewHas('halaqahData', function ($halaqahData) {
            $halaqah = collect($halaqahData)->first();
            $pekan = $halaqah['presensi'][$this->student->id]['pekan'];

            // Pekan 1-3: tidak ada presensi maupun setoran nyata -> jangan "Hadir".
            foreach ([1, 2, 3] as $p) {
                if ($pekan[$p] === 'Hadir') {
                    return false;
                }
            }

            // Pekan 4 & 5: ada presensi/setoran nyata -> harus "Hadir".
            if ($pekan[4] !== 'Hadir' || $pekan[5] !== 'Hadir') {
                return false;
            }

            // Target baris = level (reguler=5) x jumlah pertemuan aktif (2),
            // bukan pengali tetap (4 atau 20).
            $regulerRow = collect($halaqah['reguler_records'])
                ->firstWhere('student_id', $this->student->id);

            return $regulerRow['target_lines'] === 10;
        });
    }
}
