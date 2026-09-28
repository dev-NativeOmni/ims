<?php

namespace Tests\Feature;

use App\Models\HafalanTarget;
use App\Models\Role;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\CoreDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTahfizhRecordsTest extends TestCase
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

    public function test_audit_tahfizh_command_runs_successfully(): void
    {
        $teacher = TeacherProfile::first() ?? TeacherProfile::create([
            'user_id' => User::factory()->create(['role_id' => Role::where('name', 'teacher')->first()->id])->id,
        ]);

        $student = Student::create([
            'name' => 'Santri Audit Test',
            'student_number' => 'AUDIT_01',
            'status' => 'active',
            'teacher_id' => $teacher->id,
        ]);

        $this->artisan('tahfizh:audit-sync')
            ->expectsOutputToContain('Audit Data Tahfizh')
            ->expectsOutputToContain('Santri Audit Test')
            ->assertExitCode(0);

        $this->artisan('tahfizh:audit-sync --fix')
            ->expectsOutputToContain('Audit & Sinkronisasi Tahfizh selesai dengan sukses')
            ->assertExitCode(0);
    }
}
