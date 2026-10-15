<?php

namespace Tests\Feature;

use App\Models\HafalanRecord;
use App\Models\Surah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Riwayat setoran (tab Reguler): kolom Baris = baris yang dihitung capaian (ayat baru), sama dengan rapor.
 */
class HafalanRecordCountedLinesTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private function setor(Surah $surah, string $date, int $start, int $end, float $baris, string $status = 'passed'): HafalanRecord
    {
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $header->surahs()->create(['surah_id' => $surah->id, 'ayah_start' => $start, 'ayah_end' => $end, 'status' => $status, 'submission_type' => 'new', 'baris' => $baris]);

        return $header;
    }

    #[Test]
    public function baris_column_shows_only_new_ayat_lines_with_the_recorded_value_as_note(): void
    {
        $this->setUpHafizPlusData();
        $baqarah = Surah::firstOrCreate(['number' => 2], ['name_ar' => 'البقرة', 'name_latin' => 'Al-Baqarah', 'total_ayah' => 286, 'juz_start' => 1, 'juz_end' => 3]);
        $first = $this->setor($baqarah, '2026-07-30', 245, 246, 8);
        $connecting = $this->setor($baqarah, '2026-08-06', 246, 247, 11);   // ayat 246 sambungan
        $repeat = $this->setor($baqarah, '2026-08-07', 245, 247, 12);       // semua ayat sudah lulus
        $notPassed = $this->setor($baqarah, '2026-08-08', 248, 249, 7, 'repeat');

        $response = $this->actingAs($this->admin)->get(route('hafalan-records.index', ['category' => 'reguler']))->assertOk();
        $counted = $response->viewData('countedLines');

        $this->assertSame(8.0, $counted[$first->id]['counted']);
        // Hanya ayat 247 yang baru: lebih kecil dari 11 baris catatan, tidak nol.
        $this->assertSame([11.0, ['partial']], [$counted[$connecting->id]['raw'], $counted[$connecting->id]['kinds']]);
        $this->assertTrue($counted[$connecting->id]['counted'] > 0 && $counted[$connecting->id]['counted'] < 11);
        $this->assertSame([0.0, ['repeat']], [$counted[$repeat->id]['counted'], $counted[$repeat->id]['kinds']]);
        $this->assertSame([0.0, ['not_passed']], [$counted[$notPassed->id]['counted'], $counted[$notPassed->id]['kinds']]);
        $response->assertSee('dari 11 · sebagian ulang')->assertSee('dari 12 · ulang')->assertSee('dari 7 · belum lulus');
    }
}
