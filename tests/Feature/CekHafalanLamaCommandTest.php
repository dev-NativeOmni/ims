<?php

namespace Tests\Feature;

use App\Models\HafalanRecord;
use App\Models\StudentPriorHafalan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * tad:cek-hafalan-lama: setoran yang diinput sesudah tanda hafalan lama dibuat & tertimpa tanda itu.
 */
class CekHafalanLamaCommandTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setor(string $date, int $start, int $end): void
    {
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $header->surahs()->create(['surah_id' => $this->surah->id, 'ayah_start' => $start, 'ayah_end' => $end, 'status' => 'passed', 'submission_type' => 'new']);
    }

    #[Test]
    public function setoran_entered_after_the_prior_mark_is_released_from_it(): void
    {
        $this->setUpHafizPlusData();
        Carbon::setTestNow('2026-09-01 08:00');
        $this->setor('2026-07-10', 1, 3);      // diinput sebelum tanda: ulangan wajar
        Carbon::setTestNow('2026-10-05 08:00');
        $prior = StudentPriorHafalan::create(['student_id' => $this->student->id, 'surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 10]);
        Carbon::setTestNow('2026-10-08 14:15');
        $this->setor('2026-07-23', 6, 8);      // guru melengkapi setoran awal triwulan

        $this->artisan('tad:cek-hafalan-lama')
            ->expectsOutputToContain('CURIGA: lepas ayat 6-8')
            ->assertSuccessful();
        $this->assertSame(1, StudentPriorHafalan::count(), 'Mode tampil saja tidak mengubah data.');

        $this->artisan('tad:cek-hafalan-lama', ['--perbaiki' => true, '--force' => true])->assertSuccessful();

        $this->assertNull(StudentPriorHafalan::find($prior->id));
        $this->assertSame([[1, 5], [9, 10]], StudentPriorHafalan::orderBy('ayah_start')->get()
            ->map(fn ($p) => [$p->ayah_start, $p->ayah_end])->all());

        // Cadangan otomatis bisa dipulihkan.
        $backup = collect(File::files(storage_path('app/backups/hafalan-lama')))->last()->getFilename();
        $this->artisan('tad:cek-hafalan-lama', ['--pulihkan' => $backup, '--force' => true])->assertSuccessful();
        $this->assertSame([[$prior->id, 1, 10]], StudentPriorHafalan::all()->map(fn ($p) => [$p->id, $p->ayah_start, $p->ayah_end])->all());
        File::delete(storage_path('app/backups/hafalan-lama/'.$backup));
    }

    #[Test]
    public function repeats_entered_before_the_prior_mark_are_left_alone(): void
    {
        $this->setUpHafizPlusData();
        Carbon::setTestNow('2026-09-01 08:00');
        $this->setor('2026-07-10', 1, 3);
        Carbon::setTestNow('2026-10-05 08:00');
        StudentPriorHafalan::create(['student_id' => $this->student->id, 'surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 10]);

        $this->artisan('tad:cek-hafalan-lama', ['--perbaiki' => true, '--force' => true])
            ->expectsOutputToContain('Tidak ada setoran yang tertimpa')
            ->assertSuccessful();
        $this->artisan('tad:cek-hafalan-lama', ['--semua' => true])
            ->expectsOutputToContain('ulangan wajar')
            ->assertSuccessful();
        $this->assertSame(1, StudentPriorHafalan::count());
    }
}
