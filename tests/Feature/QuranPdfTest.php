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
        $response->assertRedirect(route('quran.mushaf'));
    }

    public function test_non_admin_cannot_save_drive_config(): void
    {
        $studentUser = User::where('username', 'santri')->first();

        $response = $this->actingAs($studentUser)->post(route('quran.pdf.config'), [
            'drive_link' => 'https://drive.google.com/file/d/1234567890abcdefghijklmnopqrstuv/view?usp=sharing',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_save_drive_config(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $configPath = storage_path('app/quran_settings.json');

        // Backup existing config if any
        $backup = null;
        if (file_exists($configPath)) {
            $backup = file_get_contents($configPath);
            unlink($configPath);
        }

        $response = $this->actingAs($superAdmin)->post(route('quran.pdf.config'), [
            'drive_link' => 'https://drive.google.com/file/d/1234567890abcdefghijklmnopqrstuv/view?usp=sharing',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertFileExists($configPath);
        $config = json_decode(file_get_contents($configPath), true);
        $this->assertEquals('1234567890abcdefghijklmnopqrstuv', $config['google_drive_id']);
        $this->assertEquals('https://drive.google.com/file/d/1234567890abcdefghijklmnopqrstuv/view?usp=sharing', $config['google_drive_link']);

        // Restore backup
        if ($backup !== null) {
            file_put_contents($configPath, $backup);
        } else {
            unlink($configPath);
        }
    }

    public function test_guest_cannot_access_mushaf(): void
    {
        $response = $this->get(route('quran.mushaf'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_mushaf(): void
    {
        $studentUser = User::where('username', 'santri')->first();
        $this->assertNotNull($studentUser);

        $response = $this->actingAs($studentUser)->get(route('quran.mushaf'));
        $response->assertStatus(200);
        $response->assertSee('Mushaf Al-Qur\'an Interaktif');
    }
}
