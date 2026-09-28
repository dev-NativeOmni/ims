<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\UmmiRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Riwayat Setoran: filter guru pengampu (untuk admin/super admin) di tab Reguler dan Ummi.
 */
class HafalanRecordTeacherFilterTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private TeacherProfile $otherTeacher;

    private Student $otherStudent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        $otherUser = User::factory()->create([
            'role_id' => Role::where('name', 'teacher')->value('id'), 'name' => 'Guru Kedua', 'status' => 'active',
        ]);
        $this->otherTeacher = TeacherProfile::create(['user_id' => $otherUser->id, 'employee_number' => 'TEST-GURU-002']);
        $this->otherStudent = Student::create([
            'class_room_id' => $this->student->class_room_id, 'teacher_id' => $this->otherTeacher->id, 'name' => 'Murid Guru Kedua',
            'student_number' => 'TEST-SNT-002', 'gender' => 'male', 'birth_date' => '2010-01-01', 'status' => 'active',
        ]);
    }

    private function record(Student $student): void
    {
        $record = HafalanRecord::create(['student_id' => $student->id, 'teacher_id' => $student->teacher_id, 'submitted_at' => now()]);
        $record->surahs()->create([
            'surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 7, 'submission_type' => 'new', 'status' => 'passed',
        ]);
    }

    #[Test]
    public function admin_can_filter_regular_records_by_the_students_teacher(): void
    {
        $this->record($this->student);
        $this->record($this->otherStudent);

        $all = $this->actingAs($this->admin)->get(route('hafalan-records.index'));
        $all->assertOk()->assertSee('Semua Guru Pengampu')->assertSee('Guru Kedua');
        $this->assertSame(2, $all->viewData('hafalanRecords')->total());

        $filtered = $this->actingAs($this->admin)->get(route('hafalan-records.index', ['teacher_id' => $this->otherTeacher->id]));
        $this->assertSame([$this->otherStudent->id], $filtered->viewData('hafalanRecords')->pluck('student_id')->all());
    }

    #[Test]
    public function admin_can_filter_ummi_records_by_the_students_teacher(): void
    {
        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $classX = ClassRoom::create(['program_id' => $program->id, 'name' => 'X E1', 'level' => 'X']);
        foreach ([$this->student, $this->otherStudent] as $student) {
            $student->update(['class_room_id' => $classX->id, 'tahfizh_level' => 'ummi']);
            UmmiRecord::create([
                'student_id' => $student->id, 'teacher_id' => $student->teacher_id,
                'tanggal' => now()->toDateString(), 'ummi_jilid' => 'Jilid 1', 'ummi_halaman' => '5', 'tatap_muka' => 1,
            ]);
        }

        $filtered = $this->actingAs($this->superAdmin)->get(route('hafalan-records.index', [
            'category' => 'ummi', 'teacher_id' => $this->teacherProfile->id,
        ]));

        $filtered->assertOk()->assertSee('Semua Guru Pengampu');
        $this->assertSame([$this->student->id], $filtered->viewData('hafalanRecords')->pluck('student_id')->all());
    }

    #[Test]
    public function teachers_do_not_see_the_filter_and_cannot_use_it_to_see_other_halaqah(): void
    {
        $this->record($this->student);
        $this->record($this->otherStudent);

        $response = $this->actingAs($this->teacherUser)->get(route('hafalan-records.index', ['teacher_id' => $this->otherTeacher->id]));

        $response->assertOk()->assertDontSee('Semua Guru Pengampu');
        $this->assertSame([$this->student->id], $response->viewData('hafalanRecords')->pluck('student_id')->all());
    }
}
