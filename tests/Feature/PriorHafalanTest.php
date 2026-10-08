<?php

namespace Tests\Feature;

use App\Models\HafalanRecord;
use App\Models\Role;
use App\Models\StudentPriorHafalan;
use App\Models\Surah;
use App\Models\User;
use App\Services\HafalanProgressService;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Hafalan sebelum aplikasi: dianggap sudah hafal untuk target & capaian ayat baru, tapi bukan setoran.
 */
class PriorHafalanTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private HafalanProgressService $progress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        $this->progress = app(HafalanProgressService::class);
        // Surah Juz 28-30 (cukup untuk tes ini).
        foreach ([28, 29, 30] as $juz) {
            foreach (HafalanOrder::JUZ_RANGES[$juz] as $range) {
                Surah::firstOrCreate(['number' => $range['surah']], [
                    'name_ar' => 'S'.$range['surah'], 'name_latin' => 'Surah '.$range['surah'],
                    'total_ayah' => $range['end'], 'juz_start' => $juz, 'juz_end' => $juz,
                ]);
            }
        }
    }

    private function setor(int $surah, int $start, int $end, string $date): void
    {
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $header->surahs()->create(['surah_id' => Surah::where('number', $surah)->value('id'), 'ayah_start' => $start, 'ayah_end' => $end, 'status' => 'passed', 'submission_type' => 'new']);
    }

    #[Test]
    public function teacher_marks_whole_juz_and_targets_skip_them(): void
    {
        $this->actingAs($this->teacherUser)->post(route('hafalan-targets.prior.store', $this->student), ['juz' => 30])->assertSessionHas('success');
        $this->actingAs($this->teacherUser)->post(route('hafalan-targets.prior.store', $this->student), ['juz' => 29])->assertSessionHas('success');
        $this->assertSame(37 + 11, StudentPriorHafalan::count());

        $records = $this->progress->records($this->student);
        $start = $this->progress->startPoint($this->student, $records, Carbon::parse('2026-07-01'), Carbon::parse('2026-09-30'));
        $first = HafalanOrder::segments($start['surah'], $start['ayah'], $this->progress->coverage($records, Carbon::parse('2026-07-01')), collect(), $this->student->hafalan_direction, $this->progress->juzOrders($this->student, $records))->current();

        $this->assertSame(58, $first[0], 'Juz 30 & 29 sudah hafal: target langsung ke Juz 28 (Al-Mujadilah).');
        $this->assertSame([], $this->progress->detectedJuzOrders($records), 'Hafalan lama tidak dipakai menebak arah juz.');
    }

    #[Test]
    public function setoran_of_ayat_memorised_before_the_app_is_not_new(): void
    {
        StudentPriorHafalan::create(['student_id' => $this->student->id, 'surah_id' => Surah::where('number', 67)->value('id'), 'ayah_start' => 1, 'ayah_end' => 20]);
        $this->setor(67, 15, 30, '2026-07-10'); // 15-20 sudah hafal, 21-30 baru

        $details = $this->progress->passedLineDetails($this->progress->records($this->student), Carbon::parse('2026-07-01'), Carbon::parse('2026-09-30'));

        $this->assertCount(1, $details, 'Hafalan lama bukan setoran triwulan.');
        $this->assertSame('partial', $details[0]['kind']);
        $this->assertSame(10, $details[0]['new_ayat']);
    }

    #[Test]
    public function urutan_page_shows_prior_and_only_staff_can_change_it(): void
    {
        $this->actingAs($this->teacherUser)->post(route('hafalan-targets.prior.store', $this->student), [
            'surah_id' => Surah::where('number', 78)->value('id'), 'ayah_start' => 1, 'ayah_end' => 99,
        ])->assertSessionHasErrors('ayah_end');

        $this->actingAs($this->teacherUser)->post(route('hafalan-targets.prior.store', $this->student), [
            'surah_id' => Surah::where('number', 78)->value('id'), 'ayah_start' => 1, 'ayah_end' => 40,
        ])->assertSessionHas('success');

        $this->actingAs($this->teacherUser)->get(route('hafalan-targets.juz-orders', $this->student))
            ->assertOk()->assertSee('Hafalan Sebelum Aplikasi')->assertSee('Surah 78 1–40');

        $headmaster = User::factory()->create(['role_id' => Role::where('name', 'headmaster')->value('id'), 'status' => 'active']);
        $this->actingAs($headmaster)->post(route('hafalan-targets.prior.store', $this->student), ['juz' => 30])->assertForbidden();

        $id = StudentPriorHafalan::value('id');
        $this->actingAs($this->teacherUser)->delete(route('hafalan-targets.prior.destroy', $this->student), ['id' => $id])->assertSessionHas('success');
        $this->assertSame(0, StudentPriorHafalan::count());
    }
}
