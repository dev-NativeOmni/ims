<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\StudentPriorHafalan;
use App\Models\Surah;
use App\Support\HafalanOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Menu Hafalan & Arah Juz (Super Admin): tandai juz hafal & arah di juz yang sedang dihafal.
 */
class JuzMapTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        foreach ([28, 29, 30] as $juz) {
            foreach (HafalanOrder::JUZ_RANGES[$juz] as $range) {
                Surah::firstOrCreate(['number' => $range['surah']], [
                    'name_ar' => 'S'.$range['surah'], 'name_latin' => 'Surah '.$range['surah'], 'total_ayah' => $range['end'], 'juz_start' => $juz, 'juz_end' => $juz,
                ]);
            }
        }
        $class = ClassRoom::create(['program_id' => $this->student->classRoom->program_id, 'name' => 'XI F1', 'level' => 'XI']);
        $this->student->update(['class_room_id' => $class->id, 'tahfizh_level' => 'reguler']);
    }

    private function row()
    {
        return collect($this->actingAs($this->superAdmin)->get(route('juz-map.index', ['class_room_id' => $this->student->class_room_id]))
            ->assertOk()->viewData('rows'))->firstWhere('id', $this->student->id);
    }

    #[Test]
    public function only_super_admin_can_open_it(): void
    {
        $this->actingAs($this->admin)->get(route('juz-map.index'))->assertForbidden();
        $this->actingAs($this->superAdmin)->get(route('juz-map.index'))->assertOk()->assertSee('Hafalan &amp; Arah Juz', false);
    }

    #[Test]
    public function clicking_a_juz_marks_it_memorised_and_moves_the_current_juz(): void
    {
        $this->assertSame(30, $this->row()['current'], 'Murid baru: sedang di Juz 30.');

        $row = $this->actingAs($this->superAdmin)->postJson(route('juz-map.juz', $this->student), ['juz' => 30])->assertOk()->json();
        $juz30 = collect($row['juz'])->firstWhere('juz', 30);
        $this->assertSame(100, $juz30['percent']);
        $this->assertTrue($juz30['prior']);
        $this->assertSame(29, $row['current'], 'Juz 30 hafal: sekarang Juz 29.');

        // Klik lagi = batal.
        $row = $this->actingAs($this->superAdmin)->postJson(route('juz-map.juz', $this->student), ['juz' => 30])->assertOk()->json();
        $this->assertSame(0, collect($row['juz'])->firstWhere('juz', 30)['percent']);
        $this->assertSame(0, StudentPriorHafalan::count());
    }

    #[Test]
    public function partial_juz_shows_its_percentage_and_direction_can_be_set(): void
    {
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => now()->toDateString()]);
        $header->surahs()->create(['surah_id' => Surah::where('number', 78)->value('id'), 'ayah_start' => 1, 'ayah_end' => 40, 'status' => 'passed', 'submission_type' => 'new']);

        $juz30 = collect($this->row()['juz'])->firstWhere('juz', 30);
        $this->assertGreaterThan(0, $juz30['percent']);
        $this->assertLessThan(100, $juz30['percent']);
        $this->assertSame('asc', $juz30['order'], 'Kelas 11: dari awal juz.');

        $row = $this->actingAs($this->superAdmin)->postJson(route('juz-map.direction', $this->student), ['juz' => 30, 'order' => 'desc'])->assertOk()->json();
        $this->assertSame('desc', collect($row['juz'])->firstWhere('juz', 30)['order']);
        $this->assertSame(['30' => 'desc'], $this->student->fresh()->juz_orders);
    }
}
