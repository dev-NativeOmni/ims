<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\Surah;
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

        $rows = $spreadsheet->getSheetByName('Term-Indeks')->toArray();
        $cells = $this->flatten($rows);

        $this->assertContains('Murid Kelas A', $cells);
        $this->assertContains('Murid Kelas B', $cells);
        $this->assertNotContains('Murid Guru Lain', $cells);
        $this->assertTrue(collect($cells)->contains(fn ($v) => str_contains((string) $v, 'XI F2')));
        $this->assertTrue(collect($cells)->contains(fn ($v) => str_contains((string) $v, 'XII F3')));
    }

    private function flatten(array $rows): array
    {
        return collect($rows)->flatten()->all();
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
        $tahfizhNames = $this->flatten($tahfizhSpreadsheet->getSheetByName('Term-Indeks')->toArray());
        $this->assertContains('Murid Tahfizh', $tahfizhNames);
        $this->assertNotContains('Murid Reguler', $tahfizhNames);

        $regulerSpreadsheet = $this->downloadMine($this->teacherUser, 'reguler');
        $regulerNames = $this->flatten($regulerSpreadsheet->getSheetByName('Term-Indeks')->toArray());
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

        // Setoran Ummi terakhir (Jilid/Halaman + hafalan surah) dipakai sebagai capaian.
        $anNaba = Surah::firstOrCreate(
            ['number' => 78],
            ['name_ar' => 'النبأ', 'name_latin' => 'An-Naba', 'total_ayah' => 40, 'juz_start' => 30, 'juz_end' => 30]
        );
        $latest = UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tatap_muka' => 2,
            'tanggal' => '2026-08-10',
            'ummi_jilid' => 'Jilid 4',
            'ummi_halaman' => 'Hal. 3 - 6',
            'nilai' => 'B',
        ]);
        $latest->surahs()->create(['surah_id' => $anNaba->id, 'hafalan_ayah' => '1-12']);

        $spreadsheet = $this->downloadMine($this->teacherUser, 'reguler');

        // Kelas ber-murid Ummi: [No, Nama, Level, Awal Triwulan, T.Jilid, T.Halaman, T.Surah, T.Ayat, C.Jilid, C.Halaman, C.Surah, C.Ayat, ...]
        $termRows = $spreadsheet->getSheetByName('Term-Indeks')->toArray();
        $header = collect($termRows)->first(fn ($r) => ($r[4] ?? null) === 'Target Jilid');
        $this->assertNotNull($header);
        $this->assertSame(['Target Jilid', 'Target Halaman', 'Target Surah', 'Target Ayat', 'Capaian Jilid', 'Capaian Halaman', 'Capaian Surah', 'Capaian Ayat'], array_slice($header, 4, 8));

        $studentRow = collect($termRows)->firstWhere(1, 'Murid Ummi Kelas X');
        $this->assertNotNull($studentRow);
        $this->assertSame('-', $studentRow[3], 'Murid Ummi tidak punya titik awal triwulan (baris dipatok lewat jilid/halaman).');
        $this->assertSame('Jilid 3', $studentRow[4]);
        $this->assertSame('20', $studentRow[5], 'Halaman Peraga tidak dipakai; cukup halaman akhir buku.');
        $this->assertSame('-', $studentRow[6]);
        $this->assertSame('-', $studentRow[7]);
        $this->assertSame('Jilid 4', $studentRow[8], 'Capaian = Jilid setoran Ummi terakhir.');
        $this->assertSame('6', $studentRow[9], 'Capaian = halaman akhir setoran Ummi terakhir.');
        $this->assertSame('An-Naba', $studentRow[10]);
        $this->assertSame('12', $studentRow[11], 'Capaian = ayat akhir hafalan terakhir.');

        // Setoran: Pekan 1 (1-7 Juli) memuat setoran Ummi 6 Juli -> selnya berisi "Jilid 3".
        $setoranRows = $spreadsheet->getSheetByName('Setoran')->toArray();
        $ummiRow = collect($setoranRows)->firstWhere(1, 'Murid Ummi Kelas X');
        $this->assertNotNull($ummiRow);
        $this->assertTrue(collect($ummiRow)->contains(fn ($v) => str_contains((string) $v, 'Jilid 3')));
    }

    #[Test]
    public function on_screen_term_tab_shows_jilid_halaman_columns_only_for_ummi_classes(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classX = ClassRoom::create(['program_id' => $program->id, 'name' => 'X E2', 'level' => 'X', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        $this->student->update(['class_room_id' => $classX->id, 'teacher_id' => $this->teacherProfile->id, 'tahfizh_level' => 'ummi']);

        UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tatap_muka' => 1,
            'tanggal' => '2026-07-06',
            'ummi_jilid' => 'Jilid 5',
            'ummi_halaman' => '8',
            'nilai' => 'A',
        ]);

        $query = ['academic_year' => '2026/2027', 'term' => '1', 'class_room_id' => $classX->id];
        $this->actingAs($this->admin)->get(route('reports.quarterly', $query))
            ->assertOk()
            ->assertSee('>Jilid</th>', false)
            ->assertSee('>Halaman</th>', false)
            ->assertSee('Jilid 5');

        $this->student->update(['tahfizh_level' => 'reguler']);
        $this->actingAs($this->admin)->get(route('reports.quarterly', $query))
            ->assertOk()
            ->assertDontSee('>Halaman</th>', false);
    }

    #[Test]
    public function monthly_tab_shows_ummi_jilid_halaman_columns_scoped_to_that_months_own_target(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classX = ClassRoom::create(['program_id' => $program->id, 'name' => 'X E3', 'level' => 'X', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        $this->student->update(['class_room_id' => $classX->id, 'teacher_id' => $this->teacherProfile->id, 'tahfizh_level' => 'ummi']);

        // Target Juli beda dari target September -- tab bulanan (Grafik Akhir Bulan) harus
        // menampilkan target BULAN ITU sendiri, bukan target terakhir se-triwulan.
        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'ummi_jilid' => 'Jilid 1',
            'halaman_buku' => '10',
            'target_date' => '2026-07-05',
            'status' => 'active',
        ]);
        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'ummi_jilid' => 'Jilid 5',
            'halaman_buku' => '50',
            'target_date' => '2026-09-05',
            'status' => 'active',
        ]);

        $query = ['academic_year' => '2026/2027', 'term' => '1', 'class_room_id' => $classX->id];
        $response = $this->actingAs($this->admin)->get(route('reports.quarterly', $query));
        $response->assertOk();
        $response->assertSee('Target Jilid');
        $response->assertSee('Capaian Jilid');

        $halaqah = collect($response->viewData('halaqahData'))->first();

        $julyRow = collect($halaqah['monthly']['07']['reguler_records'])->firstWhere('student_id', $this->student->id);
        $this->assertSame('Jilid 1', $julyRow['ummi']['target_jilid'], 'Juli harus pakai target Juli sendiri.');
        $this->assertSame('10', $julyRow['ummi']['target_halaman']);

        $septRow = collect($halaqah['monthly']['09']['reguler_records'])->firstWhere('student_id', $this->student->id);
        $this->assertSame('Jilid 5', $septRow['ummi']['target_jilid'], 'September harus pakai target September sendiri.');
        $this->assertSame('50', $septRow['ummi']['target_halaman']);

        // Agustus tidak punya target sendiri -> tidak ikut menampilkan target Juli/September.
        $augRow = collect($halaqah['monthly']['08']['reguler_records'])->firstWhere('student_id', $this->student->id);
        $this->assertSame('-', $augRow['ummi']['target_jilid']);

        // Term/Indeks (triwulan) tetap tidak berubah: target terakhir se-triwulan (September).
        $termRow = collect($halaqah['term_records'])->firstWhere('student_id', $this->student->id);
        $this->assertSame('Jilid 5', $termRow['ummi']['target_jilid']);
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
