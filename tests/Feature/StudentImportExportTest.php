<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\CoreDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
            CoreDataSeeder::class,
        ]);
    }

    public function test_student_and_admin_cannot_access_export(): void
    {
        $studentUser = User::where('username', 'santri')->first();
        $adminUser = User::where('username', 'admin')->first();

        // Student forbidden
        $this->actingAs($studentUser)->get(route('students.export'))->assertStatus(403);

        // Admin forbidden
        $this->actingAs($adminUser)->get(route('students.export'))->assertStatus(403);
    }

    public function test_student_and_admin_cannot_access_import(): void
    {
        $studentUser = User::where('username', 'santri')->first();
        $adminUser = User::where('username', 'admin')->first();
        $file = UploadedFile::fake()->create('import.xlsx', 100);

        // Student forbidden
        $this->actingAs($studentUser)->post(route('students.import'), ['file' => $file])->assertStatus(403);

        // Admin forbidden
        $this->actingAs($adminUser)->post(route('students.import'), ['file' => $file])->assertStatus(403);
    }

    public function test_super_admin_can_export_students_in_excel_compatible_format(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        // Ensure there is at least one student with parent relation to test
        $student = Student::first();
        $this->assertNotNull($student);

        // Add parent relations for testing export columns
        $parentUser1 = User::factory()->create([
            'username' => 'testparent1',
            'role_id' => User::where('username', 'orangtua')->first()->role_id,
        ]);
        $parentProfile1 = ParentProfile::create(['user_id' => $parentUser1->id, 'phone' => '123']);

        $parentUser2 = User::factory()->create([
            'username' => 'testparent2',
            'role_id' => User::where('username', 'orangtua')->first()->role_id,
        ]);
        $parentProfile2 = ParentProfile::create(['user_id' => $parentUser2->id, 'phone' => '456']);

        $student->parents()->sync([
            $parentProfile1->id => ['relation' => 'Ayah'],
            $parentProfile2->id => ['relation' => 'Ibu'],
        ]);

        $response = $this->actingAs($superAdmin)->get(route('students.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $content = $response->streamedContent();

        // Save content to a temp file to parse it with SimpleXlsxReader
        $tempFile = @tempnam(sys_get_temp_dir(), 'test_xlsx');
        file_put_contents($tempFile, $content);

        $rows = \App\Services\SimpleXlsxReader::read($tempFile);
        @unlink($tempFile);

        $this->assertNotEmpty($rows);

        // Assert headers
        $headers = $rows[0];
        $this->assertContains('Nama Santri', $headers);
        $this->assertContains('Nomor Induk', $headers);
        $this->assertContains('Username Orangtua', $headers);
        $this->assertContains('Hubungan Orangtua', $headers);

        // Assert student row data
        $found = false;
        foreach ($rows as $row) {
            if ($row[0] === $student->name) {
                $found = true;
                $this->assertEquals($student->student_number, $row[1]);
                $this->assertEquals('testparent1,testparent2', $row[8]); // Username Orangtua
                $this->assertEquals('Ayah,Ibu', $row[9]); // Hubungan Orangtua
            }
        }
        $this->assertTrue($found);
    }

    public function test_super_admin_can_import_students_excel_format(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        // Setup some lookup targets
        $classRoom = ClassRoom::first();
        $teacher = TeacherProfile::first();
        $teacherUser = $teacher->user;

        // Create user accounts for the new student and parents
        $studentUser = User::factory()->create([
            'username' => 'newstudent',
            'role_id' => User::where('username', 'santri')->first()->role_id,
        ]);

        $parentUser1 = User::factory()->create([
            'username' => 'parent1',
            'role_id' => User::where('username', 'orangtua')->first()->role_id,
        ]);
        $parentProfile1 = ParentProfile::create(['user_id' => $parentUser1->id, 'phone' => '111']);

        $parentUser2 = User::factory()->create([
            'username' => 'parent2',
            'role_id' => User::where('username', 'orangtua')->first()->role_id,
        ]);
        $parentProfile2 = ParentProfile::create(['user_id' => $parentUser2->id, 'phone' => '222']);

        // Write a test .xlsx file using our SimpleXlsxWriter!
        $tempFile = @tempnam(sys_get_temp_dir(), 'test_xlsx');
        $headers = [
            'Nama Santri',
            'Nomor Induk',
            'Jenis Kelamin',
            'Tanggal Lahir',
            'Status',
            'Kelas',
            'Username Guru',
            'Username Santri',
            'Username Orangtua',
            'Hubungan Orangtua',
        ];
        $data = [
            [
                'Siswa Baru',
                'SNT-999',
                'male',
                '2011-12-05',
                'active',
                $classRoom->name,
                $teacherUser->username,
                $studentUser->username,
                'parent1,parent2',
                'Ayah,Ibu',
            ]
        ];

        \App\Services\SimpleXlsxWriter::write($tempFile, $headers, $data);

        // Upload the file
        $file = new UploadedFile($tempFile, 'students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($superAdmin)->post(route('students.import'), [
            'file' => $file,
        ]);

        @unlink($tempFile);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success');

        // Assert database records
        $student = Student::where('student_number', 'SNT-999')->first();
        $this->assertNotNull($student);
        $this->assertEquals('Siswa Baru', $student->name);
        $this->assertEquals('male', $student->gender);
        $this->assertEquals('2011-12-05', $student->birth_date->toDateString());
        $this->assertEquals('active', $student->status);
        $this->assertEquals($classRoom->id, $student->class_room_id);
        $this->assertEquals($teacher->id, $student->teacher_id);
        $this->assertEquals($studentUser->id, $student->user_id);

        // Assert parents relations
        $this->assertEquals(2, $student->parents()->count());
        $this->assertTrue($student->parents->contains($parentProfile1->id));
        $this->assertTrue($student->parents->contains($parentProfile2->id));

        $p1Relation = $student->parents()->where('parent_profiles.id', $parentProfile1->id)->first()->pivot->relation;
        $p2Relation = $student->parents()->where('parent_profiles.id', $parentProfile2->id)->first()->pivot->relation;

        $this->assertEquals('Ayah', $p1Relation);
        $this->assertEquals('Ibu', $p2Relation);
    }

    public function test_super_admin_can_import_students_and_auto_create_non_existent_parents_and_students_with_legacy_emails(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        // Setup some lookup targets
        $classRoom = ClassRoom::first();
        $teacher = TeacherProfile::first();
        $teacherUser = $teacher->user;

        // Write a test .xlsx file using our SimpleXlsxWriter!
        $tempFile = @tempnam(sys_get_temp_dir(), 'test_xlsx');
        $headers = [
            'Nama Santri',
            'Nomor Induk',
            'Jenis Kelamin',
            'Tanggal Lahir',
            'Status',
            'Kelas',
            'Username Guru',
            'Username Santri',
            'Username Orangtua',
            'Hubungan Orangtua',
        ];
        $data = [
            [
                'Siswa Auto Create',
                'SNT-888',
                'female',
                '2012-05-10',
                'active',
                $classRoom->name,
                $teacherUser->username . '@hafizplus.test', // email format
                'autostudent@hafizplus.test', // email format, non-existent
                'autoparent1@hafizplus.test,autoparent2', // mix email and username, non-existent
                'Ibu,Ayah',
            ]
        ];

        \App\Services\SimpleXlsxWriter::write($tempFile, $headers, $data);

        // Upload the file
        $file = new UploadedFile($tempFile, 'students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($superAdmin)->post(route('students.import'), [
            'file' => $file,
        ]);

        @unlink($tempFile);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success');

        // Assert student was created
        $student = Student::where('student_number', 'SNT-888')->first();
        $this->assertNotNull($student);
        $this->assertEquals('Siswa Auto Create', $student->name);
        $this->assertEquals('female', $student->gender);

        // Assert student user was auto-created and linked
        $studentUser = User::where('username', 'autostudent')->first();
        $this->assertNotNull($studentUser);
        $this->assertEquals($studentUser->id, $student->user_id);
        $this->assertTrue($studentUser->hasRole('student'));

        // Assert parent users and profiles were auto-created
        $parentUser1 = User::where('username', 'autoparent1')->first();
        $this->assertNotNull($parentUser1);
        $this->assertTrue($parentUser1->hasRole('parent'));
        $this->assertNotNull($parentUser1->parentProfile);

        $parentUser2 = User::where('username', 'autoparent2')->first();
        $this->assertNotNull($parentUser2);
        $this->assertTrue($parentUser2->hasRole('parent'));
        $this->assertNotNull($parentUser2->parentProfile);

        // Assert parents relations
        $this->assertEquals(2, $student->parents()->count());
        $this->assertTrue($student->parents->contains($parentUser1->parentProfile->id));
        $this->assertTrue($student->parents->contains($parentUser2->parentProfile->id));

        $p1Relation = $student->parents()->where('parent_profiles.id', $parentUser1->parentProfile->id)->first()->pivot->relation;
        $p2Relation = $student->parents()->where('parent_profiles.id', $parentUser2->parentProfile->id)->first()->pivot->relation;

        $this->assertEquals('Ibu', $p1Relation);
        $this->assertEquals('Ayah', $p2Relation);
    }

    public function test_super_admin_import_optimizations(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $classRoom = ClassRoom::first();
        $teacher = TeacherProfile::first();
        $teacherUser = $teacher->user;

        // 1. Create a student with existing data
        $studentUser = User::factory()->create([
            'username' => 'optstudent',
            'role_id' => User::where('username', 'santri')->first()->role_id,
        ]);
        $student = Student::create([
            'name' => 'Original Name',
            'student_number' => 'SNT-OPT',
            'gender' => 'female',
            'birth_date' => '2010-01-01',
            'status' => 'active',
            'class_room_id' => $classRoom->id,
            'teacher_id' => $teacher->id,
            'user_id' => $studentUser->id,
        ]);

        // 2. Perform import with:
        // - Serial date: 44289 (which represents 2021-04-02)
        // - Matching by user_id but student_number is empty (checks duplicate key and secondary matching)
        // - Empty Class, Guru, and status (checks partial update, shouldn't overwrite existing ones)
        // - Normalized spaces in headers (checks normalizer)
        $tempFile = @tempnam(sys_get_temp_dir(), 'test_xlsx');
        $headers = [
            'Nama   Santri', // spaces normalized
            'Nomor Induk',
            'Jenis Kelamin',
            'Tanggal Lahir',
            'Status',
            'Kelas',
            'Username Guru',
            'Username Santri',
        ];
        $data = [
            [
                'Updated Name',
                '', // empty student number, should match by username 'optstudent' (user_id)
                'male',
                '44289', // numeric Excel date for 2021-04-02
                '', // empty status, should NOT overwrite
                '', // empty class, should NOT overwrite
                '', // empty teacher, should NOT overwrite
                'optstudent',
            ]
        ];

        \App\Services\SimpleXlsxWriter::write($tempFile, $headers, $data);

        $file = new UploadedFile($tempFile, 'students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($superAdmin)->post(route('students.import'), [
            'file' => $file,
        ]);

        @unlink($tempFile);

        $response->assertRedirect(route('students.index'));

        // Assert updates
        $student->refresh();
        $this->assertEquals('Updated Name', $student->name);
        $this->assertEquals('male', $student->gender);
        $this->assertEquals('2021-04-03', $student->birth_date->toDateString()); // Serial date parsed!
        
        // Assert old data was NOT overwritten with null/empty
        $this->assertEquals('SNT-OPT', $student->student_number); // kept old student number since imported was empty
        $this->assertEquals('active', $student->status); // kept old status
        $this->assertEquals($classRoom->id, $student->class_room_id); // kept old class
        $this->assertEquals($teacher->id, $student->teacher_id); // kept old teacher
        $this->assertEquals($studentUser->id, $student->user_id);
    }
}
