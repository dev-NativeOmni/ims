<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class RincianBarisCommandTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setor(string $date, int $start, int $end, ?float $baris = null): void
    {
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $header->surahs()->create(['surah_id' => $this->surah->id, 'ayah_start' => $start, 'ayah_end' => $end, 'status' => 'passed', 'submission_type' => 'new', 'baris' => $baris]);
    }

    #[Test]
    public function it_splits_term_lines_into_new_repeated_and_duplicate(): void
    {
        $this->setUpHafizPlusData();
        Carbon::setTestNow('2026-10-08');
        $this->setor('2026-06-20', 1, 3, 3);   // sebelum triwulan: sudah lulus
        $this->setor('2026-07-10', 1, 3, 3);   // ulang
        $this->setor('2026-07-11', 4, 7, 4);   // baru
        $this->setor('2026-07-11', 4, 7, 4);   // ganda

        $this->artisan('tad:rincian-baris', ['murid' => $this->student->name, '--tahun' => '2026/2027', '--term' => 1])
            ->expectsOutputToContain('ULANG (ayat sudah pernah lulus)')
            ->expectsOutputToContain('GANDA (sama persis di tanggal ini)')
            ->expectsOutputToContain('Total baris semua setoran lulus: 11 · dihitung capaian (ayat baru): 4 · ulangan: 3 · ganda: 4')
            ->expectsOutputToContain('Rapor: capaian 4 /')
            ->assertSuccessful();
    }

    #[Test]
    public function diinput_option_shows_when_each_setoran_was_entered(): void
    {
        $this->setUpHafizPlusData();
        Carbon::setTestNow('2026-10-08 09:15');
        $this->setor('2026-07-11', 4, 7, 4);

        $this->artisan('tad:rincian-baris', ['murid' => $this->student->name, '--tahun' => '2026/2027', '--term' => 1, '--diinput' => true])
            ->expectsOutputToContain('08/10 09:15')
            ->assertSuccessful();
    }

    #[Test]
    public function rekap_lists_grade_11_and_12_students_with_new_versus_all_lines(): void
    {
        Carbon::setTestNow('2026-06-01'); // naik ke XII F3 sebelum Triwulan 1 (riwayat kelas)
        $this->setUpHafizPlusData();
        $class = ClassRoom::create(['program_id' => $this->student->classRoom->program_id, 'name' => 'XII F3', 'level' => '']);
        $this->student->update(['class_room_id' => $class->id, 'tahfizh_level' => 'reguler']);
        Carbon::setTestNow('2026-10-08');
        $this->setor('2026-06-20', 1, 3, 3);
        $this->setor('2026-07-10', 1, 3, 3); // ulang
        $this->setor('2026-07-11', 4, 7, 4); // baru
        $this->setor('2026-07-11', 4, 7, 4); // ganda

        $this->artisan('tad:rekap-baris', ['--tahun' => '2026/2027', '--term' => 1])
            ->expectsOutputToContain('| XII F3 | '.$this->student->name.' | reguler |')
            ->expectsOutputToContain('Murid diperiksa: 1 · belum tuntas: 1 · status berubah: 0 · ada ulangan: 1 · ada setoran ganda: 1')
            ->assertSuccessful();

        $this->artisan('tad:rekap-baris', ['--tahun' => '2026/2027', '--term' => 1, '--belum' => true])
            ->expectsOutputToContain('| XII F3 | '.$this->student->name.' | reguler |')
            ->assertSuccessful();

        $this->artisan('tad:rekap-baris', ['--term' => 1, '--tahun' => '2026/2027', '--kelas' => 'XI Z9'])->assertFailed();
    }

    #[Test]
    public function rekap_posisi_lists_students_past_the_target_ayah_but_short_on_lines(): void
    {
        Carbon::setTestNow('2026-06-01');
        $this->setUpHafizPlusData();
        $class = ClassRoom::create(['program_id' => $this->student->classRoom->program_id, 'name' => 'XII F2', 'level' => '']);
        $this->student->update(['class_room_id' => $class->id, 'tahfizh_level' => 'reguler']);
        Carbon::setTestNow('2026-10-08');
        HafalanTarget::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'surah_id' => $this->surah->id,
            'ayah' => 5, 'target_date' => '2026-08-31', 'status' => 'active',
        ]);
        $this->setor('2026-08-10', 1, 7, 3); // sampai Al-Fatihah 7: lewat target ayat 5, tapi baris jauh di bawah target

        $this->artisan('tad:rekap-baris', ['--tahun' => '2026/2027', '--term' => 1, '--posisi' => true])
            ->expectsOutputToContain('| XII F2 | '.$this->student->name.' | reguler | Al-Fatihah 5 | Al-Fatihah 7  | ya ')
            ->expectsOutputToContain('1 murid: Capaian Akhir sudah sama/melewati target')
            ->assertSuccessful();
    }
}
