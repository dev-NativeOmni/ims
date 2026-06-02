<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuranPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
        ]);
    }

    public function test_guest_cannot_access_quran_pdf(): void
    {
        $response = $this->get(route('quran.pdf'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_quran_pdf(): void
    {
        $studentUser = User::where('username', 'santri')->first();
        $this->assertNotNull($studentUser);

        $response = $this->actingAs($studentUser)->get(route('quran.pdf'));
        $response->assertStatus(200);
        $response->assertSee('Mushaf Al-Qur\'an Digital');
    }

    public function test_non_admin_cannot_upload_quran_pdf(): void
    {
        $studentUser = User::where('username', 'santri')->first();
        $file = \Illuminate\Http\UploadedFile::fake()->create('custom_quran.pdf', 500, 'application/pdf');

        $response = $this->actingAs($studentUser)->post(route('quran.pdf.upload'), [
            'pdf_file' => $file,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_upload_quran_pdf(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $file = \Illuminate\Http\UploadedFile::fake()->create('custom_quran.pdf', 500, 'application/pdf');

        $response = $this->actingAs($superAdmin)->post(route('quran.pdf.upload'), [
            'pdf_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertFileExists(public_path('pdf/quran.pdf'));

        @unlink(public_path('pdf/quran.pdf'));
    }
}
