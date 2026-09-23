<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Download Laporan Triwulan per kelas ke file .xlsx nyata (bukan sekadar CSV
 * berlabel xlsx) dengan satu sheet per tab yang tampil di layar: Term-Indeks,
 * Presensi, Jurnal, Setoran -- isinya harus sama persis dengan data yang dipakai
 * untuk merender halaman (lihat QuarterlyReportController::buildReportData()).
 */
class QuarterlyReportExportTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function downloadAndLoad(array $query): Spreadsheet
    {
        $response = $this->actingAs($this->admin)->get(route('reports.quarterly.export', $query));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $tmpPath = tempnam(sys_get_temp_dir(), 'qre').'.xlsx';
        file_put_contents($tmpPath, $response->streamedContent());

        $spreadsheet = IOFactory::load($tmpPath);
        unlink($tmpPath);

        return $spreadsheet;
    }

    #[Test]
    public function export_produces_a_real_xlsx_with_one_sheet_per_tab_and_only_the_selected_class(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas XII F4 Export',
            'level' => 'XII',
            'tahfizh_days' => [1, 2, 3, 4, 5],
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'reguler']);

        $otherClass = ClassRoom::create(['program_id' => $program->id, 'name' => 'Kelas Lain', 'level' => 'XII']);
        $otherStudent = Student::create([
            'class_room_id' => $otherClass->id,
            'teacher_id' => $this->teacherProfile->id,
            'name' => 'Murid Kelas Lain',
            'student_number' => 'TEST-SNT-901',
            'gender' => 'male',
            'birth_date' => '2009-01-01',
            'status' => 'active',
        ]);

        Attendance::create([
            'student_id' => $this->student->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => '2026-07-06',
            'status' => 'hadir',
        ]);
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-07-06',
        ])->surahs()->create([
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'submission_type' => 'new',
            'status' => 'passed',
            'score' => 90,
        ]);
        StudentPoint::create([
            'student_id' => $this->student->id,
            'type' => 'violation',
            'points' => 5,
            'title' => 'Terlambat',
            'date' => '2026-07-06',
            'logged_by' => $this->teacherUser->id,
        ]);

        $spreadsheet = $this->downloadAndLoad([
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ]);

        $sheetTitles = array_map(fn ($s) => $s->getTitle(), $spreadsheet->getAllSheets());
        $this->assertSame(['Term-Indeks', 'Presensi', 'Jurnal', 'Setoran', 'Grafik Akhir Bulan', 'Indeks'], $sheetTitles);

        $termSheet = $spreadsheet->getSheetByName('Term-Indeks');
        $rows = $termSheet->toArray();
        $this->assertSame(
            ['Kelas', 'Halaqah (Musyrif)', 'No', 'Nama Murid', 'Level', 'Target Surah', 'Target Ayat', 'Capaian Surah', 'Capaian Ayat', 'Capaian Baris', 'Target Baris', 'Ketercapaian', 'Alpa', 'Izin', 'Sakit', 'Pelanggaran'],
            $rows[0]
        );
        $studentNames = collect($rows)->skip(1)->pluck(3)->all();
        $this->assertContains($this->student->name, $studentNames);
        $this->assertNotContains($otherStudent->name, $studentNames);

        // Pelanggaran murid muncul di kolom terakhir (Term-Indeks).
        $studentRow = collect($rows)->skip(1)->firstWhere(3, $this->student->name);
        $this->assertSame('1', (string) $studentRow[15]);

        $presensiSheet = $spreadsheet->getSheetByName('Presensi');
        $presensiRows = $presensiSheet->toArray();
        $this->assertGreaterThan(1, count($presensiRows));

        $setoranSheet = $spreadsheet->getSheetByName('Setoran');
        $setoranRows = collect($setoranSheet->toArray())->skip(1);
        $this->assertTrue($setoranRows->contains(fn ($r) => str_contains((string) $r[7], 'Al-Fatihah')));

        $grafikSheet = $spreadsheet->getSheetByName('Grafik Akhir Bulan');
        $grafikRows = collect($grafikSheet->toArray())->skip(1);
        $this->assertTrue($grafikRows->contains(fn ($r) => $r[3] === $this->student->name));

        $indeksSheet = $spreadsheet->getSheetByName('Indeks');
        $indeksRows = collect($indeksSheet->toArray())->skip(1);
        $this->assertTrue($indeksRows->contains(fn ($r) => $r[1] === 'Al-Fatihah'));
    }

    #[Test]
    public function export_can_be_narrowed_down_to_a_single_halaqoh(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas XII F5 Export',
            'level' => 'XII',
            'tahfizh_days' => [1, 2, 3, 4, 5],
        ]);
        $this->student->update([
            'class_room_id' => $classRoom->id,
            'tahfizh_level' => 'reguler',
            'name' => 'Murid Halaqoh Satu',
        ]);

        $secondTeacherRole = Role::where('name', 'teacher')->firstOrFail();
        $secondTeacherUser = User::factory()->create(['role_id' => $secondTeacherRole->id, 'name' => 'Ust. Halaqoh Dua', 'status' => 'active']);
        $secondTeacherProfile = TeacherProfile::create(['user_id' => $secondTeacherUser->id, 'employee_number' => 'TEST-GURU-002']);
        $secondStudent = Student::create([
            'class_room_id' => $classRoom->id,
            'teacher_id' => $secondTeacherProfile->id,
            'name' => 'Murid Halaqoh Dua',
            'student_number' => 'TEST-SNT-902',
            'gender' => 'male',
            'birth_date' => '2009-01-01',
            'status' => 'active',
        ]);

        $query = [
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ];

        // Pastikan dua halaqoh benar-benar terbentuk sebelum diuji filternya.
        $fullSpreadsheet = $this->downloadAndLoad($query);
        $fullNames = collect($fullSpreadsheet->getSheetByName('Term-Indeks')->toArray())->skip(1)->pluck(3);
        $this->assertContains('Murid Halaqoh Satu', $fullNames);
        $this->assertContains('Murid Halaqoh Dua', $fullNames);

        $filtered = $this->downloadAndLoad($query + ['musyrif' => $this->teacherUser->name]);
        $filteredNames = collect($filtered->getSheetByName('Term-Indeks')->toArray())->skip(1)->pluck(3);
        $this->assertContains('Murid Halaqoh Satu', $filteredNames);
        $this->assertNotContains('Murid Halaqoh Dua', $filteredNames);
    }

    #[Test]
    public function only_admin_and_super_admin_can_download_the_export(): void
    {
        $this->get(route('reports.quarterly.export'))->assertRedirect(route('login'));
        $this->actingAs($this->teacherUser)->get(route('reports.quarterly.export'))->assertStatus(403);
    }
}
