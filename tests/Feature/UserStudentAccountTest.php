<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/** Kelola User → akun murid langsung membuat data murid lengkap (kelas, guru, orangtua lama/baru). */
class UserStudentAccountTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private Role $studentRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        Role::firstOrCreate(['name' => 'parent'], ['display_name' => 'Orangtua']);
        $this->studentRole = Role::where('name', 'student')->firstOrFail();
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'name' => 'Wildan Zamorano', 'username' => 'zamorano', 'password' => 'rahasia123', 'status' => 'active',
            'role_id' => $this->studentRole->id,
            'student' => ['class_room_id' => $this->student->class_room_id, 'teacher_id' => $this->teacherProfile->id],
        ], $overrides);
    }

    #[Test]
    public function create_page_shows_student_fields(): void
    {
        $this->actingAs($this->superAdmin)->get(route('users.create'))
            ->assertOk()
            ->assertSee('Data Murid')
            ->assertSee('student[class_room_id]', false)
            ->assertSee('Orangtua/Wali baru');
    }

    #[Test]
    public function student_account_creates_complete_student_with_existing_and_new_parents(): void
    {
        $this->actingAs($this->superAdmin)->post(route('users.store'), $this->payload([
            'student' => ['student_number' => 'NIS-77', 'gender' => 'male'],
            'parent_ids' => [$this->parentProfile->id],
            'parent_relations' => [$this->parentProfile->id => 'ayah'],
            'new_parents' => [['name' => 'Ibu Zamorano', 'username' => 'ibuzamorano', 'password' => 'rahasia123', 'phone' => '0812', 'relation' => 'ibu']],
        ]))->assertRedirect(route('users.index'))->assertSessionHasNoErrors();

        $account = User::where('username', 'zamorano')->firstOrFail();
        $student = Student::where('user_id', $account->id)->firstOrFail();
        $this->assertSame([$this->student->class_room_id, $this->teacherProfile->id, 'NIS-77', 'male', 'reguler'],
            [$student->class_room_id, $student->teacher_id, $student->student_number, $student->gender, $student->tahfizh_level]);
        $this->assertSame(1, Student::where('name', 'Wildan Zamorano')->count());

        $newParent = User::where('username', 'ibuzamorano')->firstOrFail();
        $this->assertSame('parent', $newParent->role->name);
        $this->assertTrue(password_verify('rahasia123', $newParent->password));
        $this->assertSame('0812', $newParent->parentProfile->phone);

        $relations = $student->parents()->get()->mapWithKeys(fn (ParentProfile $p) => [$p->user->username => $p->pivot->relation])->all();
        $this->assertEquals([$this->parentUser->username => 'ayah', 'ibuzamorano' => 'ibu'], $relations);
    }

    #[Test]
    public function grade_ten_student_account_uses_ummi(): void
    {
        $classX = ClassRoom::create(['program_id' => $this->student->classRoom->program_id, 'name' => 'X E2', 'level' => 'X']);

        $this->actingAs($this->superAdmin)->post(route('users.store'), $this->payload(['student' => ['class_room_id' => $classX->id]]))
            ->assertSessionHasNoErrors();

        $this->assertSame('ummi', User::where('username', 'zamorano')->first()->studentProfile->tahfizh_level);
    }

    #[Test]
    public function same_name_student_without_account_in_that_class_is_linked_not_duplicated(): void
    {
        $existing = Student::create(['class_room_id' => $this->student->class_room_id, 'teacher_id' => $this->teacherProfile->id,
            'name' => 'Wildan Zamorano', 'status' => 'active', 'tahfizh_level' => 'reguler']);

        $this->actingAs($this->superAdmin)->post(route('users.store'), $this->payload(['name' => ' wildan zamorano ']))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'disambungkan'));

        $this->assertSame(User::where('username', 'zamorano')->value('id'), $existing->fresh()->user_id);
        $this->assertSame(1, Student::where('class_room_id', $this->student->class_room_id)->whereRaw('LOWER(name) LIKE ?', ['%zamorano%'])->count());
    }

    #[Test]
    public function student_account_requires_class_and_teacher_and_creates_nothing_on_error(): void
    {
        $this->actingAs($this->superAdmin)->post(route('users.store'), $this->payload(['student' => ['class_room_id' => '', 'teacher_id' => '']]))
            ->assertSessionHasErrors(['student.class_room_id', 'student.teacher_id']);

        $this->assertNull(User::where('username', 'zamorano')->first());
    }

    #[Test]
    public function new_parent_username_must_be_unused(): void
    {
        $this->actingAs($this->superAdmin)->post(route('users.store'), $this->payload([
            'new_parents' => [['name' => 'Ortu', 'username' => $this->parentUser->username, 'password' => 'rahasia123']],
        ]))->assertSessionHasErrors('new_parents.0.username');

        $this->assertNull(User::where('username', 'zamorano')->first());
    }

    #[Test]
    public function non_student_account_ignores_student_fields(): void
    {
        $teacherRole = Role::where('name', 'teacher')->firstOrFail();

        $this->actingAs($this->superAdmin)->post(route('users.store'), [
            'name' => 'Guru Baru', 'username' => 'gurubaru', 'password' => 'rahasia123', 'status' => 'active', 'role_id' => $teacherRole->id,
        ])->assertSessionHasNoErrors();

        $this->assertNotNull(User::where('username', 'gurubaru')->first()->teacherProfile);
        $this->assertSame(0, Student::where('name', 'Guru Baru')->count());
    }
}
