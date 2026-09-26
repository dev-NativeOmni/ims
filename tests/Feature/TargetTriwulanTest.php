<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportController;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Role;
use App\Models\Surah;
use App\Models\User;
use App\Services\AutoHafalanTargetService;
use App\Support\HafalanOrder;
use App\Support\TargetRules;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Target Triwulan: target manual 3 bulan per murid, deadline di pertemuan aktif terakhir.
 */
class TargetTriwulanTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        // Surah Juz 28-30 dengan jumlah ayat dari batas juz.
        foreach ([28, 29, 30] as $juz) {
            foreach (HafalanOrder::JUZ_RANGES[$juz] as $range) {
                Surah::updateOrCreate(['number' => $range['surah']], [
                    'name_ar' => "S{$range['surah']}", 'name_latin' => "Surah {$range['surah']}", 'total_ayah' => $range['end'],
                ]);
            }
        }

        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $this->classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'XII F3', 'level' => 'XII', 'tahfizh_days' => [3]]);
        $this->student->update(['class_room_id' => $this->classRoom->id, 'tahfizh_level' => 'reguler']);
    }

    private function setoran(string $date, int $surahNumber, int $from, int $to): void
    {
        $record = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $record->surahs()->create([
            'surah_id' => Surah::where('number', $surahNumber)->value('id'),
            'ayah_start' => $from, 'ayah_end' => $to, 'submission_type' => 'new', 'status' => 'passed',
        ]);
    }

    /** Semua surah Juz 30 selain An-Naba sudah disetor sebelum triwulan. */
    private function finishJuz30ExceptAnNaba(): void
    {
        foreach (range(79, 114) as $number) {
            $this->setoran('2026-06-10', $number, 1, (int) Surah::where('number', $number)->value('total_ayah'));
        }
    }

    private function saveTerm(array $cells, $user = null)
    {
        return $this->actingAs($user ?? $this->teacherUser)->post(route('hafalan-targets.term.store'), [
            'period' => '2026-07-01',
            'class_room_id' => $this->classRoom->id,
            'targets' => [$this->student->id => $cells],
        ]);
    }

    private function surahId(int $number): int
    {
        return (int) Surah::where('number', $number)->value('id');
    }

    #[Test]
    public function term_page_shows_three_months_with_deadline_on_the_last_active_meeting(): void
    {
        $response = $this->actingAs($this->admin)->get(route('hafalan-targets.term', ['period' => '2026-07-01', 'class_room_id' => $this->classRoom->id]));

        $response->assertOk();
        $response->assertSee($this->student->name);
        $this->assertFalse($response->viewData('classRooms')->contains(fn ($c) => $c->isGradeTen()));

        // Kelas hanya Rabu: pertemuan terakhir 29 Jul, 26 Agu, 30 Sep 2026.
        $deadlines = collect($response->viewData('months'))->map(fn ($m) => $m['deadline']->toDateString())->all();
        $this->assertSame(['2026-07' => '2026-07-29', '2026-08' => '2026-08-26', '2026-09' => '2026-09-30'], $deadlines);
    }

    #[Test]
    public function teacher_fills_three_months_at_once_and_the_last_month_is_the_term_target(): void
    {
        $this->student->update(['teacher_id' => $this->teacherProfile->id]);
        $this->finishJuz30ExceptAnNaba();
        $this->setoran('2026-07-01', 78, 1, 40);

        $this->saveTerm([
            '2026-07' => ['surah_id' => $this->surahId(78), 'ayah' => 40],
            '2026-08' => ['surah_id' => $this->surahId(67), 'ayah' => 15],
            '2026-09' => ['surah_id' => $this->surahId(67), 'ayah' => 30],
        ])->assertRedirect(route('hafalan-targets.term', ['period' => '2026-07-01', 'class_room_id' => $this->classRoom->id]));

        $targets = HafalanTarget::where('student_id', $this->student->id)->orderBy('target_date')->get();
        $this->assertSame(['2026-07-29', '2026-08-26', '2026-09-30'], $targets->map(fn ($t) => $t->target_date->toDateString())->all());
        $this->assertTrue($targets->every(fn ($t) => $t->auto_month === null));
        $this->assertSame('completed', $targets[0]->status, 'An-Naba 1-40 sudah lulus disetor.');
        $this->assertSame('active', $targets[2]->status);

        $plan = app(AutoHafalanTargetService::class)->termPlan($this->student->fresh(), Carbon::parse('2026-08-01'));
        $this->assertSame(67, $plan['target']->surah->number);
        $this->assertSame(30, $plan['target']->ayah);
        $this->assertSame('2026-09', $plan['target_month']);

        // Laporan Triwulan memakai target yang sama.
        $response = $this->actingAs($this->admin)->get(route('reports.quarterly', ['class_room_id' => $this->classRoom->id, 'academic_year' => '2026/2027', 'term' => '1']));
        $termRecord = $response->viewData('halaqahData')[0]['term_records'][0];
        $this->assertSame('Surah 67', $termRecord['target_surah']);
    }

    #[Test]
    public function saving_again_updates_the_same_month_target_and_empty_cells_delete_it(): void
    {
        $this->student->update(['teacher_id' => $this->teacherProfile->id]);
        // Target otomatis lama di Juli ikut diambil alih menjadi target guru.
        $old = HafalanTarget::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surahId(78), 'ayah' => 10, 'target_date' => '2026-07-31', 'status' => 'active', 'auto_month' => '2026-07',
        ]);

        $this->saveTerm([
            '2026-07' => ['surah_id' => $this->surahId(78), 'ayah' => 20],
            '2026-08' => ['surah_id' => $this->surahId(78), 'ayah' => 30],
        ])->assertSessionHasNoErrors();

        $old->refresh();
        $this->assertSame(20, $old->ayah);
        $this->assertNull($old->auto_month);
        $this->assertSame('2026-07-29', $old->target_date->toDateString());
        $this->assertSame(2, HafalanTarget::where('student_id', $this->student->id)->count());

        $this->saveTerm([
            '2026-07' => ['surah_id' => $this->surahId(78), 'ayah' => 20],
            '2026-08' => ['surah_id' => '', 'ayah' => ''],
        ]);
        $this->assertSame(1, HafalanTarget::where('student_id', $this->student->id)->count());
    }

    #[Test]
    public function invalid_ayah_rejects_the_whole_form(): void
    {
        $this->student->update(['teacher_id' => $this->teacherProfile->id]);
        $maxAyah = (int) Surah::where('number', 78)->value('total_ayah');

        $this->saveTerm([
            '2026-07' => ['surah_id' => $this->surahId(78), 'ayah' => 10],
            '2026-08' => ['surah_id' => $this->surahId(78), 'ayah' => $maxAyah + 1],
        ])->assertSessionHasErrors("targets.{$this->student->id}.2026-08");

        $this->assertSame(0, HafalanTarget::where('student_id', $this->student->id)->count());
    }

    #[Test]
    public function only_staff_who_can_create_targets_for_the_class_can_save(): void
    {
        $this->student->update(['teacher_id' => null]);
        $this->saveTerm(['2026-07' => ['surah_id' => $this->surahId(78), 'ayah' => 10]])->assertForbidden();

        $role = Role::firstOrCreate(['name' => 'headmaster'], ['display_name' => 'Kepala Sekolah']);
        $headmaster = User::factory()->create(['role_id' => $role->id, 'status' => 'active']);
        $this->saveTerm(['2026-07' => ['surah_id' => $this->surahId(78), 'ayah' => 10]], $headmaster)->assertForbidden();

        $this->assertSame(0, HafalanTarget::count());
    }

    #[Test]
    public function staff_can_switch_a_students_direction_from_the_term_page(): void
    {
        $this->student->update(['teacher_id' => $this->teacherProfile->id]);

        $this->actingAs($this->teacherUser)
            ->patch(route('hafalan-targets.direction', $this->student), ['hafalan_direction' => 'front_29', 'period' => '2026-07-01'])
            ->assertRedirect();

        $this->assertSame('front_29', $this->student->fresh()->hafalan_direction);
    }

    #[Test]
    public function staff_cannot_switch_direction_for_students_they_cannot_see(): void
    {
        $this->student->update(['teacher_id' => null]);

        $this->actingAs($this->teacherUser)
            ->patch(route('hafalan-targets.direction', $this->student), ['hafalan_direction' => 'front_29'])
            ->assertForbidden();
        $this->assertSame('backward', $this->student->fresh()->hafalan_direction);
    }

    #[Test]
    public function target_rules_are_editable_and_drive_the_calculation(): void
    {
        $this->finishJuz30ExceptAnNaba();
        $this->setoran('2026-07-01', 78, 1, 5);

        $this->actingAs($this->admin)->post(route('settings.target-rules.update'), [
            'level_lines' => ['tahsin' => 2, 'reguler' => 4, 'akselerasi' => 8],
            'mandatory_until' => 30, 'latest_switch' => 28,
        ])->assertRedirect(route('settings.hafalan-targets'));

        $this->assertSame(4, TargetRules::linesForLevel('reguler'));
        $this->assertSame([30, 29, 28], TargetRules::switchOptions());
        $this->assertArrayHasKey('front_30', HafalanOrder::directionOptions());
        $this->assertSame([30, 1, 2], array_slice(HafalanOrder::juzSequence('front_30'), 0, 3), 'Pindah setelah Juz 30 langsung ke Juz 1.');

        $this->actingAs($this->teacherUser)->post(route('settings.target-rules.update'), [
            'level_lines' => ['tahsin' => 1, 'reguler' => 1, 'akselerasi' => 1], 'mandatory_until' => 29, 'latest_switch' => 27,
        ])->assertForbidden();
        $this->actingAs($this->admin)->post(route('settings.target-rules.update'), [
            'level_lines' => ['tahsin' => 3, 'reguler' => 5, 'akselerasi' => 7], 'mandatory_until' => 27, 'latest_switch' => 29,
        ])->assertSessionHasErrors('latest_switch');
    }

    private function lines(int $surah, int $from, int $to): float
    {
        return ReportController::calculateLines($surah, $from, $to, (int) Surah::where('number', $surah)->value('total_ayah'));
    }

    private function target(string $date, int $surah, int $ayah): HafalanTarget
    {
        return HafalanTarget::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surahId($surah), 'ayah' => $ayah, 'target_date' => $date, 'status' => 'active',
        ]);
    }

    #[Test]
    public function target_lines_are_active_meetings_times_level_and_the_teachers_target_is_the_direction(): void
    {
        $this->finishJuz30ExceptAnNaba();
        $this->setoran('2026-06-20', 78, 1, 10);  // riwayat sebelum triwulan
        $this->setoran('2026-08-05', 78, 21, 30); // setoran pertama triwulan
        $this->target('2026-09-30', 78, 40);

        $plan = app(AutoHafalanTargetService::class)->termPlan($this->student->fresh(), Carbon::parse('2026-07-01'));

        // Kelas Rabu saja, reguler 5 baris: Jul 5, Agu 4, Sep 5 pertemuan.
        $this->assertSame([25, 20, 25], array_values(array_map(fn ($m) => $m['target_lines'], $plan['months'])));
        $this->assertSame(70, $plan['target_lines'], 'Target baris tidak bergantung pada jauhnya surah target.');
        $this->assertSame(round($this->lines(78, 21, 30), 1), $plan['achieved_lines']);
        $this->assertFalse($plan['reached']);
        $this->assertSame(21, $plan['start']['ayah'], 'Titik awal = setoran pertama triwulan.');

        $response = $this->actingAs($this->admin)->get(route('reports.quarterly', ['class_room_id' => $this->classRoom->id, 'academic_year' => '2026/2027', 'term' => '1']));
        $termRecord = $response->viewData('halaqahData')[0]['term_records'][0];
        $this->assertSame('40', $termRecord['target_ayat'], 'Target cukup ayat akhir.');
        $this->assertSame(70, $termRecord['target_lines']);

        // Capaian baris (baris tersimpan) mencapai 70 -> tuntas.
        $big = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => '2026-09-09']);
        $big->surahs()->create(['surah_id' => $this->surahId(78), 'ayah_start' => 31, 'ayah_end' => 40, 'submission_type' => 'new', 'status' => 'passed', 'baris' => 70]);
        $this->assertTrue(app(AutoHafalanTargetService::class)->termPlan($this->student->fresh(), Carbon::parse('2026-07-01'))['reached']);
    }

    #[Test]
    public function capaian_is_the_sum_of_baris_of_passed_setoran_like_the_setoran_tab(): void
    {
        $this->finishJuz30ExceptAnNaba();
        $this->setoran('2026-06-20', 78, 1, 20);
        $this->setoran('2026-07-08', 78, 1, 20); // mengulang ayat lama: ikut dihitung
        $withBaris = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => '2026-07-09']);
        $withBaris->surahs()->create(['surah_id' => $this->surahId(78), 'ayah_start' => 21, 'ayah_end' => 25, 'submission_type' => 'new', 'status' => 'passed', 'baris' => 4]);
        $failed = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => '2026-07-15']);
        $failed->surahs()->create(['surah_id' => $this->surahId(78), 'ayah_start' => 26, 'ayah_end' => 30, 'submission_type' => 'new', 'status' => 'repeat']);
        $this->target('2026-09-30', 78, 40);

        $plan = app(AutoHafalanTargetService::class)->termPlan($this->student->fresh(), Carbon::parse('2026-07-01'));

        // Baris tersimpan (4) dipakai apa adanya; setoran "ulang" tidak dihitung.
        $this->assertSame(round($this->lines(78, 1, 20) + 4, 1), $plan['achieved_lines']);
    }

    #[Test]
    public function periodic_monthly_report_shows_only_that_months_part_not_the_cumulative_term(): void
    {
        $this->student->update(['teacher_id' => $this->teacherProfile->id]);
        $this->finishJuz30ExceptAnNaba();
        $this->setoran('2026-07-08', 78, 1, 20);
        $this->setoran('2026-09-09', 78, 21, 30);
        $this->target('2026-07-29', 78, 20);
        $this->target('2026-09-30', 78, 40);

        $row = fn (int $month) => collect($this->actingAs($this->admin)->get(route('reports.periodic', [
            'class_room_id' => $this->classRoom->id, 'period_type' => 'monthly', 'month' => $month, 'year' => 2026,
        ]))->viewData('studentReports'))->first(fn ($r) => $r['student']->id === $this->student->id);

        $september = $row(9);
        $this->assertSame(25, $september['target_baris'], 'September saja: 5 pertemuan x 5 baris.');
        $this->assertSame(round($this->lines(78, 21, 30), 1), (float) $september['capaian_baris']);
        $this->assertSame('Surah 78', $september['target_surah']);

        $august = $row(8);
        $this->assertSame(20, $august['target_baris']);
        $this->assertSame(0.0, (float) $august['capaian_baris']);
        $this->assertFalse($august['is_tuntas']);
    }

    #[Test]
    public function quarterly_report_uses_the_same_numbers_as_the_target_page(): void
    {
        $this->finishJuz30ExceptAnNaba();
        $this->setoran('2026-07-08', 78, 11, 25);
        $this->target('2026-07-29', 78, 25);
        $this->target('2026-09-30', 78, 40);

        $plan = app(AutoHafalanTargetService::class)->termPlan($this->student->fresh(), Carbon::parse('2026-07-01'));

        $response = $this->actingAs($this->admin)->get(route('reports.quarterly', ['class_room_id' => $this->classRoom->id, 'academic_year' => '2026/2027', 'term' => '1']));
        $halaqah = $response->viewData('halaqahData')[0];
        $termRecord = $halaqah['term_records'][0];
        $this->assertSame($plan['target_lines'], $termRecord['target_lines']);
        $this->assertSame($plan['achieved_lines'], $termRecord['total_lines']);
        $this->assertFalse($termRecord['is_tuntas']);

        $july = collect($halaqah['monthly']['07']['reguler_records'] ?: $halaqah['monthly']['07']['tahfizh_records'])->firstWhere('student_id', $this->student->id);
        $this->assertSame(25, $july['target_lines']);
        $this->assertSame('Surah 78', $july['target_surah']);
    }

    #[Test]
    public function periodic_term_report_has_stacked_monthly_capaian_and_period_titles(): void
    {
        $this->student->update(['teacher_id' => $this->teacherProfile->id]);
        $this->finishJuz30ExceptAnNaba();
        $this->setoran('2026-07-08', 78, 1, 20);
        $this->setoran('2026-09-09', 78, 21, 30);
        $this->target('2026-09-30', 78, 40);

        $response = $this->actingAs($this->admin)->get(route('reports.periodic', [
            'class_room_id' => $this->classRoom->id, 'period_type' => 'quarterly', 'quarter' => 1, 'year' => 2026,
        ]));
        $response->assertOk();
        $response->assertSee('GRAFIK CAPAIAN TERM 1 (JULI – SEPTEMBER 2026) KELAS XII F3');
        $response->assertSee('KETUNTASAN TERM 1 (JULI – SEPTEMBER 2026) KELAS XII F3');

        $row = collect($response->viewData('studentReports'))->first(fn ($r) => $r['student']->id === $this->student->id);
        $this->assertSame(round($this->lines(78, 1, 20), 1), $row['monthly_capaian']['2026-07']);
        $this->assertSame(0.0, $row['monthly_capaian']['2026-08']);
        $this->assertSame(70, $row['target_baris'], 'Target term = 14 pertemuan x 5 baris.');

        $monthly = $this->actingAs($this->admin)->get(route('reports.periodic', [
            'class_room_id' => $this->classRoom->id, 'period_type' => 'monthly', 'month' => 8, 'year' => 2026,
        ]));
        $monthly->assertSee('GRAFIK CAPAIAN BULAN AGUSTUS 2026 KELAS XII F3');
    }
}
