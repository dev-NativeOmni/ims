<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Surah;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * tad:penyesuaian-baris: Program Reguler 11/12, posisi sudah sampai target tapi baris kurang ->
 * baris setoran pertama Juli ditambah sebanyak kekurangan (dengan cadangan & --pulihkan).
 */
class PenyesuaianBarisCommandTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private Surah $baqarah;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 09:00');
        $this->setUpHafizPlusData();
        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $class = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI F2', 'level' => 'XI']);
        $this->student->update(['class_room_id' => $class->id, 'tahfizh_level' => 'reguler']);
        $this->baqarah = Surah::firstOrCreate(['number' => 2], ['name_ar' => 'البقرة', 'name_latin' => 'Al-Baqarah', 'total_ayah' => 286, 'juz_start' => 1, 'juz_end' => 3]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        File::deleteDirectory(storage_path('app/backups/penyesuaian-baris'));
        parent::tearDown();
    }

    private function setor(string $date, int $start, int $end, float $baris): int
    {
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);

        return $header->surahs()->create(['surah_id' => $this->baqarah->id, 'ayah_start' => $start, 'ayah_end' => $end, 'status' => 'passed', 'submission_type' => 'new', 'baris' => $baris])->id;
    }

    private function target(int $ayah): void
    {
        HafalanTarget::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'surah_id' => $this->baqarah->id, 'ayah' => $ayah, 'target_date' => '2026-09-25', 'status' => 'active']);
    }

    #[Test]
    public function student_who_reached_the_target_position_gets_the_shortfall_added_to_the_first_july_setoran(): void
    {
        $this->target(20);
        $july = $this->setor('2026-07-14', 1, 5, 5);
        $this->setor('2026-08-10', 6, 20, 10);

        $this->artisan('tad:penyesuaian-baris', ['--tahun' => '2026/2027', '--term' => 1])
            ->expectsOutputToContain('Mode tampil saja')->assertSuccessful();
        $this->assertSame(5.0, (float) HafalanRecordSurah::find($july)->baris);

        $this->artisan('tad:penyesuaian-baris', ['--tahun' => '2026/2027', '--term' => 1, '--simpan' => true, '--force' => true])->assertSuccessful();
        $this->assertGreaterThan(5.0, (float) HafalanRecordSurah::find($july)->baris);
        $this->artisan('tad:rapor-tuntas', ['--tahun' => '2026/2027', '--term' => 1])
            ->expectsOutputToContain('Jumlah: 0 dari 1 murid Tidak Tuntas')->assertSuccessful();

        $backup = basename(collect(File::files(storage_path('app/backups/penyesuaian-baris')))->first()->getFilename());
        $this->artisan('tad:penyesuaian-baris', ['--pulihkan' => $backup, '--force' => true])->assertSuccessful();
        $this->assertSame(5.0, (float) HafalanRecordSurah::find($july)->baris);
    }

    #[Test]
    public function student_whose_position_is_short_of_the_target_is_left_alone(): void
    {
        $this->target(100);
        $july = $this->setor('2026-07-14', 1, 5, 5);

        $this->artisan('tad:penyesuaian-baris', ['--tahun' => '2026/2027', '--term' => 1, '--simpan' => true, '--force' => true])
            ->expectsOutputToContain('capaian akhir belum sampai target surah')
            ->expectsOutputToContain('Tidak ada murid yang perlu disesuaikan')
            ->assertSuccessful();
        $this->assertSame(5.0, (float) HafalanRecordSurah::find($july)->baris);
    }
}
