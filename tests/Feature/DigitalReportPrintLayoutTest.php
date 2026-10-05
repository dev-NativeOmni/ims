<?php

namespace Tests\Feature;

use App\Http\Controllers\StudentReportController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Tampilan lembar rapor cetak (Pengaturan Rapor): posisi logo kiri/tengah/kanan & font
 * Times New Roman/Tahoma/Cambria, ikut tampil di pratinjau cetak.
 */
class DigitalReportPrintLayoutTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function saveSettings(array $layout)
    {
        return $this->actingAs($this->admin)->post(route('digital-reports.settings.update'), [
            'academic_year' => '2026/2027',
            'report_main_title' => 'LAPORAN', 'report_school_name' => 'SMA', 'report_city' => 'Sukoharjo',
            ...$layout,
        ]);
    }

    private function printedHeader(): string
    {
        $html = $this->actingAs($this->admin)->get(route('digital-reports.print', $this->student))->assertOk()->getContent();

        return substr($html, strpos($html, 'class="print-container'), 4000);
    }

    #[Test]
    public function logo_is_centered_in_times_new_roman_by_default(): void
    {
        $this->assertSame(['logo' => 'center', 'font' => 'times'], StudentReportController::printLayout());

        $header = $this->printedHeader();
        $this->assertStringContainsString('Times New Roman', $header);
        $this->assertSame(1, substr_count($header, 'logo_alazhar7.png'), 'Cetak hanya memuat satu logo.');
        $this->assertMatchesRegularExpression('/flex-col items-center px-2">\s*<img src="[^"]*logo_alazhar7\.png"/', $header, 'Logo di kolom tengah, di atas basmalah.');
    }

    #[Test]
    public function logo_position_and_font_follow_report_settings(): void
    {
        $this->saveSettings(['report_logo_position' => 'right', 'report_font' => 'cambria'])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(['logo' => 'right', 'font' => 'cambria'], StudentReportController::printLayout());
        $header = $this->printedHeader();
        $this->assertStringContainsString('font-family: Cambria', $header);
        $this->assertMatchesRegularExpression('/flex justify-end">\s*<img src="[^"]*logo_alazhar7\.png"/', $header, 'Logo di kolom kanan.');
        $this->assertSame(1, substr_count($header, 'logo_alazhar7.png'));

        $this->saveSettings(['report_logo_position' => 'left', 'report_font' => 'tahoma']);
        $header = $this->printedHeader();
        $this->assertStringContainsString('font-family: Tahoma', $header);
        $this->assertMatchesRegularExpression('/flex justify-start">\s*<img src="[^"]*logo_alazhar7\.png"/', $header, 'Logo di kolom kiri.');
    }

    #[Test]
    public function unknown_logo_position_or_font_is_rejected(): void
    {
        $this->saveSettings(['report_logo_position' => 'top', 'report_font' => 'comic'])
            ->assertSessionHasErrors(['report_logo_position', 'report_font']);
    }

    #[Test]
    public function settings_preview_binds_logo_position_and_font(): void
    {
        $this->saveSettings(['report_logo_position' => 'left', 'report_font' => 'tahoma']);

        $this->actingAs($this->admin)->get(route('digital-reports.settings'))
            ->assertOk()
            ->assertSee('name="report_logo_position" value="left"', false)
            ->assertSee('name="report_font" value="cambria"', false)
            ->assertSee('x-show="logoPosition === \'center\'"', false)
            ->assertSee('[reportFont]', false);
    }
}
