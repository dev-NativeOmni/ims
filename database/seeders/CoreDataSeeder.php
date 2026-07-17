<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Program;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class CoreDataSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::updateOrCreate(
            ['name' => 'Program Tahfizh'],
            [
                'description' => 'Program tahfizh intensif untuk santri aktif.',
                'status' => 'active',
                'meeting_frequency' => 'setiap hari',
            ]
        );

        $programReguler = Program::updateOrCreate(
            ['name' => 'Program Reguler'],
            [
                'description' => 'Program kelas reguler seminggu sekali.',
                'status' => 'active',
                'meeting_frequency' => 'seminggu sekali',
            ]
        );

        $classRoom = ClassRoom::updateOrCreate(
            ['name' => 'Kelas A - Juz 30'],
            [
                'program_id' => $program->id,
                'level' => 'Pemula',
            ]
        );

        $teacherUser = User::where('username', 'guru')->firstOrFail();

        $teacherProfile = TeacherProfile::updateOrCreate(
            ['user_id' => $teacherUser->id],
            [
                'employee_number' => 'GURU-001',
                'phone' => '081111111111',
            ]
        );

        $parentUser = User::where('username', 'orangtua')->firstOrFail();

        $parentProfile = ParentProfile::updateOrCreate(
            ['user_id' => $parentUser->id],
            [
                'phone' => '082222222222',
                'address' => 'Alamat testing orangtua IMS',
            ]
        );

        $studentUser = User::where('username', 'santri')->firstOrFail();

        $student = Student::updateOrCreate(
            ['user_id' => $studentUser->id],
            [
                'student_number' => 'SNT-001',
                'class_room_id' => $classRoom->id,
                'teacher_id' => $teacherProfile->id,
                'name' => 'Santri IMS',
                'gender' => 'male',
                'birth_date' => '2012-01-15',
                'status' => 'active',
            ]
        );

        $parentProfile->students()->syncWithoutDetaching([
            $student->id => [
                'relation' => 'ayah',
            ],
        ]);
    }
}
