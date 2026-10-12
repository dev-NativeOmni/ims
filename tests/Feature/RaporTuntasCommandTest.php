<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\StudentReport;
use App\Models\UmmiRecord;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * tad:rapor-tuntas: status Tahfizh rapor per murid sama dengan rapor cetak (terkunci = isi beku).
 */
class RaporTuntasCommandTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 09:00');
        $this->setUpHafizPlusData();
        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $class = ClassRoom::create(['program_id' => $program->id, 'name' => 'X E2', 'level' => 'X']);
        $this->student->update(['class_room_id' => $class->id, 'tahfizh_level' => 'ummi']);
        HafalanTarget::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'ummi_jilid' => 'Jilid 2', 'halaman_buku' => '30', 'target_date' => '2026-09-25', 'status' => 'active']);
        UmmiRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'tanggal' => '2026-09-20', 'tatap_muka' => 1, 'ummi_jilid' => 'Jilid 2', 'ummi_halaman' => '25']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function it_lists_students_whose_rapor_is_not_tuntas(): void
    {
        $this->artisan('tad:rapor-tuntas', ['--tahun' => '2026/2027', '--term' => 1])
            ->expectsOutputToContain('Jilid 2 hal. 25')
            ->expectsOutputToContain('Jumlah: 1 dari 1 murid Tidak Tuntas')
            ->assertSuccessful();
    }

    #[Test]
    public function locked_rapor_uses_the_frozen_status(): void
    {
        StudentReport::create([
            'student_id' => $this->student->id, 'academic_year' => '2026/2027', 'semester' => 1, 'term' => 1, 'locked_at' => now(),
            'snapshot' => ['tahfizh' => ['layout' => 'ummi', 'completed' => true, 'target' => [], 'capaian' => []]],
        ]);

        $this->artisan('tad:rapor-tuntas', ['--tahun' => '2026/2027', '--term' => 1])
            ->expectsOutputToContain('Jumlah: 0 dari 1 murid Tidak Tuntas')
            ->assertSuccessful();
    }
}
