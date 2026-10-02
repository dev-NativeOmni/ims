<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TargetLock;
use App\Models\User;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Kunci target per kelas per bulan: Admin & Koordinator Tahfizh mengunci, hanya Super Admin membuka.
 * Bulan terkunci: isian target beku di semua jalur simpan, status tuntas tetap otomatis.
 */
class TargetLockTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();
        // Sebelum data dibuat: riwayat kelas murid tercatat sejak awal tahun ajaran 2026/2027.
        Carbon::setTestNow('2026-09-10 10:00:00');
        $this->setUpHafizPlusData();

        foreach (HafalanOrder::JUZ_RANGES[30] as $range) {
            Surah::updateOrCreate(['number' => $range['surah']], ['name_ar' => "S{$range['surah']}", 'name_latin' => "Surah {$range['surah']}", 'total_ayah' => $range['end']]);
        }

        $program = Program::create(['name' => 'Program Tahfizh', 'status' => 'active']);
        $this->classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'XII F3', 'level' => 'XII', 'tahfizh_days' => [3]]);
        $this->student->update(['class_room_id' => $this->classRoom->id, 'tahfizh_level' => 'reguler']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        TargetLock::flushCache();
        parent::tearDown();
    }

    private function surahId(int $number): int
    {
        return (int) Surah::where('number', $number)->value('id');
    }

    private function lock(?User $user = null, string $month = '2026-08', ?int $classId = null)
    {
        TargetLock::flushCache();

        return $this->actingAs($user ?? $this->admin)->post(route('hafalan-targets.lock'), ['class_room_id' => $classId ?? $this->classRoom->id, 'month' => $month]);
    }

    private function target(string $date, array $values = []): HafalanTarget
    {
        return HafalanTarget::create($values + [
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surahId(78), 'ayah' => 10, 'target_date' => $date, 'status' => 'active',
        ]);
    }

    #[Test]
    public function admin_and_coordinator_lock_only_super_admin_unlocks(): void
    {
        $coordinator = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'coordinator_tahfizh'], ['display_name' => 'Koordinator Tahfizh'])->id, 'status' => 'active']);

        $this->lock($this->teacherUser)->assertForbidden();
        $this->lock($coordinator)->assertSessionHas('success');
        $this->lock($this->admin, '2026-09')->assertSessionHas('success');
        $this->assertSame(2, TargetLock::count());
        $this->assertSame($coordinator->id, TargetLock::whereDate('month', '2026-08-01')->value('locked_by'));

        $this->actingAs($this->admin)->delete(route('hafalan-targets.unlock'), ['class_room_id' => $this->classRoom->id, 'month' => '2026-08'])->assertForbidden();
        $this->actingAs($this->superAdmin)->delete(route('hafalan-targets.unlock'), ['class_room_id' => $this->classRoom->id, 'month' => '2026-08'])->assertSessionHas('success');
        $this->assertSame(['2026-09'], TargetLock::all()->map(fn ($l) => $l->month->format('Y-m'))->all());
    }

    #[Test]
    public function term_targets_of_a_locked_month_are_frozen(): void
    {
        $august = $this->target('2026-08-31');
        $this->lock();

        $this->actingAs($this->teacherUser)->post(route('hafalan-targets.term.store'), [
            'period' => '2026-07-01', 'class_room_id' => $this->classRoom->id,
            'targets' => [$this->student->id => [
                '2026-08' => ['surah_id' => '', 'ayah' => ''],
                '2026-09' => ['surah_id' => $this->surahId(78), 'ayah' => '20'],
            ]],
        ])->assertSessionHas('success');

        $this->assertNotSoftDeleted($august); // Agustus terkunci: tidak terhapus walau dikosongkan.
        $this->assertSame(1, HafalanTarget::whereMonth('target_date', 9)->count(), 'September tidak terkunci: tersimpan.');

        $page = $this->actingAs($this->admin)->get(route('hafalan-targets.term', ['period' => '2026-07-01', 'class_room_id' => $this->classRoom->id]));
        $page->assertOk()->assertSee('Terkunci')->assertSee('Admin Test')->assertSee('Kunci bulan ini');
        $this->assertTrue($page->viewData('locks')->has('2026-08'));
        $page->assertDontSee('Buka kunci', false);
        $this->actingAs($this->superAdmin)->get(route('hafalan-targets.term', ['period' => '2026-07-01', 'class_room_id' => $this->classRoom->id]))->assertSee('Buka kunci');
    }

    #[Test]
    public function single_edit_and_delete_of_a_locked_month_are_rejected(): void
    {
        $target = $this->target('2026-08-31');
        $this->lock();

        $this->actingAs($this->teacherUser)->put(route('hafalan-targets.update', $target), [
            'surah_id' => $this->surahId(78), 'ayah' => 30, 'target_date' => '2026-08-31', 'status' => 'active',
        ])->assertSessionHasErrors('target_date');
        $this->assertSame(10, $target->fresh()->ayah);

        // Memindahkan target ke bulan terkunci juga ditolak.
        $september = $this->target('2026-09-30');
        $this->actingAs($this->teacherUser)->put(route('hafalan-targets.update', $september), [
            'surah_id' => $this->surahId(78), 'ayah' => 10, 'target_date' => '2026-08-28', 'status' => 'active',
        ])->assertSessionHasErrors('target_date');

        $this->actingAs($this->teacherUser)->delete(route('hafalan-targets.destroy', $target))->assertSessionHas('error');
        $this->actingAs($this->admin)->post(route('hafalan-targets.bulk-destroy'), ['target_ids' => [$target->id, $september->id]])->assertSessionHas('success');
        $this->assertNotSoftDeleted($target);
        $this->assertSoftDeleted($september); // Bulan tidak terkunci tetap bisa dihapus.
    }

    #[Test]
    public function status_stays_automatic_while_locked(): void
    {
        $target = $this->target('2026-08-31');
        $this->lock();

        $this->artisan('tad:sync-completed-targets')->assertSuccessful();

        $this->assertSame('missed', $target->fresh()->status, 'Deadline lewat tanpa capaian: Terlewat walau terkunci.');
    }

    #[Test]
    public function ummi_cells_of_a_locked_month_are_frozen(): void
    {
        $gradeTen = ClassRoom::create(['program_id' => $this->classRoom->program_id, 'name' => 'X E1', 'level' => 'X', 'tahfizh_days' => [1, 2, 3, 4]]);
        $this->student->update(['class_room_id' => $gradeTen->id, 'tahfizh_level' => 'ummi']);
        $other = Student::create([
            'class_room_id' => $gradeTen->id, 'teacher_id' => $this->teacherProfile->id, 'name' => 'Murid Kedua',
            'student_number' => 'U-2', 'gender' => 'male', 'birth_date' => '2010-01-01', 'status' => 'active', 'tahfizh_level' => 'ummi',
        ]);
        $this->target('2026-08-31', ['surah_id' => null, 'ayah' => null, 'ummi_jilid' => 'Jilid 1', 'halaman_buku' => '20']);
        $this->lock($this->admin, '2026-08', $gradeTen->id);

        $this->actingAs($this->teacherUser)->post(route('hafalan-targets.ummi.store'), [
            'period' => '2026-07-01', 'teacher_id' => $this->teacherProfile->id, 'class_room_id' => $gradeTen->id,
            'targets' => [
                $this->student->id => ['2026-08' => ['jilid' => 'Jilid 2', 'halaman' => '5'], '2026-09' => ['jilid' => 'Jilid 2', 'halaman' => '20']],
                $other->id => ['2026-08' => ['jilid' => 'Jilid 2', 'halaman' => '5']],
            ],
        ])->assertSessionHas('success');

        $this->assertSame('20', HafalanTarget::where('student_id', $this->student->id)->whereMonth('target_date', 8)->value('halaman_buku'));
        $this->assertSame(0, HafalanTarget::where('student_id', $other->id)->count());
        $this->assertSame('20', HafalanTarget::where('student_id', $this->student->id)->whereMonth('target_date', 9)->value('halaman_buku'));

        $page = $this->actingAs($this->admin)->get(route('hafalan-targets.ummi', ['period' => '2026-07-01', 'teacher_id' => $this->teacherProfile->id, 'class_room_id' => $gradeTen->id]));
        $page->assertOk()->assertSee('Terkunci')->assertSee('Kunci bulan ini');
        $this->assertTrue($page->viewData('locks')->get($gradeTen->id)->has('2026-08'));
    }
}
