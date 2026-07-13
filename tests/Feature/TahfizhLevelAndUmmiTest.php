<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\ParentProfile;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentReport;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\Surah;
use App\Models\UmmiRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfizhLevelAndUmmiTest extends TestCase
{
    use RefreshDatabase;

    private User $teacherUser;
    private TeacherProfile $teacher;
    private Student $studentUmmi;
    private Student $studentReguler;
    private Surah $surah;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $teacherRole = Role::firstOrCreate(['name' => 'teacher'], ['display_name' => 'Guru']);
        $studentRole = Role::firstOrCreate(['name' => 'student'], ['display_name' => 'Santri']);

        // Create teacher
        $this->teacherUser = User::factory()->create([
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);
        $this->teacher = TeacherProfile::create([
            'user_id' => $this->teacherUser->id,
            'employee_number' => 'T-001',
            'phone' => '081111111111',
        ]);

        // Create Programs & ClassRooms
        $program = Program::create(['name' => 'Tahfizh Reguler', 'status' => 'active']);
        
        $classX = ClassRoom::create([
            'name' => 'Kelas X-A',
            'level' => '10',
            'program_id' => $program->id,
        ]);
        
        $classXI = ClassRoom::create([
            'name' => 'Kelas XI-A',
            'level' => '11',
            'program_id' => $program->id,
        ]);

        // Create Students
        $userUmmi = User::factory()->create(['role_id' => $studentRole->id]);
        $this->studentUmmi = Student::create([
            'user_id' => $userUmmi->id,
            'class_room_id' => $classX->id,
            'teacher_id' => $this->teacher->id,
            'name' => 'Santri Kelas 10',
            'student_number' => 'S-001',
            'status' => 'active',
            'tahfizh_level' => 'ummi',
        ]);

        $userReguler = User::factory()->create(['role_id' => $studentRole->id]);
        $this->studentReguler = Student::create([
            'user_id' => $userReguler->id,
            'class_room_id' => $classXI->id,
            'teacher_id' => $this->teacher->id,
            'name' => 'Santri Kelas 11',
            'student_number' => 'S-002',
            'status' => 'active',
            'tahfizh_level' => 'reguler',
        ]);

        $this->surah = Surah::create([
            'number' => 1,
            'name_arabic' => 'الفاتحة',
            'name_latin' => 'Al-Fatihah',
            'total_ayah' => 7,
            'revelation_type' => 'meccan',
        ]);
    }

    public function test_auto_defaults_level_to_ummi_for_grade_10_classroom()
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $admin = User::factory()->create(['role_id' => $role->id, 'status' => 'active']);

        $program = Program::create(['name' => 'Program A', 'status' => 'active']);
        $classX = ClassRoom::create([
            'name' => 'X IPA 1',
            'level' => '10',
            'program_id' => $program->id,
        ]);

        $studentUser = User::factory()->create(['role_id' => Role::where('name', 'student')->first()->id]);

        $response = $this->actingAs($admin)->post(route('students.store'), [
            'user_id' => $studentUser->id,
            'class_room_id' => $classX->id,
            'teacher_id' => $this->teacher->id,
            'name' => 'Santri Baru X',
            'student_number' => 'S-X01',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('students.index'));
        $this->assertDatabaseHas('students', [
            'name' => 'Santri Baru X',
            'tahfizh_level' => 'ummi',
        ]);
    }

    public function test_teacher_can_save_ummi_record_for_ummi_student()
    {
        $response = $this->actingAs($this->teacherUser)->post(route('quick-inputs.ummi.store'), [
            'student_id' => $this->studentUmmi->id,
            'tatap_muka' => 5,
            'tanggal' => now()->toDateString(),
            'hafalan_surah_id' => $this->surah->id,
            'hafalan_ayah' => '1-5',
            'ummi_jilid' => 'Jilid 4',
            'ummi_halaman' => 'Halaman 12',
            'materi' => 'Mad Jaiz Munfashil',
            'nilai' => 'B+',
            'disimak_guru' => 'Ya',
            'disimak_ortu' => 'Tidak',
            'keterangan' => 'Salah 1 kali pada mad munfashil.',
        ]);

        $response->assertRedirect(route('quick-inputs.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ummi_records', [
            'student_id' => $this->studentUmmi->id,
            'tatap_muka' => 5,
            'ummi_jilid' => 'Jilid 4',
            'nilai' => 'B+',
        ]);
    }

    public function test_can_update_tahfizh_target_term_in_student_report()
    {
        $response = $this->actingAs($this->teacherUser)->post(route('digital-reports.update', $this->studentReguler), [
            'academic_year' => '2025/2026',
            'semester' => 1,
            'teacher_notes' => 'Catatan ulasan wali kelas.',
            'tahfizh_target_term' => 'Selesai Juz 29 di term ini',
            'status' => 'draft',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('student_reports', [
            'student_id' => $this->studentReguler->id,
            'academic_year' => '2025/2026',
            'semester' => 1,
            'tahfizh_target_term' => 'Selesai Juz 29 di term ini',
        ]);
    }

    public function test_report_computes_correct_latest_achievement_for_ummi()
    {
        // Add Ummi Record
        UmmiRecord::create([
            'student_id' => $this->studentUmmi->id,
            'teacher_id' => $this->teacher->id,
            'tatap_muka' => 10,
            'tanggal' => now(),
            'ummi_jilid' => 'Jilid 5',
            'ummi_halaman' => '15',
            'materi' => 'Materi UMMI',
            'nilai' => 'A',
            'disimak_guru' => 'Ya',
            'disimak_ortu' => 'Tidak',
            'keterangan' => 'Sangat lancar',
        ]);

        $response = $this->actingAs($this->teacherUser)->get(route('digital-reports.show', $this->studentUmmi));
        $response->assertStatus(200);

        // Verify variables are passed in view
        $response->assertViewHas('tahfizhLevelLabel', 'Metode Ummi');
        $response->assertViewHas('latestCapaianText', 'Jilid 5 Hal. 15 [Nilai: A]');
        $response->assertViewHas('latestCapaianNotes', 'Sangat lancar');
    }

    public function test_report_computes_correct_latest_achievement_for_reguler()
    {
        // Add passed Hafalan Record
        HafalanRecord::create([
            'student_id' => $this->studentReguler->id,
            'teacher_id' => $this->teacher->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'score' => 95,
            'status' => 'passed',
            'submitted_at' => now(),
            'notes' => 'Sangat baik',
        ]);

        $response = $this->actingAs($this->teacherUser)->get(route('digital-reports.show', $this->studentReguler));
        $response->assertStatus(200);

        $response->assertViewHas('tahfizhLevelLabel', 'Reguler');
        $response->assertViewHas('latestCapaianText', 'QS. Al-Fatihah (Ayat 1-7)');
        $response->assertViewHas('latestCapaianNotes', 'Sangat baik');
    }
}
