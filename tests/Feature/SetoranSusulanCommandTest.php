<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
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
 * tad:setoran-susulan: ayat dari Capaian Akhir sampai target dicatat sebagai setoran susulan
 * (lalu tad:penyesuaian-baris menggenapkan baris), dengan --pulihkan.
 */
class SetoranSusulanCommandTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private Surah $baqarah;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-15 09:00');
        $this->setUpHafizPlusData();
        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $class = ClassRoom::create(['program_id' => $program->id, 'name' => 'XII F4', 'level' => 'XII']);
        $this->student->update(['class_room_id' => $class->id, 'tahfizh_level' => 'reguler']);
        $this->baqarah = Surah::firstOrCreate(['number' => 2], ['name_ar' => 'البقرة', 'name_latin' => 'Al-Baqarah', 'total_ayah' => 286, 'juz_start' => 1, 'juz_end' => 3]);
        HafalanTarget::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'surah_id' => $this->baqarah->id, 'ayah' => 135, 'target_date' => '2026-09-25', 'status' => 'active']);
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => '2026-07-14']);
        $header->surahs()->create(['surah_id' => $this->baqarah->id, 'ayah_start' => 120, 'ayah_end' => 131, 'status' => 'passed', 'submission_type' => 'new', 'baris' => 10]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        File::deleteDirectory(storage_path('app/backups/setoran-susulan'));
        File::deleteDirectory(storage_path('app/backups/penyesuaian-baris'));
        parent::tearDown();
    }

    #[Test]
    public function missing_ayat_up_to_the_target_are_recorded_and_the_term_becomes_tuntas(): void
    {
        $args = ['--tahun' => '2026/2027', '--term' => 1];
        $this->artisan('tad:setoran-susulan', $args)->expectsOutputToContain('Al-Baqarah 132-135')->expectsOutputToContain('Mode tampil saja')->assertSuccessful();
        $this->assertSame(1, HafalanRecord::count());

        $this->artisan('tad:setoran-susulan', $args + ['--simpan' => true, '--force' => true])->assertSuccessful();
        $susulan = HafalanRecord::query()->latest('id')->first();
        $this->assertStringContainsString('Setoran susulan', $susulan->notes);
        $this->assertSame([[132, 135, 'passed']], $susulan->surahs->map(fn ($s) => [$s->ayah_start, $s->ayah_end, $s->status])->all());
        $this->assertSame('2026-09', $susulan->submitted_at->format('Y-m'));

        // Posisi sudah sampai target: baris digenapkan penyesuaian, rapor Tuntas.
        $this->artisan('tad:penyesuaian-baris', $args + ['--simpan' => true, '--force' => true])->assertSuccessful();
        $this->artisan('tad:rapor-tuntas', $args)->expectsOutputToContain('Jumlah: 0 dari 1 murid Tidak Tuntas')->assertSuccessful();

        $backup = collect(File::files(storage_path('app/backups/setoran-susulan')))->first()->getFilename();
        $this->artisan('tad:setoran-susulan', ['--pulihkan' => $backup, '--force' => true])->assertSuccessful();
        $this->assertSame(1, HafalanRecord::count());
    }
}
