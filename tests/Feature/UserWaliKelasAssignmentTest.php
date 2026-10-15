<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Edit User: guru yang merangkap Wali Kelas memilih kelas tanggung jawabnya (class_rooms.wali_kelas_user_id).
 */
class UserWaliKelasAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $teacher;

    private ClassRoom $classA;

    private ClassRoom $classB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, UserSeeder::class, CoreDataSeeder::class]);
        $this->superAdmin = User::where('username', 'superadmin')->first();
        $this->teacher = User::where('username', 'guru')->first();
        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $this->classA = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI F2', 'level' => 'XI']);
        $this->classB = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI F3', 'level' => 'XI']);
    }

    private function save(array $additional, ?int $classId)
    {
        return $this->actingAs($this->superAdmin)->patch(route('users.update', $this->teacher), [
            'name' => $this->teacher->name,
            'username' => $this->teacher->username,
            'role_id' => Role::where('name', 'teacher')->value('id'),
            'additional_role_ids' => Role::whereIn('name', $additional)->pluck('id')->all(),
            'wali_kelas_class_id' => $classId,
            'status' => 'active',
        ])->assertRedirect(route('users.index'));
    }

    #[Test]
    public function edit_page_shows_the_wali_kelas_class_picker(): void
    {
        $this->actingAs($this->superAdmin)->get(route('users.edit', $this->teacher))
            ->assertOk()->assertSee('Kelas Wali Kelas')->assertSee('name="wali_kelas_class_id"', false);
    }

    #[Test]
    public function teacher_with_wali_kelas_side_role_gets_the_chosen_class_and_can_move_or_drop_it(): void
    {
        $this->save(['wali_kelas'], $this->classA->id);
        $this->assertSame($this->teacher->id, $this->classA->fresh()->wali_kelas_user_id);

        // Pindah kelas: kelas lama dilepas.
        $this->save(['wali_kelas'], $this->classB->id);
        $this->assertNull($this->classA->fresh()->wali_kelas_user_id);
        $this->assertSame($this->teacher->id, $this->classB->fresh()->wali_kelas_user_id);

        // Peran Wali Kelas dicabut: kelas dilepas walau pilihan kelas masih terkirim.
        $this->save([], $this->classB->id);
        $this->assertNull($this->classB->fresh()->wali_kelas_user_id);
    }

    #[Test]
    public function choosing_a_class_that_already_has_a_wali_replaces_the_old_one(): void
    {
        $other = User::factory()->create(['role_id' => Role::where('name', 'wali_kelas')->value('id'), 'status' => 'active']);
        $this->classA->update(['wali_kelas_user_id' => $other->id]);

        $this->save(['wali_kelas'], $this->classA->id);

        $this->assertSame($this->teacher->id, $this->classA->fresh()->wali_kelas_user_id);
    }
}
