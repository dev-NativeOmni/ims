<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ThirtyStudentsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get or create program and classroom
        $program = Program::firstOrCreate(
            ['name' => 'Program Tahfizh'],
            [
                'description' => 'Program tahfizh intensif untuk santri aktif.',
                'status' => 'active',
                'meeting_frequency' => 'setiap hari',
            ]
        );

        $classRoom = ClassRoom::firstOrCreate(
            ['name' => 'Kelas A - Juz 30'],
            [
                'program_id' => $program->id,
                'level' => 'Pemula',
            ]
        );

        // 2. Get a teacher to assign to these students
        $teacherProfile = TeacherProfile::first();
        if (!$teacherProfile) {
            $teacherRole = Role::where('name', 'teacher')->first();
            $teacherUser = User::create([
                'role_id' => $teacherRole->id,
                'name' => 'Guru Pengampu',
                'username' => 'gurupengampu',
                'password' => Hash::make('password123'),
                'plain_password' => 'password123',
                'status' => 'active',
            ]);
            $teacherProfile = TeacherProfile::create([
                'user_id' => $teacherUser->id,
                'employee_number' => 'GURU-999',
                'phone' => '089999999999',
            ]);
        }

        // Roles
        $studentRole = Role::where('name', 'student')->firstOrFail();
        $parentRole = Role::where('name', 'parent')->firstOrFail();

        // 3. Create 30 students and parents
        for ($i = 1; $i <= 30; $i++) {
            $index = str_pad($i, 3, '0', STR_PAD_LEFT);
            
            // Parent user
            $parentUser = User::create([
                'role_id' => $parentRole->id,
                'name' => "Wali Santri $index",
                'username' => "walisantri$index",
                'password' => Hash::make('password123'),
                'plain_password' => 'password123',
                'status' => 'active',
            ]);

            $parentProfile = ParentProfile::create([
                'user_id' => $parentUser->id,
                'phone' => '0833' . $index . '0000',
                'address' => "Alamat Wali Santri $index",
            ]);

            // Student user
            $studentUser = User::create([
                'role_id' => $studentRole->id,
                'name' => "Santri Dummy $index",
                'username' => "santridummy$index",
                'password' => Hash::make('password123'),
                'plain_password' => 'password123',
                'status' => 'active',
            ]);

            $student = Student::create([
                'user_id' => $studentUser->id,
                'student_number' => "SDM-$index",
                'class_room_id' => $classRoom->id,
                'teacher_id' => $teacherProfile->id,
                'name' => "Santri Dummy $index",
                'gender' => $i % 2 === 0 ? 'male' : 'female',
                'birth_date' => '2013-05-' . str_pad(min(28, $i), 2, '0', STR_PAD_LEFT),
                'status' => 'active',
            ]);

            // Connect student and parent
            $parentProfile->students()->syncWithoutDetaching([
                $student->id => [
                    'relation' => 'ayah',
                ],
            ]);
        }
    }
}
