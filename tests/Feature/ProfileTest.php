<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_admin_can_update_profile_name_and_username(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $response = $this
            ->actingAs($admin)
            ->patch('/profile', [
                'name' => 'Updated Admin',
                'username' => 'adminupdated',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $admin->refresh();

        $this->assertSame('Updated Admin', $admin->name);
        $this->assertSame('adminupdated', $admin->username);
    }

    public function test_non_admin_cannot_update_name_and_username_from_profile(): void
    {
        $teacherRole = Role::firstOrCreate(['name' => 'teacher'], ['display_name' => 'Guru']);
        $user = User::factory()->create([
            'role_id' => $teacherRole->id,
            'name' => 'Original Name',
            'username' => 'originaluser',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Hacker Name',
                'username' => 'hackerusername',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        // Name and username must remain unchanged
        $this->assertSame('Original Name', $user->name);
        $this->assertSame('originaluser', $user->username);
    }

    public function test_user_cannot_delete_their_own_account_from_profile(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response->assertStatus(403);
        $this->assertNotNull($user->fresh());
    }

    public function test_avatar_can_be_uploaded(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $tempFile = @tempnam(sys_get_temp_dir(), 'avatar');
        file_put_contents($tempFile, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
        $file = new UploadedFile($tempFile, 'avatar.png', 'image/png', null, true);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'avatar' => $file,
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');

        $user->refresh();
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_avatar_can_be_removed(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $tempFile = @tempnam(sys_get_temp_dir(), 'avatar');
        file_put_contents($tempFile, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
        $file = new UploadedFile($tempFile, 'avatar.png', 'image/png', null, true);

        $path = $file->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'remove_avatar' => 1,
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');

        $user->refresh();
        $this->assertNull($user->avatar);
        Storage::disk('public')->assertMissing($path);
    }
}
