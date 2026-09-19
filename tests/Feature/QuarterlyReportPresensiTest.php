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
        ]));

        $response->assertStatus(200);
        $response->assertDontSee('Pilih Bulan Laporan');

        $response->assertViewHas('halaqahData', function ($halaqahData) {
            $halaqah = collect($halaqahData)->first();

            // Seluruh bulan dalam term (Jul-Sep) tampil sekaligus.
            if (array_keys($halaqah['monthly']) !== ['07', '08', '09']) {
                return false;
            }

            $september = $halaqah['monthly']['09'];
            $pekan = $september['presensi'][$this->student->id]['pekan'];

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
            $regulerRow = collect($september['reguler_records'])
                ->firstWhere('student_id', $this->student->id);

            if ($regulerRow['target_lines'] !== 10) {
                return false;
            }

            // Rekap term menjumlahkan semua bulan: Juli & Agustus kosong, September 10 baris target.
            $termRow = collect($halaqah['term_records'])->firstWhere('student_id', $this->student->id);

            return $termRow['target_lines'] === 10 && $termRow['total_lines'] > 0;
        });
    }

    #[Test]
    public function tahfizh_program_shows_every_month_of_the_term_at_once(): void
    {
        $program = Program::create(['name' => 'Program Tahfizh', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X E1',
            'level' => 'X',
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'reguler']);

        // Setoran di Juli (07-06) dan September (09-08); Agustus kosong.
        foreach (['2026-07-06', '2026-09-08'] as $date) {
            Attendance::create([
                'student_id' => $this->student->id,
                'class_room_id' => $classRoom->id,
                'teacher_id' => $this->teacherProfile->id,
                'tanggal' => $date,
                'status' => 'hadir',
            ]);
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
        ]));

        $response->assertStatus(200);
        $response->assertSee('Juli');
        $response->assertSee('Agustus');
        $response->assertSee('September');

        $response->assertViewHas('halaqahData', function ($halaqahData) {
            $halaqah = collect($halaqahData)->first();
            $studentId = $this->student->id;

            $monthLines = fn ($code) => collect($halaqah['monthly'][$code]['tahfizh_records'])
                ->firstWhere('student_id', $studentId)['total_lines'];

            return array_keys($halaqah['monthly']) === ['07', '08', '09']
                && array_keys($halaqah['presensi'][$studentId]) === ['Juli', 'Agustus', 'September']
                && $monthLines('07') > 0
                && $monthLines('08') == 0
                && $monthLines('09') > 0
                && collect($halaqah['term_records'])->firstWhere('student_id', $studentId)['total_lines']
                    == $monthLines('07') + $monthLines('09');
        });
    }
}
