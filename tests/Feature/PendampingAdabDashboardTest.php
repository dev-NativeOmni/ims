<?php

namespace Tests\Feature;

use App\Models\AdabRecord;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Dashboard Pendamping Adab: sama seperti dashboard Wali Kelas, menampilkan daftar nama
 * murid yang belum mengisi kuisioner adab hari ini -- bukan cuma jumlah/persentase.
 */
class PendampingAdabDashboardTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function pendamping(): User
    {
        $role = Role::firstOrCreate(['name' => 'pendamping_adab'], ['display_name' => 'Pendamping Adab']);
        $pendamping = User::factory()->create(['role_id' => $role->id, 'status' => 'active']);
        $this->student->classRoom->update(['pendamping_adab_id' => $pendamping->id]);

        return $pendamping;
    }

    #[Test]
    public function dashboard_lists_the_names_of_students_who_have_not_filled_todays_questionnaire(): void
    {
        $pendamping = $this->pendamping();

        $otherStudent = Student::create([
            'class_room_id' => $this->student->class_room_id,
            'teacher_id' => $this->teacherProfile->id,
            'name' => 'Sudah Isi',
            'student_number' => 'TEST-SNT-902',
            'gender' => 'female',
            'birth_date' => '2010-01-01',
            'status' => 'active',
        ]);

        AdabRecord::create([
            'student_id' => $otherStudent->id,
            'assessment_date' => now()->toDateString(),
            'answers' => [],
            'total_score' => 100,
        ]);

        $response = $this->actingAs($pendamping)->get(route('pendamping-adab.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('adabToday', function ($adabToday) use ($otherStudent) {
            $names = $adabToday['missing']->pluck('name');

            return $names->contains($this->student->name)
                && ! $names->contains($otherStudent->name);
        });
        $response->assertSee($this->student->name);
    }

    #[Test]
    public function dashboard_shows_a_celebratory_message_when_everyone_has_filled_it_in(): void
    {
        $pendamping = $this->pendamping();

        AdabRecord::create([
            'student_id' => $this->student->id,
            'assessment_date' => now()->toDateString(),
            'answers' => [],
            'total_score' => 100,
        ]);

        $response = $this->actingAs($pendamping)->get(route('pendamping-adab.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('adabToday', fn ($adabToday) => $adabToday['missing']->isEmpty());
        $response->assertSee('Semua murid sudah mengisi kuisioner adab hari ini');
    }
}
