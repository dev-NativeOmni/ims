<?php

namespace Tests\Feature;

use App\Models\HafalanRecord;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class RincianBarisCommandTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setor(string $date, int $start, int $end, ?float $baris = null): void
    {
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $header->surahs()->create(['surah_id' => $this->surah->id, 'ayah_start' => $start, 'ayah_end' => $end, 'status' => 'passed', 'submission_type' => 'new', 'baris' => $baris]);
    }

    #[Test]
    public function it_splits_term_lines_into_new_repeated_and_duplicate(): void
    {
        $this->setUpHafizPlusData();
        Carbon::setTestNow('2026-10-08');
        $this->setor('2026-06-20', 1, 3, 3);   // sebelum triwulan: sudah lulus
        $this->setor('2026-07-10', 1, 3, 3);   // ulang
        $this->setor('2026-07-11', 4, 7, 4);   // baru
        $this->setor('2026-07-11', 4, 7, 4);   // ganda

        $this->artisan('tad:rincian-baris', ['murid' => $this->student->name, '--tahun' => '2026/2027', '--term' => 1])
            ->expectsOutputToContain('ULANG (ayat sudah pernah lulus)')
            ->expectsOutputToContain('GANDA (sama persis di tanggal ini)')
            ->expectsOutputToContain('Asal baris triwulan ini: ayat baru 4 · ulangan 3 · ganda 4')
            ->assertSuccessful();
    }
}
