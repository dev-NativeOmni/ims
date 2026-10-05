<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TahfizhExam;
use App\Models\User;
use App\Support\ReadOnlyAccess;
use App\Support\SidebarMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Role trial: pengunjung dari luar melihat aplikasi seperti Kepala Sekolah, tanpa bisa
 * menambah/mengubah/menghapus, unggah, unduh, ekspor, atau cetak (App\Support\ReadOnlyAccess).
 */
class TrialRoleTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private User $trial;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        $this->trial = User::where('username', 'trial')->firstOrFail();
    }

    #[Test]
    public function default_trial_account_logs_in_with_000000_and_lands_on_the_headmaster_dashboard(): void
    {
        $this->assertSame('trial', $this->trial->role->name);

        $this->post(route('login'), ['username' => 'trial', 'password' => '000000'])->assertRedirect();
        $this->assertAuthenticatedAs($this->trial);

        $this->get(route('dashboard'))->assertRedirect(route('headmaster.dashboard'));
        $this->get(route('headmaster.dashboard'))->assertOk()->assertSee('Mode Trial');
    }

    #[Test]
    public function trial_sees_view_pages_like_the_headmaster(): void
    {
        $this->assertTrue($this->trial->isReadOnly());
        $this->assertTrue($this->trial->hasRole('headmaster'));
        $this->assertStringStartsWith('Trial · ', $this->trial->currentRole()->display_name);

        foreach (['tahfizh-exams.index', 'hafalan-records.index', 'digital-reports.index', 'reports.periodic', 'adab.chart', 'student-points.chart'] as $route) {
            $this->actingAs($this->trial)->get(route($route))->assertOk();
        }
        $this->actingAs($this->trial)->get(route('digital-reports.show', $this->student))->assertOk();
    }

    #[Test]
    public function trial_cannot_write_anything(): void
    {
        $this->actingAs($this->trial)->post(route('tahfizh-exams.store'), [
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'type' => 'juz', 'juz' => 30, 'score' => 40, 'exam_date' => now()->toDateString(),
        ])->assertForbidden();
        $this->assertSame(0, TahfizhExam::count());

        $exam = TahfizhExam::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'juz' => 30, 'total_score' => 40, 'exam_date' => now()]);
        $this->actingAs($this->trial)->delete(route('tahfizh-exams.destroy', $exam))->assertForbidden();
        $this->actingAs($this->trial)->put(route('tahfizh-exams.update', $exam), [])->assertForbidden();
        $this->actingAs($this->trial)->postJson(route('digital-reports.update', $this->student), [])->assertForbidden();
        $this->assertModelExists($exam);
    }

    #[Test]
    public function trial_cannot_open_form_input_export_print_or_account_pages(): void
    {
        $blocked = [
            route('tahfizh-exams.create'),
            route('hafalan-records.create'),
            route('spreadsheet-input.index'),
            route('digital-reports.print', $this->student),
            route('reports.export.csv'),
            route('reports.quarterly.export'),
            route('reports.whatsapp'),
            route('profile.edit'),
        ];
        foreach ($blocked as $url) {
            $this->actingAs($this->trial)->from(route('tahfizh-exams.index'))->get($url)
                ->assertRedirect(route('tahfizh-exams.index'))
                ->assertSessionHas('read_only_blocked', true);
        }

        // Halaman di luar akses Kepala Sekolah tetap ditolak seperti biasa.
        $this->actingAs($this->trial)->get(route('students.index'))->assertForbidden();
        $this->actingAs($this->trial)->get(route('settings.tahfizh-scoring'))->assertForbidden();
    }

    #[Test]
    public function trial_can_still_log_out(): void
    {
        $this->actingAs($this->trial)->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    }

    #[Test]
    public function trial_menu_hides_input_pages_and_api_login_is_refused(): void
    {
        $urls = collect(SidebarMenu::for($this->trial))->flatMap(fn ($group) => collect($group['items'])->pluck('url'))->all();
        $headmasterUrls = collect(SidebarMenu::for($this->headmasterUser()))->flatMap(fn ($group) => collect($group['items'])->pluck('url'))->all();
        $this->assertContains(route('digital-reports.index'), $urls);
        $this->assertSame(
            collect($headmasterUrls)->reject(fn ($url) => str_contains($url, 'whatsapp') || str_contains($url, 'spreadsheet-input'))->values()->all(),
            $urls,
            'Menu trial = menu Kepala Sekolah tanpa halaman yang diblokir.'
        );
        $this->assertTrue(ReadOnlyAccess::blocksRoute('spreadsheet-input.index'));
        $this->assertFalse(ReadOnlyAccess::blocksRoute('tahfizh-exams.index'));

        $this->postJson('/api/v1/auth/login', ['username' => 'trial', 'password' => '000000', 'device_name' => 'Test'])
            ->assertForbidden();
    }

    private function headmasterUser(): User
    {
        return User::factory()->create(['role_id' => Role::where('name', 'headmaster')->value('id'), 'status' => 'active']);
    }

    #[Test]
    public function trial_can_view_as_a_leadership_role_without_using_another_account(): void
    {
        $this->actingAs($this->trial)->post(route('trial.view-as'), ['role' => 'admin'])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->trial);
        $this->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->get(route('students.index'))->assertOk();
        $this->assertTrue($this->trial->fresh()->hasRole('admin'));

        // Tetap lihat saja, dan halaman akun/sistem (daftar user memuat password) tertutup.
        $this->post(route('students.store'), ['name' => 'X'])->assertForbidden();
        $this->get(route('students.create'))->assertSessionHas('read_only_blocked', true);

        $this->post(route('trial.view-as'), ['role' => 'super_admin']);
        $this->get(route('settings.index'))->assertOk();
        $this->get(route('users.index'))->assertSessionHas('read_only_blocked', true);
        $this->get(route('audit-logs.index'))->assertSessionHas('read_only_blocked', true);
    }

    #[Test]
    public function trial_views_account_scoped_roles_through_a_read_only_sample_account(): void
    {
        $this->actingAs($this->trial)->post(route('trial.view-as'), ['role' => 'teacher'])->assertRedirect(route('dashboard'));

        // Masuk sebagai guru contoh (guru dengan murid bimbingan), tapi tetap lihat saja.
        $this->assertAuthenticatedAs($this->teacherUser);
        $this->get(route('dashboard'))->assertRedirect(route('teacher.dashboard'));
        $this->get(route('teacher.dashboard'))->assertOk()->assertSee('Mode Trial')->assertSee('akun contoh');
        $this->get(route('tahfizh-exams.index'))->assertOk();
        $this->post(route('tahfizh-exams.store'), [])->assertForbidden();
        $this->get(route('tahfizh-exams.create'))->assertSessionHas('read_only_blocked', true);
        $this->get(route('profile.edit'))->assertSessionHas('read_only_blocked', true);
        $this->post(route('role.switch'), ['role_id' => 1])->assertForbidden();

        // Ganti ke orangtua contoh, lalu kembali ke role pimpinan = kembali ke akun trial.
        $this->post(route('trial.view-as'), ['role' => 'parent']);
        $this->assertAuthenticatedAs($this->parentUser);
        $this->get(route('parent.dashboard'))->assertOk();

        $this->post(route('trial.view-as'), ['role' => 'headmaster']);
        $this->assertAuthenticatedAs($this->trial);
        $this->get(route('dashboard'))->assertRedirect(route('headmaster.dashboard'));
    }

    #[Test]
    public function only_trial_can_use_view_as(): void
    {
        $this->actingAs($this->admin)->post(route('trial.view-as'), ['role' => 'teacher'])->assertForbidden();
        $this->assertAuthenticatedAs($this->admin);
        $this->assertFalse(ReadOnlyAccess::applies($this->admin));
    }

    #[Test]
    public function other_roles_are_not_affected(): void
    {
        $this->assertFalse($this->admin->isReadOnly());
        $this->actingAs($this->admin)->get(route('tahfizh-exams.create'))->assertOk();
    }
}
