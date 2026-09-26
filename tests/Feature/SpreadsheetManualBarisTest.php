<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportController;
use App\Models\Attendance;
use App\Models\HafalanRecordSurah;
use App\Models\UmmiRecordSurah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Input spreadsheet: kolom Baris manual (validasi guru). Diisi = angka guru yang disimpan;
 * dikosongkan = hitungan kalkulator baris. Saat dibuka lagi, isian hanya terisi bila manual.
 */
class SpreadsheetManualBarisTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function save(string $type, array $cell): void
    {
        // SQLite tes menyimpan tanggal presensi dengan jam; kosongkan supaya simpan ulang tidak bentrok.
        Attendance::query()->delete();

        $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), [
            'class_room_id' => $this->student->class_room_id,
            'month' => '2026-08',
            'type' => $type,
            'records' => [$this->student->id => ['dates' => ['2026-08-03' => ['attendance' => 'hadir'] + $cell]]],
        ])->assertSessionHas('success');
    }

    private function hafalan(?string $baris): array
    {
        return ['hafalans' => [[
            'id' => null, 'surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 5,
            'score' => '95', 'status' => 'passed', 'submission_type' => 'new', 'baris' => $baris,
        ]]];
    }

    #[Test]
    public function manual_baris_is_saved_and_blank_uses_the_calculator(): void
    {
        $calculated = ReportController::calculateLines($this->surah->number, 1, 5, $this->surah->total_ayah);

        $this->save('hafalan', $this->hafalan('7,5'));
        $this->assertSame(7.5, (float) HafalanRecordSurah::query()->latest('id')->value('baris'));

        $this->save('hafalan', $this->hafalan(''));
        $this->assertSame($calculated, (float) HafalanRecordSurah::query()->latest('id')->value('baris'));
    }

    #[Test]
    public function reopening_the_sheet_shows_only_manual_values(): void
    {
        $this->save('hafalan', $this->hafalan('9'));
        $cell = fn () => $this->actingAs($this->teacherUser)
            ->get(route('spreadsheet-input.index', ['class_room_id' => $this->student->class_room_id, 'month' => '2026-08']))
            ->viewData('hafalanRecordsMap')[$this->student->id]['2026-08-03'];
        $last = fn () => collect($cell())->sortBy('id')->last();

        $this->assertSame('9', $last()['baris']);

        $this->save('hafalan', $this->hafalan(null));
        $this->assertSame('', $last()['baris'], 'Sama dengan kalkulator = isian kosong (placeholder perkiraan).');
    }

    #[Test]
    public function ummi_hafalan_accepts_manual_baris_too(): void
    {
        $this->student->update(['tahfizh_level' => 'ummi']);
        $this->save('ummi', [
            'ummi_jilid' => 'Jilid 2', 'ummi_halaman' => '10',
            'hafalans' => [['id' => null, 'surah_id' => $this->surah->id, 'ayah' => '1-5', 'baris' => '3']],
        ]);

        $this->assertSame(3.0, (float) UmmiRecordSurah::query()->value('baris'));
    }
}
