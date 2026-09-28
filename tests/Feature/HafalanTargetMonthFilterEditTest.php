<?php

namespace Tests\Feature;

use App\Models\HafalanTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Target Bulanan: filter bulan deadline dan edit target tersimpan (surah/ayat maupun Ummi).
 */
class HafalanTargetMonthFilterEditTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function target(string $date, array $extra = []): HafalanTarget
    {
        return HafalanTarget::create($extra + [
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id, 'ayah' => 5, 'target_date' => $date, 'status' => 'active',
        ]);
    }

    #[Test]
    public function month_filter_limits_the_list_and_the_summary(): void
    {
        $august = $this->target('2026-08-20');
        $september = $this->target('2026-09-15');

        $response = $this->actingAs($this->admin)->get(route('hafalan-targets.index', ['month' => '2026-09']));

        $response->assertOk();
        $this->assertSame([$september->id], $response->viewData('targets')->pluck('id')->all());
        $this->assertSame(1, $response->viewData('summary')['total']);
        $this->assertArrayHasKey(now()->format('Y-m'), $response->viewData('monthOptions'));

        $all = $this->actingAs($this->admin)->get(route('hafalan-targets.index'));
        $this->assertEqualsCanonicalizing([$august->id, $september->id], $all->viewData('targets')->pluck('id')->all());
    }

    #[Test]
    public function saved_targets_have_an_edit_button_and_editing_returns_to_the_filtered_list(): void
    {
        $target = $this->target('2026-09-15');
        $back = route('hafalan-targets.index', ['month' => '2026-09']);

        $this->actingAs($this->teacherUser)->get($back)
            ->assertSee(route('hafalan-targets.edit', ['hafalan_target' => $target, 'back' => $back]), false);

        $this->actingAs($this->teacherUser)->put(route('hafalan-targets.update', $target), [
            'student_id' => $this->student->id, 'surah_id' => $this->surah->id, 'ayah' => 7,
            'target_date' => '2026-09-20', 'status' => 'completed', 'back' => $back,
        ])->assertRedirect($back);

        $target->refresh();
        $this->assertSame(7, $target->ayah);
        $this->assertSame('2026-09-20', $target->target_date->toDateString());
        $this->assertNotNull($target->completed_at, 'Status selesai mengisi tanggal selesai.');
    }

    #[Test]
    public function ummi_targets_can_be_edited_with_their_own_fields(): void
    {
        $target = $this->target('2026-09-15', ['ummi_jilid' => 'Jilid 1', 'halaman_buku' => '10', 'surah_id' => null, 'ayah' => null]);

        $this->actingAs($this->teacherUser)->get(route('hafalan-targets.edit', $target))
            ->assertOk()->assertSee('Halaman Buku')->assertSee('name="ummi_jilid"', false);

        $this->actingAs($this->teacherUser)->put(route('hafalan-targets.update', $target), [
            'ummi_jilid' => 'Jilid 2', 'halaman_peraga' => '5', 'halaman_buku' => '12-15',
            'surah_id' => $this->surah->id, 'ayah' => 3, 'target_date' => '2026-09-30', 'status' => 'active',
        ])->assertRedirect(route('hafalan-targets.index'));

        $target->refresh();
        $this->assertSame(['Jilid 2', '5', '12-15', 3], [$target->ummi_jilid, $target->halaman_peraga, $target->halaman_buku, $target->ayah]);
        $this->assertSame($this->student->id, $target->student_id, 'Murid tetap.');

        $this->actingAs($this->teacherUser)->put(route('hafalan-targets.update', $target), [
            'ummi_jilid' => 'Jilid 2', 'surah_id' => $this->surah->id, 'ayah' => 999, 'target_date' => '2026-09-30', 'status' => 'active',
        ])->assertSessionHasErrors('ayah');
    }

    #[Test]
    public function back_url_outside_the_target_pages_is_ignored(): void
    {
        $target = $this->target('2026-09-15');

        $this->actingAs($this->admin)->put(route('hafalan-targets.update', $target), [
            'student_id' => $this->student->id, 'surah_id' => $this->surah->id, 'ayah' => 5,
            'target_date' => '2026-09-15', 'status' => 'active', 'back' => 'https://contoh-jahat.test/phish',
        ])->assertRedirect(route('hafalan-targets.index'));
    }
}
