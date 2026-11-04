<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\User;
use App\Support\SidebarMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Setiap menu yang tampil harus bisa dibuka oleh role yang melihatnya: menu yang tampil tapi
 * 403/500 saat diklik adalah bug (lihat catatan di App\Support\SidebarMenu).
 */
class MenuAccessConsistencyTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    #[Test]
    public function every_visible_menu_opens_for_every_role(): void
    {
        $this->setUpHafizPlusData();
        $classX = ClassRoom::create(['program_id' => $this->student->classRoom->program_id, 'name' => 'X E1', 'level' => 'X', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        $this->student->update(['class_room_id' => $classX->id, 'tahfizh_level' => 'ummi', 'teacher_id' => $this->teacherProfile->id]);

        $broken = [];
        foreach (['super_admin', 'admin', 'teacher', 'supervisor', 'coordinator_tahfizh', 'headmaster', 'tanse', 'pendamping_adab', 'wali_kelas', 'parent', 'student'] as $roleName) {
            $user = match ($roleName) {
                'super_admin' => $this->superAdmin,
                'admin' => $this->admin,
                'teacher' => $this->teacherUser,
                'parent' => $this->parentUser,
                'student' => $this->studentUser,
                default => User::factory()->create([
                    'role_id' => Role::firstOrCreate(['name' => $roleName], ['display_name' => $roleName])->id,
                    'status' => 'active',
                ]),
            };

            $this->actingAs($user);
            foreach (SidebarMenu::for($user) as $group) {
                foreach ($group['items'] as $item) {
                    $status = $this->get($item['url'])->getStatusCode();
                    if ($status >= 400) {
                        $broken[] = "{$roleName}: {$group['label']} > {$item['label']} ({$status})";
                    }
                }
            }
        }

        $this->assertSame([], $broken);
    }
}
