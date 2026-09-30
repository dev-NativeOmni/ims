<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanRecordSurah;
use App\Models\Program;
use App\Models\UmmiRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Setoran ganda identik (mis. tombol Simpan terklik dua kali): dicegah saat simpan, dan yang
 * sudah terlanjur ada dibersihkan lewat tad:hapus-setoran-ganda.
 */
class DuplicateSetoranTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function submitting_the_same_ummi_form_twice_keeps_one_session(): void
    {
        $program = Program::create(['name' => 'Program Tahfizh', 'status' => 'active']);
        $classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'X E1', 'level' => 'X', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'ummi', 'teacher_id' => $this->teacherProfile->id]);

        $payload = [
            'class_room_id' => $classRoom->id,
            'student_ids' => [$this->student->id],
            'tanggal' => '2026-09-14',
            'ummi_jilid' => 'Jilid 2',
            'ummi_halaman_awal' => 10,
            'ummi_halaman_akhir' => 12,
            'hafalan_surah_ids' => [$this->surah->id],
            'hafalan_ayahs' => ['1-5'],
            'disimak_guru' => 'Ya',
            'disimak_ortu' => 'Ya',
            'redirect_to' => 'hafalan',
        ];
        $this->actingAs($this->teacherUser)->post(route('ummi-records.store'), $payload)->assertSessionHasNoErrors();
        $this->actingAs($this->teacherUser)->post(route('ummi-records.store'), $payload)->assertSessionHasNoErrors();

        $this->assertSame(1, UmmiRecord::where('student_id', $this->student->id)->count());
        $this->assertSame(1, UmmiRecord::where('student_id', $this->student->id)->first()->surahs()->count());
    }

    #[Test]
    public function submitting_the_same_hafalan_twice_does_not_create_a_second_line(): void
    {
        $payload = [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-09-14',
            'surah_ids' => [$this->surah->id],
            'ayah_starts' => [1],
            'ayah_ends' => [5],
            'submission_types' => ['new'],
            'statuses' => ['repeat'], // bukan "passed", jadi lolos validasi duplikat -- tetap tidak boleh ganda
        ];
        $this->actingAs($this->admin)->post(route('hafalan-records.store'), $payload)->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('hafalan-records.store'), $payload)
            ->assertSessionHas('success', 'Setoran ini sudah tercatat sebelumnya, tidak disimpan ganda.');

        $this->assertSame(1, HafalanRecordSurah::whereHas('hafalanRecord', fn ($q) => $q->where('student_id', $this->student->id))->count());
    }

    #[Test]
    public function command_removes_identical_duplicates_and_keeps_the_earliest(): void
    {
        // Hafalan: dua header tanggal sama, baris identik.
        $makeHafalan = function () {
            $record = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => '2026-09-14']);
            $record->surahs()->create(['surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 5, 'submission_type' => 'new', 'status' => 'passed']);

            return $record;
        };
        $firstHafalan = $makeHafalan();
        $secondHafalan = $makeHafalan();

        // Ummi: dua sesi identik + satu sesi berbeda (nilai lain) di tanggal yang sama.
        $makeUmmi = function (string $nilai) {
            $ummi = UmmiRecord::create([
                'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'tatap_muka' => 1,
                'tanggal' => '2026-09-15', 'ummi_jilid' => 'Jilid 2', 'ummi_halaman' => '10-12', 'nilai' => $nilai,
            ]);
            $ummi->surahs()->create(['surah_id' => $this->surah->id, 'hafalan_ayah' => '1-5', 'sort_order' => 0]);

            return $ummi;
        };
        $firstUmmi = $makeUmmi('B');
        $identicalUmmi = $makeUmmi('B');
        $differentUmmi = $makeUmmi('A');

        $this->artisan('tad:hapus-setoran-ganda', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(3, UmmiRecord::count());

        $this->artisan('tad:hapus-setoran-ganda', ['--force' => true])->assertSuccessful();

        $this->assertNotNull($firstHafalan->fresh());
        $this->assertTrue($secondHafalan->fresh()->trashed(), 'Header yang kosong setelah baris ganda dihapus ikut dihapus.');
        $this->assertSame(1, HafalanRecordSurah::count());

        $this->assertNotNull($firstUmmi->fresh());
        $this->assertNull($identicalUmmi->fresh());
        $this->assertNotNull($differentUmmi->fresh(), 'Isi berbeda tidak dihapus, hanya dilaporkan.');

        $this->assertSame(2, AuditLog::where('user_name', 'Sistem (tad:hapus-setoran-ganda)')->count());
    }
}
