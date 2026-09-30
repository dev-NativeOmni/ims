<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\Setting;
use App\Support\Signatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Tanda tangan pejabat (Pengaturan Umum) & guru (Profil), dan pemakaiannya di
 * Laporan Triwulan (.xlsx) serta rapor cetak.
 */
class SignatureTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        Storage::fake('local');
    }

    private function png(string $name = 'ttd.png'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 300, 100);
    }

    #[Test]
    public function super_admin_uploads_and_removes_official_signatures_privately(): void
    {
        $this->actingAs($this->superAdmin)->post(route('settings.update'), [
            'signatures' => ['headmaster' => $this->png(), 'coord_tanse' => $this->png()],
        ])->assertRedirect(route('settings.index'));

        $path = Signatures::officialFile('headmaster');
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
        $this->assertStringStartsWith('signatures/officials/', $path);

        $this->actingAs($this->superAdmin)->get(route('settings.index'))->assertOk()->assertSee('data:image/', false);

        $this->actingAs($this->superAdmin)->post(route('settings.update'), ['reset_signatures' => ['headmaster']]);
        $this->assertNull(Signatures::officialFile('headmaster'));
        Storage::disk('local')->assertMissing($path);
        $this->assertNotNull(Signatures::officialFile('coord_tanse'), 'Tanda tangan lain tidak ikut terhapus.');
    }

    #[Test]
    public function admin_cannot_change_official_signatures(): void
    {
        $this->actingAs($this->admin)->post(route('settings.update'), ['signatures' => ['headmaster' => $this->png()]])->assertForbidden();
        $this->assertNull(Signatures::officialFile('headmaster'));
    }

    #[Test]
    public function teacher_uploads_own_signature_from_profile_and_non_teachers_cannot(): void
    {
        $this->actingAs($this->teacherUser)->get(route('profile.edit'))->assertOk()->assertSee('Tanda Tangan');
        $this->actingAs($this->teacherUser)->post(route('profile.signature.update'), ['signature' => $this->png()])
            ->assertRedirect(route('profile.edit'));

        $path = $this->teacherUser->fresh()->signature_path;
        Storage::disk('local')->assertExists($path);

        $this->actingAs($this->teacherUser)->delete(route('profile.signature.destroy'));
        $this->assertNull($this->teacherUser->fresh()->signature_path);
        Storage::disk('local')->assertMissing($path);

        $this->actingAs($this->parentUser)->get(route('profile.edit'))->assertOk()->assertDontSee('profile/signature', false);
        $this->actingAs($this->parentUser)->post(route('profile.signature.update'), ['signature' => $this->png()])->assertForbidden();
    }

    #[Test]
    public function quarterly_export_adds_signature_blocks_under_jurnal_and_capaian(): void
    {
        $program = Program::create(['name' => 'Program Reguler Ttd', 'status' => 'active']);
        $classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI Ttd', 'level' => 'XI', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        $this->student->update(['class_room_id' => $classRoom->id, 'teacher_id' => $this->teacherProfile->id, 'tahfizh_level' => 'reguler']);
        Setting::set('report_headmaster_name', 'Moh. Pandoyo, S.Si., M.Pd., Gr.');
        Setting::set('report_city', 'Sukoharjo');

        $this->actingAs($this->superAdmin)->post(route('settings.update'), ['signatures' => ['headmaster' => $this->png()]]);
        $this->actingAs($this->teacherUser)->post(route('profile.signature.update'), ['signature' => $this->png()]);

        $response = $this->actingAs($this->admin)->get(route('reports.quarterly.export', [
            'class_room_id' => $classRoom->id, 'academic_year' => '2026/2027', 'term' => '1',
        ]));
        $response->assertOk();
        $tmp = tempnam(sys_get_temp_dir(), 'ttd').'.xlsx';
        file_put_contents($tmp, $response->streamedContent());
        $spreadsheet = IOFactory::load($tmp);
        unlink($tmp);

        foreach (['Jurnal', 'Setoran'] as $title) {
            $sheet = collect($spreadsheet->getAllSheets())->first(fn ($s) => str_starts_with($s->getTitle(), $title));
            $this->assertNotNull($sheet, "Sheet {$title} ada.");
            $cells = collect($sheet->toArray())->flatten()->filter()->map(fn ($v) => (string) $v);

            $this->assertTrue($cells->contains('Mengetahui,'), "{$title}: ada 'Mengetahui,'.");
            $this->assertTrue($cells->contains('Guru Pengampu'), "{$title}: ada 'Guru Pengampu'.");
            $this->assertTrue($cells->contains('Moh. Pandoyo, S.Si., M.Pd., Gr.'), "{$title}: nama kepala sekolah.");
            $this->assertTrue($cells->contains($this->teacherUser->name), "{$title}: nama guru pengampu.");
            $this->assertTrue($cells->contains('Sukoharjo, 30 September 2026'), "{$title}: kota & tanggal akhir triwulan.");
            // 3 bulan x (kepala sekolah + guru)
            $this->assertCount(6, $sheet->getDrawingCollection(), "{$title}: gambar tanda tangan ditanam.");
        }
    }

    #[Test]
    public function export_without_uploaded_signatures_still_has_names_but_no_images(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.quarterly.export', ['academic_year' => '2026/2027', 'term' => '1']));
        $tmp = tempnam(sys_get_temp_dir(), 'ttd').'.xlsx';
        file_put_contents($tmp, $response->streamedContent());
        $sheet = IOFactory::load($tmp)->getSheetByName('Jurnal');
        unlink($tmp);

        $this->assertTrue(collect($sheet->toArray())->flatten()->contains('Guru Pengampu'));
        $this->assertCount(0, $sheet->getDrawingCollection());
    }

    #[Test]
    public function printed_rapor_embeds_official_signatures(): void
    {
        $this->actingAs($this->superAdmin)->post(route('settings.update'), ['signatures' => ['coord_tahfizh' => $this->png()]]);

        $response = $this->actingAs($this->admin)->get(route('digital-reports.print', [$this->student]));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'alt="Tanda tangan"'));
        $response->assertSee('data:image/', false);
    }

    /** Isian wajib Pengaturan Rapor (nama/NIK pejabat) + tambahan. */
    private function raporSettings(array $extra = []): array
    {
        return $extra + [
            'academic_year' => '2026/2027', 'semester' => 1,
            'report_coord_tahfizh_name' => 'Zainal Arifin, S.Pd', 'report_coord_tahfizh_nik' => '06.0577',
            'report_coord_keagamaan_name' => 'Rifqi Ihsan, S.Pd., Gr.', 'report_coord_keagamaan_nik' => '15.06.0393',
            'report_headmaster_title' => 'Kepala SMA Islam Al Azhar 7 Sukoharjo',
            'report_headmaster_name' => 'Moh Pandoyo, S.Si., M.Pd., Gr.', 'report_headmaster_nik' => '08.04.0160',
            'report_coord_tanse_name' => 'Yatim Hermawan, S.E., S.Kom', 'report_coord_tanse_nik' => '12.06.0280',
        ];
    }

    #[Test]
    public function super_admin_manages_official_signatures_from_rapor_settings_with_preview(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('digital-reports.settings.update'), $this->raporSettings(['signatures' => ['headmaster' => $this->png(), 'coord_tanse' => $this->png()]]))
            ->assertRedirect();

        $path = Signatures::officialFile('headmaster');
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);

        $page = $this->actingAs($this->superAdmin)->get(route('digital-reports.settings'));
        $page->assertOk();
        $this->assertStringStartsWith('data:image/', $page->viewData('officialSignatures')['headmaster']);
        $this->assertTrue($page->viewData('canEditSignatures'));
        $page->assertSee('signatures[headmaster]', false);

        // Data sama dengan Pengaturan Umum.
        $this->actingAs($this->superAdmin)->get(route('settings.index'))->assertSee('data:image/', false);

        $this->actingAs($this->superAdmin)->post(route('digital-reports.settings.update'), $this->raporSettings(['reset_signatures' => ['headmaster']]));
        $this->assertNull(Signatures::officialFile('headmaster'));
        $this->assertNotNull(Signatures::officialFile('coord_tanse'), 'Tanda tangan lain tidak ikut terhapus.');
    }

    #[Test]
    public function admin_sees_signatures_in_rapor_settings_but_cannot_change_them(): void
    {
        $this->actingAs($this->superAdmin)->post(route('digital-reports.settings.update'), $this->raporSettings(['signatures' => ['headmaster' => $this->png()]]));
        $original = Signatures::officialFile('headmaster');

        $page = $this->actingAs($this->admin)->get(route('digital-reports.settings'));
        $page->assertOk()->assertSee('Diatur oleh Super Admin');
        $page->assertDontSee('signatures[headmaster]', false);
        $this->assertFalse($page->viewData('canEditSignatures'));

        $this->actingAs($this->admin)->post(route('digital-reports.settings.update'), $this->raporSettings([
            'report_headmaster_name' => 'Nama Baru',
            'signatures' => ['headmaster' => $this->png('lain.png')],
            'reset_signatures' => ['headmaster'],
        ]))->assertRedirect();

        $this->assertSame($original, Signatures::officialFile('headmaster'), 'Unggahan/hapus dari Admin diabaikan.');
        $this->assertSame('Nama Baru', Setting::get('report_headmaster_name'), 'Nama/NIK tetap tersimpan.');
    }

    #[Test]
    public function quarterly_web_jurnal_shows_the_halaqoh_teachers_signature_as_paraf(): void
    {
        $program = Program::create(['name' => 'Program Reguler Paraf', 'status' => 'active']);
        $classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI Paraf', 'level' => 'XI', 'tahfizh_days' => [1]]);
        $this->student->update(['class_room_id' => $classRoom->id, 'teacher_id' => $this->teacherProfile->id, 'tahfizh_level' => 'reguler']);
        Attendance::create([
            'student_id' => $this->student->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => '2026-07-06',
            'status' => 'hadir',
        ]);
        $query = ['class_room_id' => $classRoom->id, 'academic_year' => '2026/2027', 'term' => '1'];

        // Belum ada tanda tangan: paraf tetap tanda centang.
        $this->actingAs($this->admin)->get(route('reports.quarterly', $query))
            ->assertOk()
            ->assertDontSee('--paraf-signature', false);

        $this->actingAs($this->teacherUser)->post(route('profile.signature.update'), ['signature' => $this->png()]);

        $this->actingAs($this->admin)->get(route('reports.quarterly', $query))
            ->assertOk()
            ->assertSee('--paraf-signature: url(data:image/', false)
            ->assertSee('Paraf '.$this->teacherUser->name, false);
    }
}
