<?php

namespace Tests\Feature\Concerns;

use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;

/**
 * Trait reusable untuk membuat data dasar HafizPlus di semua feature test.
 */
trait SetsUpHafizPlusData
{
    protected User $superAdmin;

    protected User $admin;

    protected User $teacherUser;

    protected User $parentUser;

    protected User $studentUser;

    protected TeacherProfile $teacherProfile;

    protected ParentProfile $parentProfile;

    protected Student $student;

    protected Surah $surah;

    protected function setUpHafizPlusData(): void
    {
        // Roles
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $roleAdmin = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $roleTeacher = Role::firstOrCreate(['name' => 'teacher'], ['display_name' => 'Guru']);
        $roleParent = Role::firstOrCreate(['name' => 'parent'], ['display_name' => 'Orangtua']);
        $roleStudent = Role::firstOrCreate(['name' => 'student'], ['display_name' => 'Santri']);

        // Users
        $this->superAdmin = User::factory()->create([
            'role_id' => $roleSuperAdmin->id,
            'name' => 'Super Admin Test',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'role_id' => $roleAdmin->id,
            'name' => 'Admin Test',
            'status' => 'active',
        ]);

        $this->teacherUser = User::factory()->create([
            'role_id' => $roleTeacher->id,
            'name' => 'Guru Test',
            'status' => 'active',
        ]);

        $this->parentUser = User::factory()->create([
            'role_id' => $roleParent->id,
            'name' => 'Orangtua Test',
            'status' => 'active',
        ]);

        $this->studentUser = User::factory()->create([
            'role_id' => $roleStudent->id,
            'name' => 'Santri Test',
            'status' => 'active',
        ]);

        // Program & Kelas
        $program = Program::create([
            'name' => 'Tahfizh Reguler Test',
            'description' => 'Program test',
            'status' => 'active',
        ]);

        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas A Test',
            'level' => 'Pemula',
        ]);

        // Teacher Profile
        $this->teacherProfile = TeacherProfile::create([
            'user_id' => $this->teacherUser->id,
            'employee_number' => 'TEST-GURU-001',
            'phone' => '081100001111',
        ]);

        // Parent Profile
        $this->parentProfile = ParentProfile::create([
            'user_id' => $this->parentUser->id,
            'phone' => '082200002222',
            'address' => 'Jl. Test No. 1',
        ]);

        // Student
        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id,
            'name' => 'Santri Test',
            'student_number' => 'TEST-SNT-001',
            'gender' => 'male',
            'birth_date' => '2010-05-10',
            'status' => 'active',
        ]);

        // Hubungkan parent dengan student
        $this->parentProfile->students()->syncWithoutDetaching([
            $this->student->id => ['relation' => 'ayah'],
        ]);

        // Surah (Al-Fatihah)
        $this->surah = Surah::firstOrCreate(
            ['number' => 1],
            [
                'name_ar' => 'الفاتحة',
                'name_latin' => 'Al-Fatihah',
                'total_ayah' => 7,
                'juz_start' => 1,
                'juz_end' => 1,
            ]
        );
    }
}
