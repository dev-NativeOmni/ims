<?php

namespace Tests\Feature;

use App\Models\AdabMentorAssessment;
use App\Models\AdabRecord;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * tad:isi-adab-bulan: lengkapi kuisioner Adab harian (hari efektif kosong) & nilai pendamping satu
 * bulan; isian asli murid tidak ditimpa; --batalkan mengembalikan keadaan semula.
 */
class FillAdabMonthTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-02 09:00');
        $this->setUpHafizPlusData();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function fills_july_without_touching_real_answers_and_can_be_undone(): void
    {
        $effective = array_keys(Setting::getEffectiveDatesSet(2026, 7));
        AdabRecord::create(['student_id' => $this->student->id, 'assessment_date' => $effective[0], 'student_score' => 60, 'total_score' => 60, 'notes' => 'isian murid']);
        AdabMentorAssessment::create(['student_id' => $this->student->id, 'mentor_id' => $this->admin->id, 'year' => 2026, 'month' => 7, 'mentor_score' => 85, 'notes' => 'catatan pendamping']);

        $this->artisan('tad:isi-adab-bulan', ['bulan' => '2026-07', '--dry-run' => true])->assertSuccessful();
        $this->assertSame(1, AdabRecord::count(), 'Uji coba tidak menyimpan.');

        $this->artisan('tad:isi-adab-bulan', ['bulan' => '2026-07', '--force' => true])->assertSuccessful();

        $records = AdabRecord::where('student_id', $this->student->id)->get();
        $this->assertCount(count($effective), $records, 'Setiap hari efektif Juli terisi.');
        $this->assertSame('isian murid', $records->firstWhere('assessment_date', Carbon::parse($effective[0]))->notes, 'Isian asli tidak ditimpa.');
        $auto = $records->firstWhere('assessment_date', Carbon::parse($effective[1]));
        $this->assertEquals(100, $auto->student_score);
        $this->assertNotContains(false, array_merge(...array_values($auto->answers)), 'Semua butir "Ya".');

        $mentor = AdabMentorAssessment::where(['student_id' => $this->student->id, 'year' => 2026, 'month' => 7])->first();
        $this->assertSame(100, (int) $mentor->mentor_score);
        $this->assertStringContainsString('sebelumnya=85', $mentor->notes);

        // Menjalankan ulang tidak menggandakan dan tidak menimpa catatan nilai asli.
        $this->artisan('tad:isi-adab-bulan', ['bulan' => '2026-07', '--force' => true])->assertSuccessful();
        $this->assertCount(count($effective), AdabRecord::where('student_id', $this->student->id)->get());
        $this->assertStringContainsString('sebelumnya=85', $mentor->fresh()->notes);

        $this->artisan('tad:isi-adab-bulan', ['bulan' => '2026-07', '--batalkan' => true, '--force' => true])->assertSuccessful();
        $this->assertSame(['isian murid'], AdabRecord::pluck('notes')->all(), 'Hanya isian asli yang tersisa.');
        $this->assertSame([85, 'catatan pendamping'], [(int) $mentor->fresh()->mentor_score, $mentor->fresh()->notes]);
    }
}
