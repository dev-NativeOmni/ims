<?php

namespace Tests\Unit\Services;

use App\Models\HafalanRecord;
use App\Models\MurajaahRecord;
use App\Models\Surah;
use App\Services\StudentProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class StudentProgressServiceTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private StudentProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        $this->service = $this->app->make(StudentProgressService::class);
    }

    #[Test]
    public function it_returns_zero_progress_when_no_records_exist(): void
    {
        $progress = $this->service->calculate($this->student);

        $this->assertEquals(0, $progress['memorized_ayahs']);
        $this->assertEquals(0, $progress['progress_percent']);
        $this->assertEquals(0, $progress['total_hafalan_records']);
        $this->assertEquals(0, $progress['total_murajaah_records']);
        $this->assertEquals(0, $progress['average_hafalan_score']);
        $this->assertEquals(0, $progress['average_murajaah_score']);
    }

    #[Test]
    public function it_correctly_calculates_memorized_ayahs_for_single_non_overlapping_record(): void
    {
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'status' => 'passed',
            'score' => 80,
            'submitted_at' => now(),
        ]);

        $progress = $this->service->calculate($this->student);

        $this->assertEquals(3, $progress['memorized_ayahs']);
        $this->assertEquals(80, $progress['average_hafalan_score']);
        $this->assertEquals(1, $progress['total_hafalan_records']);
    }

    #[Test]
    public function it_merges_overlapping_ayah_ranges_in_same_surah(): void
    {
        // Setoran 1: Ayat 1 - 3
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'status' => 'passed',
            'score' => 80,
            'submitted_at' => now()->subDay(),
        ]);

        // Setoran 2: Ayat 2 - 5 (overlapping dengan setoran 1)
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 2,
            'ayah_end' => 5,
            'status' => 'passed',
            'score' => 90,
            'submitted_at' => now(),
        ]);

        $progress = $this->service->calculate($this->student);

        // Gabungan interval [1, 3] dan [2, 5] adalah [1, 5] = 5 ayat
        $this->assertEquals(5, $progress['memorized_ayahs']);
        $this->assertEquals(85, $progress['average_hafalan_score']);
        $this->assertEquals(2, $progress['total_hafalan_records']);
    }

    #[Test]
    public function it_does_not_count_non_passed_records_for_progress(): void
    {
        // Setoran 1: Ayat 1 - 3, status repeat (gagal)
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'status' => 'repeat',
            'score' => 50,
            'submitted_at' => now()->subDay(),
        ]);

        // Setoran 2: Ayat 4 - 7, status passed
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 4,
            'ayah_end' => 7,
            'status' => 'passed',
            'score' => 88,
            'submitted_at' => now(),
        ]);

        $progress = $this->service->calculate($this->student);

        // Hanya hitung yang 'passed' [4, 7] = 4 ayat
        $this->assertEquals(4, $progress['memorized_ayahs']);
        $this->assertEquals(69, $progress['average_hafalan_score']); // (50 + 88) / 2 = 69
        $this->assertEquals(2, $progress['total_hafalan_records']);
    }

    #[Test]
    public function it_calculates_progress_across_multiple_surahs(): void
    {
        // Surah 1 (Al-Fatihah, total 7 ayat): Ayat 1 - 7 (lulus)
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'status' => 'passed',
            'score' => 95,
            'submitted_at' => now()->subDay(),
        ]);

        // Buat surah kedua: Al-Baqarah (total 286 ayat)
        $surah2 = Surah::create([
            'number' => 2,
            'name_ar' => 'البقرة',
            'name_latin' => 'Al-Baqarah',
            'total_ayah' => 286,
            'juz_start' => 1,
            'juz_end' => 3,
        ]);

        // Setoran Surah 2: Ayat 1 - 10 (lulus)
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $surah2->id,
            'ayah_start' => 1,
            'ayah_end' => 10,
            'status' => 'passed',
            'score' => 85,
            'submitted_at' => now(),
        ]);

        $progress = $this->service->calculate($this->student);

        // Total memorized: 7 (Al-Fatihah) + 10 (Al-Baqarah) = 17 ayat
        $this->assertEquals(17, $progress['memorized_ayahs']);

        $totalQuranAyahs = 7 + 286; // Al-Fatihah + Al-Baqarah di DB
        $expectedPercent = round((17 / $totalQuranAyahs) * 100, 2);
        $this->assertEquals($expectedPercent, $progress['progress_percent']);
    }

    #[Test]
    public function it_correctly_calculates_average_murajaah_score(): void
    {
        MurajaahRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'status' => 'passed',
            'fluency_score' => 90,
            'tajwid_score' => 85,
            'makhraj_score' => 80,
            'overall_score' => 85,
            'reviewed_at' => now(),
        ]);

        MurajaahRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'status' => 'passed',
            'fluency_score' => 100,
            'tajwid_score' => 95,
            'makhraj_score' => 90,
            'overall_score' => 95,
            'reviewed_at' => now(),
        ]);

        $progress = $this->service->calculate($this->student);

        // Rata-rata overall_score: (85 + 95) / 2 = 90
        $this->assertEquals(90, $progress['average_murajaah_score']);
        $this->assertEquals(2, $progress['total_murajaah_records']);
    }
}
