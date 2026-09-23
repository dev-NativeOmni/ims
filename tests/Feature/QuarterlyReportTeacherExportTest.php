<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\UmmiRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Download Laporan Triwulan "Kelas Saya": guru login download rekap lintas semua
 * kelas/halaqoh yang benar-benar dia ampu (bukan satu kelas seperti export biasa),
 * terpisah per program (Reguler vs Tahfizh), dengan Kelas 10 otomatis memakai
 * data Ummi (Jilid/Halaman) alih-alih Surah/Ayat.
 */
class QuarterlyReportTeacherExportTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function downloadMine(User $user, string $program): Spreadsheet
    {
        $response = $this->actingAs($user)->get(route('reports.quarterly.export.mine', [
            'program' => $program,
            'academic_year' => '2026/2027',
            'term' => '1',
        ]));
        $response->assertStatus(200);

        $tmpPath = tempnam(sys_get_temp_dir(), 'qrem').'.xlsx';
        file_put_contents($tmpPath, $response->streamedContent());
        $spreadsheet = IOFactory::load($tmpPath);
        unlink($tmpPath);

        return $spreadsheet;
    }

    #[Test]
    public function guests_and_non_teachers_cannot_download_it(): void
    {
        $this->get(route('reports.quarterly.export.mine'))->assertRedirect(route('login'));

        $roleParent = Role::where('name', 'parent')->firstOrFail();
        $parent = User::factory()->create(['role_id' => $roleParent->id, 'status' => 'active']);
        $this->actingAs($parent)->get(route('reports.quarterly.export.mine'))->assertForbidden();
    }

    #[Test]
    public function teacher_without_a_teacher_profile_gets_a_clear_error_instead_of_a_crash(): void
    {
        $roleTeacher = Role::where('name', 'teacher')->firstOrFail();
        $orphanTeacher = User::factory()->create(['role_id' => $roleTeacher->id, 'status' => 'active']);

        $this->actingAs($orphanTeacher)
            ->get(route('reports.quarterly.export.mine', ['program' => 'reguler']))
            ->assertForbidden();
    }

    #[Test]
    public function reguler_download_bundles_every_class_the_teacher_teaches_but_not_other_teachers_students(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classA = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI F2', 'level' => 'XI', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        $classB = ClassRoom::create(['program_id' => $program->id, 'name' => 'XII F3', 'level' => 'XII', 'tahfizh_days' => [1, 2, 3, 4, 5]]);

        $this->student->update(['class_room_id' => $classA->id, 'teacher_id' => $this->teacherProfile->id, 'tahfizh_level' => 'reguler', 'name' => 'Murid Kelas A']);

        $studentB = Student::create([
            'class_room_id' => $classB->id,
            'teacher_id' => $this->teacherProfile->id,
            'name' => 'Murid Kelas B',
            'student_number' => 'TEST-SNT-910',
            'gender' => 'male',
            'birth_date' => '2008-01-01',
            'status' => 'active',
            'tahfizh_level' => 'reguler',
        ]);

        $otherTeacherRole = Role::where('name', 'teacher')->firstOrFail();
        $otherTeacherUser = User::factory()->create(['role_id' => $otherTeacherRole->id, 'name' => 'Ust. Lain', 'status' => 'active']);
        $otherTeacherProfile = TeacherProfile::create(['user_id' => $otherTeacherUser->id, 'employee_number' => 'TEST-GURU-910']);
        Student::create([
            'class_room_id' => $classA->id,
            'teacher_id' => $otherTeacherProfile->id,
            'name' => 'Murid Guru Lain',
            'student_number' => 'TEST-SNT-911',
            'gender' => 'male',
            'birth_date' => '2008-01-01',
            'status' => 'active',
            'tahfizh_level' => 'reguler',
        ]);

        $spreadsheet = $this->downloadMine($this->teacherUser, 'reguler');

        $rows = collect($spreadsheet->getSheetByName('Term-Indeks')->toArray())->skip(1);
        $names = $rows->pluck(3);
        $classes = $rows->pluck(0);

        $this->assertContains('Murid Kelas A', $names);
        $this->assertContains('Murid Kelas B', $names);
        $this->assertNotContains('Murid Guru Lain', $names);
        $this->assertContains('XI F2', $classes->all());
        $this->assertContains('XII F3', $classes->all());
    }

    #[Test]
    public function tahfizh_download_only_contains_tahfizh_program_classes_not_reguler_ones(): void
    {
        $regulerProgram = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $tahfizhProgram = Program::create(['name' => 'Program Tahfizh Test', 'status' => 'active']);

        $regulerClass = ClassRoom::create(['program_id' => $regulerProgram->id, 'name' => 'XI F4', 'level' => 'XI', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        $tahfizhClass = ClassRoom::create(['program_id' => $tahfizhProgram->id, 'name' => 'XI Tahfizh 1', 'level' => 'XI', 'tahfizh_days' => [1, 2, 3, 4, 5]]);

        $this->student->update(['class_room_id' => $regulerClass->id, 'teacher_id' => $this->teacherProfile->id, 'tahfizh_level' => 'reguler', 'name' => 'Murid Reguler']);

        Student::create([
            'class_room_id' => $tahfizhClass->id,
            'teacher_id' => $this->teacherProfile->id,
            'name' => 'Murid Tahfizh',
            'student_number' => 'TEST-SNT-920',
            'gender' => 'male',
            'birth_date' => '2008-01-01',
            'status' => 'active',
            'tahfizh_level' => 'akselerasi',
        ]);

        $tahfizhSpreadsheet = $this->downloadMine($this->teacherUser, 'tahfizh');
        $tahfizhNames = collect($tahfizhSpreadsheet->getSheetByName('Term-Indeks')->toArray())->skip(1)->pluck(3);
        $this->assertContains('Murid Tahfizh', $tahfizhNames);
        $this->assertNotContains('Murid Reguler', $tahfizhNames);

        $regulerSpreadsheet = $this->downloadMine($this->teacherUser, 'reguler');
        $regulerNames = collect($regulerSpreadsheet->getSheetByName('Term-Indeks')->toArray())->skip(1)->pluck(3);
        $this->assertContains('Murid Reguler', $regulerNames);
        $this->assertNotContains('Murid Tahfizh', $regulerNames);
    }

    #[Test]
    public function grade_ten_students_show_ummi_jilid_and_halaman_instead_of_surah_and_ayat(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classX = ClassRoom::create(['program_id' => $program->id, 'name' => 'X E2', 'level' => 'X', 'tahfizh_days' => [1, 2, 3, 4, 5]]);

        $this->student->update([
            'class_room_id' => $classX->id,
            'teacher_id' => $this->teacherProfile->id,
            'tahfizh_level' => 'ummi',
            'name' => 'Murid Ummi Kelas X',
        ]);

        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'ummi_jilid' => 'Jilid 3',
            'halaman_peraga' => 'Hal. 10 - 15',
            'halaman_buku' => 'Hal. 15 - 20',
            'target_date' => '2026-07-01',
            'status' => 'active',
        ]);

        UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tatap_muka' => 1,
            'tanggal' => '2026-07-06',
            'ummi_jilid' => 'Jilid 3',
            'ummi_halaman' => 'Hal. 12 - 15',
            'materi' => 'Jilid 3 halaman 12-15',
            'nilai' => 'A',
        ]);

        $spreadsheet = $this->downloadMine($this->teacherUser, 'reguler');

        $termRows = collect($spreadsheet->getSheetByName('Term-Indeks')->toArray())->skip(1);
        $studentRow = $termRows->firstWhere(3, 'Murid Ummi Kelas X');
        $this->assertNotNull($studentRow);
        // Kolom: Kelas(0) Halaqah(1) No(2) Nama(3) Level(4) Target Surah(5) Target Ayat(6) Capaian Surah(7) Capaian Ayat(8)
        $this->assertSame('Jilid 3', $studentRow[5]);
        $this->assertStringContainsString('Peraga: Hal. 10 - 15', $studentRow[6]);
        $this->assertSame('Jilid 3', $studentRow[7]);
        $this->assertStringContainsString('Hal. 12 - 15', $studentRow[8]);

        $setoranRows = collect($spreadsheet->getSheetByName('Setoran')->toArray())->skip(1);
        // Kolom Setoran: Kelas(0) Halaqah(1) Bulan(2) Nama(3) Level(4) Pekan(5) Hari(6) Surah/Keterangan(7)
        $ummiRow = $setoranRows->first(fn ($r) => $r[3] === 'Murid Ummi Kelas X' && $r[7] === 'Jilid 3');
        $this->assertNotNull($ummiRow);
    }

    #[Test]
    public function returns_a_friendly_404_when_the_teacher_has_no_classes_in_that_program(): void
    {
        $roleTeacher = Role::where('name', 'teacher')->firstOrFail();
        $freshTeacherUser = User::factory()->create(['role_id' => $roleTeacher->id, 'name' => 'Guru Baru', 'status' => 'active']);
        TeacherProfile::create(['user_id' => $freshTeacherUser->id, 'employee_number' => 'TEST-GURU-930']);

        $this->actingAs($freshTeacherUser)
            ->get(route('reports.quarterly.export.mine', ['program' => 'reguler']))
            ->assertStatus(404);
    }
}
