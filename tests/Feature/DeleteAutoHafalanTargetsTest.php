<?php

namespace Tests\Feature;

use App\Models\HafalanTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class DeleteAutoHafalanTargetsTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private function target(?string $autoMonth, bool $trashed = false): HafalanTarget
    {
        $target = HafalanTarget::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id, 'ayah' => 5, 'target_date' => '2026-09-30', 'status' => 'active',
            'auto_month' => $autoMonth,
        ]);
        if ($trashed) {
            $target->delete();
        }

        return $target;
    }

    #[Test]
    public function only_system_targets_are_deleted_and_dry_run_changes_nothing(): void
    {
        $this->setUpHafizPlusData();
        $this->target('2026-07');
        $this->target('2026-08', trashed: true);
        $teacher = $this->target(null);

        $this->artisan('tad:hapus-target-otomatis', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(3, HafalanTarget::withTrashed()->count());

        $this->artisan('tad:hapus-target-otomatis', ['--force' => true])->assertSuccessful();
        $this->assertSame([$teacher->id], HafalanTarget::withTrashed()->pluck('id')->all());
    }
}
