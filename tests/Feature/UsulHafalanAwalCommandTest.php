<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\StudentPriorHafalan;
use App\Models\Surah;
use App\Services\HafalanProgressService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class UsulHafalanAwalCommandTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setor(int $surah, int $start, int $end, string $date): void
    {
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $header->surahs()->create(['surah_id' => Surah::where('number', $surah)->value('id'), 'ayah_start' => $start, 'ayah_end' => $end, 'status' => 'passed', 'submission_type' => 'new']);
    }

    #[Test]
    public function student_starting_mid_juz_gets_the_earlier_part_proposed_and_saved(): void
    {
        Carbon::setTestNow('2026-06-01');
        $this->setUpHafizPlusData();
        foreach ([15 => ['Al-Hijr', 99], 16 => ['An-Nahl', 128]] as $n => [$name, $ayahs]) {
            Surah::firstOrCreate(['number' => $n], ['name_ar' => $name, 'name_latin' => $name, 'total_ayah' => $ayahs, 'juz_start' => 14, 'juz_end' => 14]);
        }
        $class = ClassRoom::create(['program_id' => $this->student->classRoom->program_id, 'name' => 'XI F1', 'level' => '']);
        $this->student->update(['class_room_id' => $class->id, 'tahfizh_level' => 'akselerasi']);
        Carbon::setTestNow('2026-10-08');

        $this->setor(16, 25, 40, '2026-07-14');
        $this->setor(15, 1, 99, '2026-08-13'); // kemungkinan muraja'ah hafalan lama
        $this->setor(16, 1, 24, '2026-08-21');

        $this->artisan('tad:usul-hafalan-awal', ['--term' => 1, '--tahun' => '2026/2027'])
            ->expectsOutputToContain('Al-Hijr 1-99; An-Nahl 1-24')
            ->expectsOutputToContain('Mode tampil saja')
            ->assertSuccessful();
        $this->assertSame(0, StudentPriorHafalan::count(), 'Tampil saja: belum disimpan.');

        $this->artisan('tad:usul-hafalan-awal', ['--term' => 1, '--tahun' => '2026/2027', '--murid' => ['Santri'], '--simpan' => true, '--force' => true])
            ->assertSuccessful();
        $this->assertSame(2, StudentPriorHafalan::count());

        $progress = app(HafalanProgressService::class);
        $details = collect($progress->passedLineDetails($progress->records($this->student->fresh()), Carbon::parse('2026-07-01'), Carbon::parse('2026-09-30')));
        $this->assertSame(['new', 'repeat', 'repeat'], $details->pluck('kind')->all(), 'Setoran Agustus kini ulangan.');

        $this->artisan('tad:usul-hafalan-awal', ['--term' => 1, '--tahun' => '2026/2027'])
            ->expectsOutputToContain('Tidak ada murid yang mulai di tengah juz')
            ->assertSuccessful();
    }
}
