<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Setiap pilihan surah di form & filter menampilkan jumlah ayatnya: "1. Al-Fatihah — 7 ayat".
 */
class SurahOptionLabelTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    #[Test]
    public function every_surah_picker_shows_the_number_of_ayah(): void
    {
        $this->setUpHafizPlusData();
        $program = Program::create(['name' => 'Program Tahfizh', 'status' => 'active']);
        $classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'X E1', 'level' => 'X', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'ummi']);

        $label = "{$this->surah->number}. {$this->surah->name_latin} — {$this->surah->total_ayah} ayat";
        $this->assertSame($label, $this->surah->option_label);

        // Target Triwulan khusus Kelas 11 & 12 (murid Ummi memakai Target Ummi).
        $classXI = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI F1', 'level' => 'XI', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        $this->student->update(['class_room_id' => $classXI->id, 'tahfizh_level' => 'reguler']);
        foreach ([
            route('hafalan-targets.index', ['class_room_id' => $classXI->id]),
            route('hafalan-targets.term', ['class_room_id' => $classXI->id]),
        ] as $url) {
            $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();
            $this->assertStringContainsString($label, $html, "Label surah tanpa jumlah ayat di {$url}");
        }

        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'ummi']);
        foreach ([
            route('hafalan-targets.ummi', ['class_room_id' => $classRoom->id]),
            route('spreadsheet-input.index', ['class_room_id' => $classRoom->id]),
            route('tahfizh-exams.create'),
            route('tahfizh-exams.index'),
            route('murajaah-records.index'),
            route('reports.index'),
        ] as $url) {
            $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();
            $this->assertStringContainsString($label, $html, "Label surah tanpa jumlah ayat di {$url}");
        }
    }
}
