<?php

namespace Tests\Feature;

use App\Models\HafalanRecord;
use App\Models\Surah;
use App\Models\UmmiRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class HafalanRecordBulkEditTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function admin_can_bulk_update_reguler_hafalan_records(): void
    {
        $surah2 = Surah::create([
            'number' => 2,
            'name_arabic' => 'البقرة',
            'name_latin' => 'Al-Baqarah',
            'total_ayah' => 286,
        ]);

        $record1 = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-09-01',
            'notes' => 'Awal',
        ]);
        $record1->surahs()->create([
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'score' => 80,
            'status' => 'passed',
            'baris' => 7,
        ]);

        $record2 = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-09-02',
            'notes' => 'Kedua',
        ]);
        $record2->surahs()->create([
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'submission_type' => 'new',
            'score' => 75,
            'status' => 'passed',
            'baris' => 5,
        ]);

        $response = $this->actingAs($this->admin)->post(route('hafalan-records.bulk-update'), [
            'records' => [
                [
                    'id' => $record1->id,
                    'submitted_at' => '2026-09-05',
                    'surah_id' => $this->surah->id,
                    'ayah_start' => 1,
                    'ayah_end' => 7,
                    'submission_type' => 'continuation',
                    'score' => 'A',
                    'status' => 'passed',
                    'baris' => 8,
                ],
                [
                    'id' => $record2->id,
                    'submitted_at' => '2026-09-06',
                    'surah_id' => $surah2->id,
                    'ayah_start' => 1,
                    'ayah_end' => 10,
                    'submission_type' => 'new',
                    'score' => 'B+',
                    'status' => 'needs_improvement',
                    'baris' => 10,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $record1->refresh();
        $this->assertEquals('2026-09-05', $record1->submitted_at->format('Y-m-d'));
        $firstSurah1 = $record1->surahs->first();
        $this->assertEquals('continuation', $firstSurah1->submission_type);
        $this->assertEquals(95, $firstSurah1->score);
        $this->assertEquals(8, $firstSurah1->baris);

        $record2->refresh();
        $this->assertEquals('2026-09-06', $record2->submitted_at->format('Y-m-d'));
        $firstSurah2 = $record2->surahs->first();
        $this->assertEquals($surah2->id, $firstSurah2->surah_id);
        $this->assertEquals(10, $firstSurah2->ayah_end);
        $this->assertEquals(85, $firstSurah2->score);
        $this->assertEquals('needs_improvement', $firstSurah2->status);
    }

    #[Test]
    public function admin_can_bulk_update_ummi_records(): void
    {
        $ummi1 = UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => '2026-09-01',
            'tatap_muka' => 1,
            'ummi_jilid' => 'Jilid 1',
            'ummi_halaman' => '1-5',
            'materi' => 'Pengenalan',
            'nilai' => 'B',
            'disimak_guru' => 'Ya',
            'disimak_ortu' => 'Tidak',
        ]);
        $ummi1->surahs()->create([
            'surah_id' => $this->surah->id,
            'hafalan_ayah' => '1-5',
            'baris' => 5,
        ]);

        $ummi2 = UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => '2026-09-02',
            'tatap_muka' => 2,
            'ummi_jilid' => 'Jilid 1',
            'ummi_halaman' => '6-10',
            'materi' => 'Latihan',
            'nilai' => 'B',
            'disimak_guru' => 'Ya',
            'disimak_ortu' => 'Tidak',
        ]);

        $response = $this->actingAs($this->admin)->post(route('ummi-records.bulk-update'), [
            'records' => [
                [
                    'id' => $ummi1->id,
                    'tanggal' => '2026-09-03',
                    'tatap_muka' => 3,
                    'ummi_jilid' => 'Jilid 2',
                    'ummi_halaman' => '1-3',
                    'materi' => 'Mad Thobi\'i',
                    'nilai' => 'A',
                    'disimak_guru' => 'Ya',
                    'disimak_ortu' => 'Ya',
                    'surah_id' => $this->surah->id,
                    'hafalan_ayah' => '1-7',
                    'hafalan_baris' => 7,
                ],
                [
                    'id' => $ummi2->id,
                    'tanggal' => '2026-09-04',
                    'tatap_muka' => 4,
                    'ummi_jilid' => 'Jilid 2',
                    'ummi_halaman' => '4-6',
                    'materi' => 'Kelanjutan',
                    'nilai' => 'A+',
                    'disimak_guru' => 'Ya',
                    'disimak_ortu' => 'Ya',
                    'surah_id' => $this->surah->id,
                    'hafalan_ayah' => '1-3',
                    'hafalan_baris' => 3,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $ummi1->refresh();
        $this->assertEquals('2026-09-03', $ummi1->tanggal->format('Y-m-d'));
        // TM otomatis = urutan pertemuan Ummi di triwulan: 3 Sep pertemuan ke-1, 4 Sep ke-2.
        $this->assertEquals(1, $ummi1->tatap_muka);
        $this->assertEquals('Jilid 2', $ummi1->ummi_jilid);
        $this->assertEquals('A', $ummi1->nilai);
        $this->assertEquals('Ya', $ummi1->disimak_ortu);
        $firstSurah1 = $ummi1->surahs->first();
        $this->assertEquals('1-7', $firstSurah1->hafalan_ayah);
        $this->assertEquals(7, $firstSurah1->baris);

        $ummi2->refresh();
        $this->assertEquals('2026-09-04', $ummi2->tanggal->format('Y-m-d'));
        $this->assertEquals(2, $ummi2->tatap_muka);
        $this->assertEquals('A+', $ummi2->nilai);
        $firstSurah2 = $ummi2->surahs->first();
        $this->assertNotNull($firstSurah2);
        $this->assertEquals($this->surah->id, $firstSurah2->surah_id);
        $this->assertEquals('1-3', $firstSurah2->hafalan_ayah);
        $this->assertEquals(3, $firstSurah2->baris);
    }
}
