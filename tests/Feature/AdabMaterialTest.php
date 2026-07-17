<?php

namespace Tests\Feature;

use App\Models\AdabMaterial;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdabMaterialTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Create roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $teacherRole = Role::firstOrCreate(['name' => 'teacher'], ['display_name' => 'Guru']);
        $studentRole = Role::firstOrCreate(['name' => 'student'], ['display_name' => 'Santri']);

        // Create users
        $this->admin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'status' => 'active',
        ]);
        $this->teacher = User::factory()->create([
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);
        $this->student = User::factory()->create([
            'role_id' => $studentRole->id,
            'status' => 'active',
        ]);
    }

    public function test_guest_cannot_access_adab_materials_index()
    {
        $response = $this->get(route('adab-materials.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_all_authenticated_users_can_access_adab_materials_index()
    {
        $response = $this->actingAs($this->student)->get(route('adab-materials.index'));
        $response->assertStatus(200);

        $response2 = $this->actingAs($this->teacher)->get(route('adab-materials.index'));
        $response2->assertStatus(200);
    }

    public function test_authorized_user_can_create_adab_material_with_file_and_link()
    {
        $file = UploadedFile::fake()->create('materi-ikhlas.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->teacher)->post(route('adab-materials.store'), [
            'title' => 'Materi Adab Ikhlas',
            'description' => 'Panduan keikhlasan dalam belajar.',
            'file' => $file,
            'url_link' => 'https://youtube.com/watch?v=xyz',
        ]);

        $response->assertRedirect(route('adab-materials.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('adab_materials', [
            'title' => 'Materi Adab Ikhlas',
            'file_name' => 'materi-ikhlas.pdf',
            'url_link' => 'https://youtube.com/watch?v=xyz',
            'created_by' => $this->teacher->id,
        ]);

        $material = AdabMaterial::first();
        Storage::disk('public')->assertExists($material->file_path);
    }

    public function test_student_cannot_create_adab_material()
    {
        $response = $this->actingAs($this->student)->post(route('adab-materials.store'), [
            'title' => 'Materi Adab Ikhlas',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseEmpty('adab_materials');
    }

    public function test_manager_can_update_and_replace_file()
    {
        $oldFile = UploadedFile::fake()->create('old-adab.pdf', 50, 'application/pdf');
        $material = AdabMaterial::create([
            'title' => 'Judul Lama',
            'file_path' => $oldFile->store('adab-materials', 'public'),
            'file_name' => 'old-adab.pdf',
            'file_size' => 50000,
            'created_by' => $this->teacher->id,
        ]);

        Storage::disk('public')->assertExists($material->file_path);

        $newFile = UploadedFile::fake()->create('new-adab.pdf', 120, 'application/pdf');

        $response = $this->actingAs($this->admin)->put(route('adab-materials.update', $material), [
            'title' => 'Judul Baru',
            'file' => $newFile,
        ]);

        $response->assertRedirect(route('adab-materials.index'));
        $this->assertDatabaseHas('adab_materials', [
            'id' => $material->id,
            'title' => 'Judul Baru',
            'file_name' => 'new-adab.pdf',
        ]);

        // Old file must be deleted, new file must exist
        Storage::disk('public')->assertMissing($material->file_path);

        $updatedMaterial = AdabMaterial::first();
        Storage::disk('public')->assertExists($updatedMaterial->file_path);
    }

    public function test_manager_can_delete_adab_material_and_associated_file()
    {
        $file = UploadedFile::fake()->create('todelete.pdf', 50, 'application/pdf');
        $material = AdabMaterial::create([
            'title' => 'Hapus Saya',
            'file_path' => $file->store('adab-materials', 'public'),
            'file_name' => 'todelete.pdf',
            'file_size' => 50000,
            'created_by' => $this->teacher->id,
        ]);

        Storage::disk('public')->assertExists($material->file_path);

        $response = $this->actingAs($this->teacher)->delete(route('adab-materials.destroy', $material));
        $response->assertRedirect(route('adab-materials.index'));

        $this->assertDatabaseEmpty('adab_materials');
        Storage::disk('public')->assertMissing($material->file_path);
    }

    public function test_student_cannot_delete_adab_material()
    {
        $material = AdabMaterial::create([
            'title' => 'Materi Penting',
            'created_by' => $this->teacher->id,
        ]);

        $response = $this->actingAs($this->student)->delete(route('adab-materials.destroy', $material));
        $response->assertStatus(403);
        $this->assertDatabaseHas('adab_materials', ['id' => $material->id]);
    }
}
