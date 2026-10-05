<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\Student;
use App\Models\TahfizhExam;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class TahfizhExamTermFilterTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private Student $otherStudent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        Carbon::setTestNow('2026-08-15');

        $classRoom = ClassRoom::create([
            'program_id' => Program::first()->id,
            'name' => 'Kelas XII F1',
            'level' => 'XII',
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'name' => 'Murid Sudah Ujian']);
        $this->otherStudent = Student::create([
            'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id,
            'name' => 'Murid Belum Ujian',
            'student_number' => 'TEST-SNT-777',
            'gender' => 'male',
            'birth_date' => '2009-01-01',
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function exam(Student $student, string $date): void
    {
        TahfizhExam::create([
            'student_id' => $student->id,
            'teacher_id' => $this->teacherProfile->id,
            'juz' => 30,
            'total_score' => 40,
            'exam_date' => $date,
        ]);
    }

    #[Test]
    public function belum_filter_lists_only_students_without_an_exam_in_the_current_term(): void
    {
        $this->exam($this->student, '2026-09-30'); // hari terakhir term Jul-Sep, batas inklusif
        $this->exam($this->otherStudent, '2026-06-20'); // term sebelumnya, tidak dihitung

        $response = $this->actingAs($this->admin)->get(route('tahfizh-exams.index', ['exam_status' => 'belum']));

        $response->assertStatus(200);
        $response->assertSee('Juli - September 2026');
        $this->assertSame(['Murid Belum Ujian'], $response->viewData('pendingStudents')->pluck('name')->all());
    }

    #[Test]
    public function sudah_filter_lists_only_exams_in_the_current_term(): void
    {
        $this->exam($this->student, '2026-07-01');
        $this->exam($this->otherStudent, '2026-06-20');

        $response = $this->actingAs($this->admin)->get(route('tahfizh-exams.index', ['exam_status' => 'sudah']));

        $response->assertStatus(200);
        $exams = $response->viewData('exams');
        $this->assertCount(1, $exams);
        $this->assertSame($this->student->id, $exams->first()->student_id);
    }

    #[Test]
    public function belum_filter_respects_class_filter_and_teacher_scope(): void
    {
        $otherClass = ClassRoom::create(['program_id' => Program::first()->id, 'name' => 'Kelas XII F2', 'level' => 'XII']);
        $this->otherStudent->update(['class_room_id' => $otherClass->id]);

        $byClass = $this->actingAs($this->admin)->get(route('tahfizh-exams.index', [
            'exam_status' => 'belum',
            'class_room_id' => $otherClass->id,
        ]));
        $this->assertSame(['Murid Belum Ujian'], $byClass->viewData('pendingStudents')->pluck('name')->all());

        // Guru hanya melihat muridnya sendiri: murid guru lain tidak ikut.
        $otherTeacherStudent = Student::create([
            'class_room_id' => $otherClass->id,
            'teacher_id' => null,
            'name' => 'Murid Guru Lain',
            'student_number' => 'TEST-SNT-778',
            'gender' => 'male',
            'birth_date' => '2009-01-01',
            'status' => 'active',
        ]);
        $asTeacher = $this->actingAs($this->teacherUser)->get(route('tahfizh-exams.index', ['exam_status' => 'belum']));
        $names = $asTeacher->viewData('pendingStudents')->pluck('name')->all();
        $this->assertNotContains($otherTeacherStudent->name, $names);
        $this->assertContains('Murid Belum Ujian', $names);
    }

    #[Test]
    public function default_index_still_lists_all_exams_and_create_preselects_student(): void
    {
        $this->exam($this->student, '2026-06-20');

        $this->actingAs($this->admin)->get(route('tahfizh-exams.index'))
            ->assertStatus(200)
            ->assertSee('Murid Sudah Ujian');

        $this->actingAs($this->admin)->get(route('tahfizh-exams.create', ['student_id' => $this->otherStudent->id]))
            ->assertStatus(200)
            ->assertSee("selectedStudent: '{$this->otherStudent->id}'", false);
    }

    #[Test]
    public function triwulan_filter_limits_the_exam_history(): void
    {
        Carbon::setTestNow('2026-11-10');
        $this->exam($this->student, '2026-08-01'); // Triwulan 1
        $this->exam($this->otherStudent, '2026-10-01'); // Triwulan 2, hari pertama

        $term1 = $this->actingAs($this->admin)->get(route('tahfizh-exams.index', ['triwulan' => '2026-1']));
        $term1->assertOk()->assertSee('Juli - September 2026');
        $this->assertSame([$this->student->id], $term1->viewData('exams')->pluck('student_id')->all());

        $term2 = $this->actingAs($this->admin)->get(route('tahfizh-exams.index', ['triwulan' => '2026-2']));
        $this->assertSame([$this->otherStudent->id], $term2->viewData('exams')->pluck('student_id')->all());

        // Tidak valid = semua riwayat.
        $all = $this->actingAs($this->admin)->get(route('tahfizh-exams.index', ['triwulan' => '2025-9']));
        $this->assertCount(2, $all->viewData('exams'));
        $this->assertSame('', $all->viewData('selectedTriwulan'));
    }

    #[Test]
    public function belum_filter_follows_the_chosen_triwulan(): void
    {
        Carbon::setTestNow('2026-11-10');
        $this->exam($this->student, '2026-08-01'); // sudah ujian di Triwulan 1 saja

        $term1 = $this->actingAs($this->admin)->get(route('tahfizh-exams.index', ['exam_status' => 'belum', 'triwulan' => '2026-1']));
        $this->assertSame(['Murid Belum Ujian'], $term1->viewData('pendingStudents')->pluck('name')->all());

        // Tanpa pilihan triwulan: triwulan berjalan (Okt - Des), keduanya belum ujian.
        $current = $this->actingAs($this->admin)->get(route('tahfizh-exams.index', ['exam_status' => 'belum']));
        $current->assertSee('Oktober - Desember 2026');
        $this->assertSame('2026-2', $current->viewData('selectedTriwulan'));
        $this->assertCount(2, $current->viewData('pendingStudents'));
    }

    #[Test]
    public function triwulan_options_stop_at_the_current_triwulan(): void
    {
        Carbon::setTestNow('2026-11-10');

        $options = $this->actingAs($this->admin)->get(route('tahfizh-exams.index'))->viewData('triwulanOptions');

        $this->assertSame(['2026-2', '2026-1'], array_keys($options['2026/2027']));
    }
}
