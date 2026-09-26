<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Surah;
use App\Models\UmmiRecord;
use App\Services\UmmiProgressService;
use App\Support\HafalanOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Grafik capaian Ummi Kelas 10: posisi Buku Ummi (Jilid & Halaman) dan hafalan surah
 * (Juz 30 mundur, juz berikutnya maju) per murid, dengan dua titik target dan dua ketuntasan.
 */
class UmmiChartTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        foreach ([29, 30] as $juz) {
            foreach (HafalanOrder::JUZ_RANGES[$juz] as $range) {
                Surah::updateOrCreate(['number' => $range['surah']], [
                    'name_ar' => "S{$range['surah']}", 'name_latin' => "Surah {$range['surah']}", 'total_ayah' => $range['end'],
                ]);
            }
        }

        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $this->classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'X E1', 'level' => 'X', 'tahfizh_days' => [1, 2, 3, 4]]);
        $this->student->update(['class_room_id' => $this->classRoom->id, 'tahfizh_level' => 'ummi', 'teacher_id' => $this->teacherProfile->id]);
    }

    private function ummi(string $date, string $jilid, string $halaman, ?int $surah = null, ?string $ayat = null): void
    {
        $record = UmmiRecord::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'tanggal' => $date, 'ummi_jilid' => $jilid, 'ummi_halaman' => $halaman, 'tatap_muka' => 1,
        ]);
        if ($surah) {
            $record->surahs()->create(['surah_id' => Surah::where('number', $surah)->value('id'), 'hafalan_ayah' => $ayat]);
        }
    }

    #[Test]
    public function positions_are_numbered_along_the_book_and_the_ummi_memorisation_order(): void
    {
        $service = app(UmmiProgressService::class);

        $this->assertSame(55, UmmiProgressService::pageValue('Jilid 2', 'Hal. 12-15'));
        $this->assertSame('J2 h.15', UmmiProgressService::pageLabel(55));
        $this->assertNull(UmmiProgressService::pageValue(null, '5'));

        // Juz 30 mundur: An-Nas ayat 1 paling awal, Al-Falaq setelah 6 ayat An-Nas.
        $this->assertSame(1, $service->hafalanValue(114, 1));
        $this->assertSame(7, $service->hafalanValue(113, 1));
        // Juz berikutnya maju: Al-Mulk ayat 1 tepat setelah seluruh Juz 30 (564 ayat).
        $this->assertSame(565, $service->hafalanValue(67, 1));
        $this->assertSame('Surah 67 1', $service->hafalanLabel(565));
    }

    #[Test]
    public function monthly_chart_shows_last_positions_two_targets_and_two_completions(): void
    {
        $this->ummi('2026-08-03', 'Jilid 1', '30', 114, '1-6');
        $this->ummi('2026-08-20', 'Jilid 2', '5', 112, '1-4');
        HafalanTarget::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'status' => 'active',
            'ummi_jilid' => 'Jilid 2', 'halaman_buku' => '1-5', 'halaman_peraga' => '99',
            'surah_id' => Surah::where('number', 111)->value('id'), 'ayah' => 5, 'target_date' => '2026-08-31',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.periodic', [
            'class_room_id' => $this->classRoom->id, 'period_type' => 'monthly', 'month' => 8, 'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee('GRAFIK CAPAIAN UMMI BULAN AGUSTUS 2026 KELAS X E1');
        $response->assertSee('KETUNTASAN UMMI BULAN AGUSTUS 2026 KELAS X E1');
        $chart = $response->viewData('ummiChart');
        $row = $chart['rows'][0];

        $this->assertSame(45, $row['book'], 'Jilid 2 hal. 5');
        $this->assertSame('Surah 112', $row['last_surah']);
        $this->assertSame(45, $row['target_book'], 'Target memakai Halaman Buku, bukan Peraga.');
        $this->assertTrue($row['book_reached']);
        $this->assertFalse($row['hafalan_reached'], 'Al-Ikhlas 4 belum sampai Al-Lahab 5.');
        $this->assertSame([1, 1, 0, 1], [$chart['book_done'], $chart['book_total'], $chart['hafalan_done'], $chart['hafalan_total']]);
    }

    #[Test]
    public function term_chart_stacks_the_start_position_and_each_months_progress(): void
    {
        $this->ummi('2026-06-15', 'Jilid 1', '10', 114, '1-6'); // sebelum term
        $this->ummi('2026-07-10', 'Jilid 1', '20', 113, '1-5');
        $this->ummi('2026-09-10', 'Jilid 1', '35', 112, '1-4');

        $response = $this->actingAs($this->admin)->get(route('reports.periodic', [
            'class_room_id' => $this->classRoom->id, 'period_type' => 'quarterly', 'quarter' => 1, 'year' => 2026,
        ]));

        $response->assertSee('GRAFIK CAPAIAN UMMI TERM 1 (JULI – SEPTEMBER 2026) KELAS X E1');
        $row = $response->viewData('ummiChart')['rows'][0];
        $this->assertSame(10, $row['book_base']);
        $this->assertSame([10, 0, 15], array_values($row['book_months']));
        $this->assertSame(35, $row['book_base'] + array_sum($row['book_months']));
        $this->assertSame(6, $row['hafalan_base'], 'An-Nas selesai sebelum term.');
        $this->assertSame($row['hafalan'], $row['hafalan_base'] + array_sum($row['hafalan_months']));
    }

    #[Test]
    public function ummi_target_form_saves_the_target_ayah(): void
    {
        $this->actingAs($this->admin)->post(route('hafalan-targets.store-bulk-ummi'), [
            'teacher_id' => $this->teacherProfile->id, 'class_room_id' => $this->classRoom->id,
            'ummi_jilid' => 'Jilid 2', 'halaman_buku' => '10', 'surah_id' => Surah::where('number', 111)->value('id'), 'ayah' => 3,
            'target_date' => '2026-08-31',
        ])->assertRedirect();

        $this->assertSame(3, HafalanTarget::where('student_id', $this->student->id)->value('ayah'));

        $this->actingAs($this->admin)->post(route('hafalan-targets.store-bulk-ummi'), [
            'teacher_id' => $this->teacherProfile->id, 'class_room_id' => $this->classRoom->id,
            'ummi_jilid' => 'Jilid 2', 'surah_id' => Surah::where('number', 111)->value('id'), 'ayah' => 99,
            'target_date' => '2026-08-31',
        ])->assertSessionHasErrors('ayah');
    }

    #[Test]
    public function the_chart_uses_the_last_record_not_a_higher_mistyped_one(): void
    {
        $this->ummi('2026-06-10', 'Jilid 3', '2', 114, '1-6');  // catatan lama keliru (lebih tinggi)
        $this->ummi('2026-09-15', 'Jilid 2', '21-23');
        $this->ummi('2026-09-17', 'Jilid 2', '24-25', 86, '1-9');

        $monthly = $this->actingAs($this->admin)->get(route('reports.periodic', [
            'class_room_id' => $this->classRoom->id, 'period_type' => 'monthly', 'month' => 9, 'year' => 2026,
        ]))->viewData('ummiChart')['rows'][0];
        $this->assertSame('J2 h.25', $monthly['book_label'], 'Capaian akhir = catatan terakhir (Jilid 2 hal. 24-25).');

        $term = $this->actingAs($this->admin)->get(route('reports.periodic', [
            'class_room_id' => $this->classRoom->id, 'period_type' => 'quarterly', 'quarter' => 1, 'year' => 2026,
        ]))->viewData('ummiChart')['rows'][0];
        $this->assertSame(65, $term['book_base'] + array_sum($term['book_months']), 'Puncak tumpukan = J2 h.25, tidak melampaui.');
    }
}
