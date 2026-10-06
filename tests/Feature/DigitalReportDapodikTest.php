<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Rapor cetak memakai NIS/NISN & rombel Dapodik; tampilan web tetap data pembelajaran.
 */
class DigitalReportDapodikTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function printed_rapor_uses_dapodik_identity_and_web_keeps_learning_data(): void
    {
        $this->student->update(['dapodik_nis' => '2610201', 'dapodik_nisn' => '0091234567', 'dapodik_rombel' => 'X E2']);
        $learningClass = $this->student->classRoom->name;

        $this->actingAs($this->admin)->get(route('digital-reports.print', $this->student))
            ->assertOk()
            ->assertSee('2610201 / 0091234567')
            ->assertSee('<td>X E2</td>', false)
            ->assertDontSee('<td>'.$learningClass.'</td>', false);

        $this->actingAs($this->admin)->get(route('digital-reports.show', $this->student))
            ->assertOk()
            ->assertSee($learningClass)
            ->assertDontSee('X E2');
    }

    #[Test]
    public function printed_rapor_falls_back_to_app_data_without_dapodik(): void
    {
        $this->actingAs($this->admin)->get(route('digital-reports.print', $this->student))
            ->assertOk()
            ->assertSee('<td>'.$this->student->student_number.'</td>', false)
            ->assertSee('<td>'.$this->student->classRoom->name.'</td>', false);

        $this->student->update(['dapodik_nisn' => '0091234567']);
        $this->assertSame('0091234567', $this->student->fresh()->nisNisn(), 'Hanya NISN = NISN saja.');
    }
}
