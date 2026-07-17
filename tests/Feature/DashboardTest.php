<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    // =========================================================================
    // AKSES DASHBOARD — GUEST
    // =========================================================================

    #[Test]
    public function guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect('/login');
    }

    // =========================================================================
    // AKSES DASHBOARD — REDIRECT PER ROLE
    // =========================================================================

    #[Test]
    public function super_admin_is_redirected_to_super_admin_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard'));

        // Route 'dashboard' adalah redirect ke dashboard spesifik role
        $response->assertRedirect(route('super-admin.dashboard'));
    }

    #[Test]
    public function admin_is_redirected_to_admin_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertRedirect(route('admin.dashboard'));
    }

    #[Test]
    public function teacher_is_redirected_to_teacher_dashboard(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('dashboard'));

        $response->assertRedirect(route('teacher.dashboard'));
    }

    #[Test]
    public function parent_is_redirected_to_parent_dashboard(): void
    {
        $response = $this->actingAs($this->parentUser)->get(route('dashboard'));

        $response->assertRedirect(route('parent.dashboard'));
    }

    #[Test]
    public function student_is_redirected_to_student_dashboard(): void
    {
        $response = $this->actingAs($this->studentUser)->get(route('dashboard'));

        $response->assertRedirect(route('student.dashboard'));
    }

    // =========================================================================
    // AKSES DASHBOARD DETAIL — VIEW RENDER PER ROLE
    // =========================================================================

    #[Test]
    public function super_admin_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('super-admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboards.admin');
    }

    #[Test]
    public function admin_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboards.admin');
    }

    #[Test]
    public function teacher_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('teacher.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboards.teacher');
    }

    #[Test]
    public function parent_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->parentUser)->get(route('parent.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboards.parent');
    }

    #[Test]
    public function student_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->studentUser)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboards.student');
    }

    // =========================================================================
    // AKUN NONAKTIF
    // =========================================================================

    #[Test]
    public function inactive_user_is_logged_out_and_redirected_to_login(): void
    {
        // Nonaktifkan admin
        $this->admin->update(['status' => 'inactive']);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        // Harus dilogout dan dikembalikan ke login dengan pesan error
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }
}
