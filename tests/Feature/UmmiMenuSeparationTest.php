<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\MurajaahRecord;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Support\ReadOnlyAccess;
use App\Support\SidebarMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Menu Metode Ummi terpisah dari menu Tahfizh: mode ditentukan oleh menu, Kelas 10 Program Tahfizh
 * punya lembar Setoran Mandiri & Muraja'ah, Kelas 10 lain hanya hafalan Ummi.
 */
class UmmiMenuSeparationTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $tahfizhTen;

    private ClassRoom $regulerTen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        $tahfizh = Program::create(['name' => 'Program Tahfizh', 'status' => 'active']);
        $reguler = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $this->tahfizhTen = ClassRoom::create(['program_id' => $tahfizh->id, 'name' => 'X E1', 'level' => 'X', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        $this->regulerTen = ClassRoom::create(['program_id' => $reguler->id, 'name' => 'X E2', 'level' => 'X', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
    }

    private function moveStudentTo(ClassRoom $classRoom): void
    {
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'ummi', 'teacher_id' => $this->teacherProfile->id]);
    }

    private function menuUrls(User $user): array
    {
        return collect(SidebarMenu::for($user))->flatMap(fn ($group) => collect($group['items'])->pluck('url'))->all();
    }

    #[Test]
    public function teacher_sees_the_ummi_group_only_with_ummi_students(): void
    {
        $this->assertNotContains(route('ummi.input'), $this->menuUrls($this->teacherUser));

        $this->moveStudentTo($this->tahfizhTen);
        $this->actingAs($this->teacherUser);

        $this->assertContains(route('ummi.input'), $this->menuUrls($this->teacherUser->fresh()));
    }

    #[Test]
    public function coordinator_menu_has_no_input_ummi_because_the_page_is_forbidden(): void
    {
        $role = Role::firstOrCreate(['name' => 'coordinator_tahfizh'], ['display_name' => 'Koordinator Tahfizh']);
        $coordinator = User::factory()->create(['role_id' => $role->id, 'status' => 'active']);

        $urls = $this->menuUrls($coordinator);
        $this->assertNotContains(route('ummi.input'), $urls);
        $this->assertContains(route('ummi.riwayat'), $urls);
        $this->actingAs($coordinator)->get(route('ummi.input'))->assertForbidden();
    }

    #[Test]
    public function headmaster_can_view_but_not_save_prosem(): void
    {
        $role = Role::firstOrCreate(['name' => 'headmaster'], ['display_name' => 'Kepala Sekolah']);
        $headmaster = User::factory()->create(['role_id' => $role->id, 'status' => 'active']);

        $this->assertContains(route('prosem-ummi.index'), $this->menuUrls($headmaster));
        $this->actingAs($headmaster)->get(route('prosem-ummi.index'))->assertOk()->assertDontSee('Simpan Prosem');
        $this->actingAs($headmaster)->post(route('prosem-ummi.update'), ['rows' => []])->assertForbidden();
    }

    #[Test]
    public function class_lists_are_split_between_the_two_menus(): void
    {
        $this->moveStudentTo($this->tahfizhTen);
        $classXI = ClassRoom::create(['program_id' => $this->tahfizhTen->program_id, 'name' => 'XI F1', 'level' => 'XI', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        Student::create(['class_room_id' => $classXI->id, 'teacher_id' => $this->teacherProfile->id, 'name' => 'Murid XI', 'status' => 'active', 'tahfizh_level' => 'reguler']);

        $ummi = $this->actingAs($this->admin)->get(route('ummi.input'))->assertOk()->viewData('classRooms');
        $this->assertTrue($ummi->contains('id', $this->tahfizhTen->id));

        $reguler = $this->actingAs($this->admin)->get(route('spreadsheet-input.index'))->assertOk()->viewData('classRooms');
        $this->assertFalse($reguler->contains('id', $this->tahfizhTen->id));
    }

    #[Test]
    public function mandiri_sheet_is_only_for_grade_ten_tahfizh(): void
    {
        $this->moveStudentTo($this->tahfizhTen);
        $this->assertTrue($this->actingAs($this->teacherUser)->get(route('ummi.input', ['class_room_id' => $this->tahfizhTen->id]))->viewData('showMandiriSheet'));

        $this->moveStudentTo($this->regulerTen);
        $this->assertFalse($this->actingAs($this->teacherUser)->get(route('ummi.input', ['class_room_id' => $this->regulerTen->id]))->viewData('showMandiriSheet'));
    }

    #[Test]
    public function grade_ten_outside_tahfizh_cannot_save_hafalan_sheet(): void
    {
        $this->moveStudentTo($this->regulerTen);

        $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), [
            'class_room_id' => $this->regulerTen->id, 'month' => '2026-09', 'type' => 'hafalan',
            'records' => [$this->student->id => ['dates' => ['2026-09-09' => [
                'attendance' => 'hadir',
                'hafalans' => [['id' => null, 'surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 3, 'status' => 'passed', 'submission_type' => 'new']],
            ]]]],
        ])->assertSessionHas('error');

        $this->assertSame(0, HafalanRecord::where('student_id', $this->student->id)->count());
    }

    #[Test]
    public function ummi_save_returns_to_the_ummi_page(): void
    {
        $this->moveStudentTo($this->tahfizhTen);

        $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), [
            'class_room_id' => $this->tahfizhTen->id, 'month' => '2026-09', 'type' => 'ummi', 'section' => 'ummi', 'records' => [],
        ])->assertRedirect(route('ummi.input', ['class_room_id' => $this->tahfizhTen->id, 'month' => '2026-09', 'week' => 'all']));
    }

    #[Test]
    public function old_ummi_url_redirects_and_trial_cannot_open_input(): void
    {
        $this->actingAs($this->admin)->get(route('hafalan-records.index', ['category' => 'ummi']))->assertRedirect(route('ummi.riwayat'));

        foreach (['ummi.input', 'ummi.input.single', 'ummi.murajaah'] as $route) {
            $this->assertTrue(ReadOnlyAccess::blocksRoute($route), $route);
        }
        $this->assertFalse(ReadOnlyAccess::blocksRoute('ummi.riwayat'));
    }

    #[Test]
    public function class_names_are_recognized_as_the_right_grade(): void
    {
        $program = $this->tahfizhTen->program_id;
        foreach ([
            ['X E1', 'X', 10], ['X-A', null, 10], ['X.E3', null, 10], ['XE1', null, 10], ['Kelas X', null, 10], ['Kelas 10', null, 10], ['10 A', null, 10], ['E1', 'X', 10], ['E1', '10', 10],
            ['XI F1', 'XI', 11], ['XI-A', null, 11], ['Kelas 11', null, 11], ['XII F1', 'XII', 11], ['12 B', null, 11], ['F1', 'XII', 11],
        ] as [$name, $level, $grade]) {
            $class = new ClassRoom(['program_id' => $program, 'name' => $name, 'level' => $level]);
            $this->assertSame($grade === 10, $class->isGradeTen(), "{$name} / {$level}: kelas 10");
            $this->assertSame($grade === 11, $class->isGradeElevenOrTwelve(), "{$name} / {$level}: kelas 11-12");
        }
    }

    #[Test]
    public function old_links_lead_to_the_right_menu(): void
    {
        $this->moveStudentTo($this->tahfizhTen);

        $this->actingAs($this->teacherUser)->get(route('spreadsheet-input.index', ['class_room_id' => $this->tahfizhTen->id, 'month' => '2026-09']))
            ->assertRedirect(route('ummi.input', ['class_room_id' => $this->tahfizhTen->id, 'month' => '2026-09']));
        $this->actingAs($this->teacherUser)->get(route('murajaah-records.fast-input'))->assertRedirect(route('ummi.murajaah'));
        $this->actingAs($this->teacherUser)->get(route('hafalan-targets.index', ['program' => 'ummi']))->assertRedirect(route('hafalan-targets.ummi'));
    }

    #[Test]
    public function saving_an_edit_without_changing_the_setoran_is_not_a_duplicate_of_itself(): void
    {
        $record = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => now()->toDateString()]);
        $record->surahs()->create(['surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 3, 'submission_type' => 'new', 'status' => 'passed']);

        $this->actingAs($this->teacherUser)->from(route('hafalan-records.edit', $record))->put(route('hafalan-records.update', $record), [
            'student_id' => $this->student->id, 'submitted_at' => now()->toDateString(), 'notes' => 'Diedit',
            'surah_ids' => [$this->surah->id], 'ayah_starts' => [1], 'ayah_ends' => [3], 'submission_types' => ['new'], 'statuses' => ['passed'],
        ])->assertRedirect(route('hafalan-records.index'))->assertSessionHasNoErrors();

        $this->assertSame('Diedit', $record->fresh()->notes);
    }

    #[Test]
    public function an_invalid_month_in_the_chart_url_is_not_a_server_error(): void
    {
        $this->actingAs($this->admin)->get('/reports/periodic?month=2026-11')->assertOk();
    }

    #[Test]
    public function teacher_with_only_grade_ten_is_sent_from_input_setoran_to_input_ummi(): void
    {
        $this->moveStudentTo($this->tahfizhTen);

        $this->actingAs($this->teacherUser)->get(route('spreadsheet-input.index'))->assertRedirect(route('ummi.input'));
    }

    #[Test]
    public function every_ummi_page_renders(): void
    {
        $this->moveStudentTo($this->tahfizhTen);

        foreach ([$this->admin, $this->teacherUser] as $user) {
            foreach ([
                route('ummi.input'), route('ummi.input', ['lembar' => 'mandiri']), route('ummi.input.single'), route('ummi.murajaah'),
                route('ummi.riwayat'), route('ummi.riwayat', ['lembar' => 'mandiri']), route('ummi.riwayat.murajaah'),
                route('ummi.progress'), route('hafalan-targets.ummi'), route('prosem-ummi.index'), route('ummi-flow.index'),
            ] as $url) {
                $this->actingAs($user)->get($url)->assertOk();
            }
        }
    }

    #[Test]
    public function progress_ummi_only_lists_ummi_students(): void
    {
        $classXI = ClassRoom::create(['program_id' => $this->tahfizhTen->program_id, 'name' => 'XI F1', 'level' => 'XI', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        Student::create(['class_room_id' => $classXI->id, 'teacher_id' => $this->teacherProfile->id, 'name' => 'Murid XI', 'status' => 'active', 'tahfizh_level' => 'reguler']);
        $this->moveStudentTo($this->tahfizhTen);

        $page = $this->actingAs($this->admin)->get(route('ummi.progress'))->assertOk();

        $this->assertSame([$this->tahfizhTen->id], $page->viewData('classRooms')->pluck('id')->intersect([$this->tahfizhTen->id, $classXI->id])->values()->all());
        $this->assertContains($this->student->id, collect($page->viewData('progressRows'))->pluck('student_id')->all());
        $this->assertNotContains('Murid XI', collect($page->viewData('progressRows'))->pluck('student_name')->all());
    }

    #[Test]
    public function riwayat_separates_grade_ten_from_reguler(): void
    {
        $this->moveStudentTo($this->tahfizhTen);
        $record = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => '2026-09-14']);
        $record->surahs()->create(['surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 3, 'submission_type' => 'new', 'status' => 'passed']);

        $reguler = $this->actingAs($this->admin)->get(route('hafalan-records.index'))->assertOk()->viewData('hafalanRecords');
        $this->assertFalse(collect($reguler->items())->contains('id', $record->id));

        $mandiri = $this->actingAs($this->admin)->get(route('ummi.riwayat', ['lembar' => 'mandiri']))->assertOk()->viewData('hafalanRecords');
        $this->assertTrue(collect($mandiri->items())->contains('id', $record->id));
    }

    #[Test]
    public function ummi_sheet_is_rejected_for_a_class_without_ummi(): void
    {
        $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), [
            'class_room_id' => $this->student->class_room_id, 'month' => '2026-09', 'type' => 'ummi', 'records' => [],
        ])->assertSessionHas('error');
    }

    #[Test]
    public function murajaah_is_rejected_for_grade_ten_outside_tahfizh(): void
    {
        $this->moveStudentTo($this->regulerTen);

        $this->actingAs($this->teacherUser)->postJson(route('murajaah-records.fast-store'), ['entries' => [[
            'student_id' => $this->student->id, 'surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 3, 'score' => 90,
        ]]])->assertStatus(422);

        $this->assertSame(0, MurajaahRecord::where('student_id', $this->student->id)->count());
    }

    #[Test]
    public function teacher_outside_tahfizh_does_not_see_mandiri_tabs(): void
    {
        $this->moveStudentTo($this->regulerTen);

        $this->actingAs($this->teacherUser)->get(route('ummi.riwayat'))->assertOk()->assertDontSee('Setoran Mandiri');
        $this->actingAs($this->teacherUser)->get(route('ummi.input'))->assertOk()->assertDontSee('Lembar Muraja');
    }

    #[Test]
    public function input_setoran_only_has_spreadsheet_and_murajaah_modes(): void
    {
        $this->actingAs($this->teacherUser)->get(route('hafalan-records.create'))->assertRedirect(route('spreadsheet-input.index'));
        $this->actingAs($this->teacherUser)->get(route('spreadsheet-input.index'))->assertOk()
            ->assertDontSee('Mode Satuan')->assertSee("Mode Muraja'ah");
        $this->actingAs($this->teacherUser)->get(route('hafalan-records.index'))->assertOk()->assertDontSee('Input Per Murid');

        $this->moveStudentTo($this->tahfizhTen);
        $this->actingAs($this->teacherUser)->get(route('ummi.input'))->assertOk()->assertSee('Mode Satuan');
    }

    private function periodicReportFor(ClassRoom $classRoom, string $level = 'reguler')
    {
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => $level, 'teacher_id' => $this->teacherProfile->id]);
        MurajaahRecord::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'surah_id' => $this->surah->id,
            'ayah_start' => 1, 'ayah_end' => 3, 'overall_score' => 90, 'status' => 'passed', 'reviewed_at' => '2026-09-14',
        ]);

        return $this->actingAs($this->admin)->get(route('reports.periodic', ['class_room_id' => $classRoom->id, 'period_type' => 'monthly', 'month' => 9, 'year' => 2026]))->assertOk();
    }

    #[Test]
    public function grade_eleven_tahfizh_chart_shows_murajaah_separately_from_capaian(): void
    {
        $classXI = ClassRoom::create(['program_id' => $this->tahfizhTen->program_id, 'name' => 'XI F2', 'level' => 'XI', 'tahfizh_days' => [1, 2, 3, 4, 5]]);

        $page = $this->periodicReportFor($classXI);
        $report = collect($page->viewData('studentReports'))->firstWhere('student.id', $this->student->id);

        $this->assertTrue($page->viewData('showMurajaahChart'));
        $this->assertGreaterThan(0, $report['murajaah_baris']);
        $this->assertEquals(0, $report['capaian_baris'], "Muraja'ah tidak boleh masuk capaian.");
        $page->assertSee('#d946ef', false);
    }

    #[Test]
    public function periodic_print_renders_for_a_class_with_students(): void
    {
        $classXI = ClassRoom::create(['program_id' => $this->tahfizhTen->program_id, 'name' => 'XI F2', 'level' => 'XI', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        $this->periodicReportFor($classXI);

        $this->actingAs($this->admin)->get(route('reports.periodic.print', ['class_room_id' => $classXI->id, 'period_type' => 'monthly', 'month' => 9, 'year' => 2026]))
            ->assertOk()->assertSee($this->student->name)->assertSee('#d946ef', false);
    }

    #[Test]
    public function murajaah_bar_is_only_for_the_tahfizh_program(): void
    {
        foreach ([
            'Program Tahfizh' => true,
            'TAHFIZH' => true,
            'Program Akselerasi' => false,
            'Tahfizh Akselerasi' => false,
            'Tahfizh Reguler' => false,
            'Program Reguler' => false,
        ] as $name => $expected) {
            $program = Program::create(['name' => $name, 'status' => 'active']);
            $class = ClassRoom::create(['program_id' => $program->id, 'name' => 'XII '.$name, 'level' => 'XII', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
            $this->student->update(['class_room_id' => $class->id, 'tahfizh_level' => 'reguler']);

            $page = $this->actingAs($this->admin)->get(route('reports.periodic', ['class_room_id' => $class->id, 'period_type' => 'monthly', 'month' => 9, 'year' => 2026]));

            $this->assertSame($expected, $page->assertOk()->viewData('showMurajaahChart'), $name);
        }
    }

    #[Test]
    public function chart_without_tahfizh_or_for_grade_ten_has_no_murajaah_bar(): void
    {
        $regulerXI = ClassRoom::create(['program_id' => $this->regulerTen->program_id, 'name' => 'XI F1', 'level' => 'XI', 'tahfizh_days' => [1, 2, 3, 4, 5]]);

        $this->assertFalse($this->periodicReportFor($regulerXI)->viewData('showMurajaahChart'));
        $this->assertFalse($this->periodicReportFor($this->tahfizhTen, 'ummi')->viewData('showMurajaahChart'));
    }

    #[Test]
    public function murajaah_sheet_only_lists_grade_ten_tahfizh(): void
    {
        $this->moveStudentTo($this->tahfizhTen);

        $classes = $this->actingAs($this->admin)->get(route('ummi.murajaah'))->assertOk()->viewData('classRooms');
        $this->assertTrue($classes->contains('id', $this->tahfizhTen->id));
        $this->assertFalse($classes->contains('id', $this->regulerTen->id));
    }
}
