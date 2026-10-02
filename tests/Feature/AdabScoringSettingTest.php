<?php

namespace Tests\Feature;

use App\Http\Controllers\StudentReportController;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Pengaturan Adab > Rumus Nilai, Predikat & Deskripsi Rapor (Setting::adabScoring()): satu sumber
 * aturan untuk bobot kerajinan/pendamping, batas predikat, dan deskripsi Adab di rapor.
 */
class AdabScoringSettingTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function save(array $overrides = [])
    {
        return $this->actingAs($this->admin)->post(route('settings.adab.scoring'), array_replace_recursive([
            'attendance_weight' => 50,
            'thresholds' => ['A' => 85, 'B' => 75, 'C' => 65, 'D' => 55],
            'grades' => [
                'A' => ['term' => 'Istimewa', 'arabic' => 'Mumtaz'], 'B' => ['term' => 'Baik', 'arabic' => 'Jayyid'],
                'C' => ['term' => 'Cukup', 'arabic' => ''], 'D' => ['term' => 'Kurang', 'arabic' => "Dho'if"],
                'E' => ['term' => '', 'arabic' => "Dho'if Jiddan"],
            ],
            'descriptions' => ['A' => 'Teks Mumtaz kustom.', 'B' => '', 'C' => '', 'D' => '', 'E' => ''],
        ], $overrides));
    }

    #[Test]
    public function defaults_keep_the_previous_rules(): void
    {
        $this->assertSame(62.0, Setting::adabCompositeScore(80, 50), '40% x 80 + 60% x 50');
        $this->assertSame(75.0, Setting::adabCompositeScore(75, null), 'Tanpa nilai pendamping = kehadiran saja.');
        $this->assertSame(['A', 'B', 'D', 'E'], [Setting::getAdabGrade(90), Setting::getAdabGrade(89.9), Setting::getAdabGrade(60), Setting::getAdabGrade(59)]);
        $this->assertStringStartsWith('Menunjukkan sikap yang sangat sopan', StudentReportController::adabDescription(95));
        $this->assertSame(
            ['Sangat Baik / Mumtaz', 'Baik / Jayyid', 'Cukup / Maqbul', "Kurang / Dho'if", "Sangat Kurang / Dho'if Jiddan"],
            array_map(fn ($g) => Setting::getAdabGradeLabel($g), ['A', 'B', 'C', 'D', 'E'])
        );
        $this->assertSame('Mumtaz', Setting::adabGradeArabic('A'));
    }

    #[Test]
    public function saved_rules_are_used_everywhere(): void
    {
        $this->save()->assertRedirect(route('settings.adab'))->assertSessionHas('success');

        $this->assertSame(65.0, Setting::adabCompositeScore(80, 50), '50% x 80 + 50% x 50');
        $this->assertSame(['A', 'B', 'E'], [Setting::getAdabGrade(85), Setting::getAdabGrade(84), Setting::getAdabGrade(54)]);
        $this->assertSame('Teks Mumtaz kustom.', StudentReportController::adabDescription(90));
        $this->assertSame('Menunjukkan kesopanan kepada guru dan teman.', Setting::adabDescription('B'), 'Kosong = teks bawaan.');
        $this->assertSame(['Istimewa / Mumtaz', 'Cukup', 'Sangat Kurang / '."Dho'if Jiddan"], [Setting::getAdabGradeLabel('A'), Setting::getAdabGradeLabel('C'), Setting::getAdabGradeLabel('E')], 'Istilah kosong = bawaan; Arab kosong = istilah saja.');
        $this->assertSame('Cukup', Setting::adabGradeArabic('C'));

        $this->actingAs($this->admin)->get(route('settings.adab'))->assertOk()
            ->assertSee('Kerajinan 50% + Pendamping 50%')
            ->assertSee('85–100%');
    }

    #[Test]
    public function thresholds_must_descend(): void
    {
        $this->save(['thresholds' => ['B' => 90]])->assertSessionHasErrors('thresholds.B');
        $this->assertSame(90, Setting::adabScoring()['thresholds']['A'], 'Tidak tersimpan.');
    }
}
