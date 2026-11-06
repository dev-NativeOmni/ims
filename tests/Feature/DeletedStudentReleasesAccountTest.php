<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Kasus nyata: akun murid dibuat di Kelola User (otomatis membuat data murid tanpa kelas), murid yang sama
 * ditambahkan lagi di Data Murid, lalu data tanpa kelas dihapus. Baris terhapus dulu tetap memegang
 * user_id yang unik, sehingga login murid dan simpan akun berakhir error 500 (Duplicate entry).
 */
class DeletedStudentReleasesAccountTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function deleted_student_releases_account_so_it_can_log_in_and_be_relinked(): void
    {
        $roleStudent = Role::where('name', 'student')->first();

        $this->actingAs($this->superAdmin)->post(route('users.store'), [
            'name' => 'Wildan Zamorano', 'username' => 'zamorano', 'role_id' => $roleStudent->id,
            'password' => 'rahasia123', 'status' => 'active',
        ])->assertRedirect();
        $account = User::where('username', 'zamorano')->firstOrFail();
        $autoCreated = Student::where('user_id', $account->id)->firstOrFail();

        $this->actingAs($this->superAdmin)->post(route('students.store'), [
            'class_room_id' => $this->student->class_room_id, 'teacher_id' => $this->teacherProfile->id,
            'name' => 'Wildan Zamorano', 'status' => 'active',
        ])->assertRedirect();
        $real = Student::whereNull('user_id')->where('name', 'Wildan Zamorano')->firstOrFail();

        $this->actingAs($this->superAdmin)->delete(route('students.destroy', $autoCreated))->assertRedirect();
        $this->assertNull(Student::withTrashed()->find($autoCreated->id)->user_id);

        // Login murid & simpan akun tidak lagi 500.
        $this->actingAs($account)->get(route('student.dashboard'))->assertOk();
        $this->actingAs($this->superAdmin)->put(route('users.update', $account), [
            'name' => 'Wildan Zamorano', 'username' => 'zamorano', 'role_id' => $roleStudent->id, 'status' => 'active',
        ])->assertRedirect();

        // Akun bisa disambungkan ke data murid yang benar lewat Edit Murid.
        $account->studentProfile?->forceDelete();
        $this->actingAs($this->superAdmin)->put(route('students.update', $real), [
            'user_id' => $account->id, 'class_room_id' => $real->class_room_id, 'teacher_id' => $real->teacher_id,
            'name' => $real->name, 'status' => 'active',
        ])->assertSessionHasNoErrors();
        $this->assertSame($account->id, $real->fresh()->user_id);
    }

    #[Test]
    public function migration_releases_accounts_held_by_already_deleted_students(): void
    {
        $this->student->delete();
        Student::withTrashed()->whereKey($this->student->id)->update(['user_id' => $this->studentUser->id]);

        $migration = require database_path('migrations/2026_11_06_000001_release_user_of_deleted_students.php');
        $migration->up();

        $this->assertNull(Student::withTrashed()->find($this->student->id)->user_id);
        $this->actingAs($this->studentUser)->get(route('student.dashboard'))->assertOk();
    }
}
