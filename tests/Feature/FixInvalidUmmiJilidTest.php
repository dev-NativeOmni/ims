<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\UmmiRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class FixInvalidUmmiJilidTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function ummiSession(string $date, ?string $jilid, int $tatapMuka = 1, ?Student $student = null, string $halaman = '10'): UmmiRecord
    {
        return UmmiRecord::create([
            'student_id' => ($student ?? $this->student)->id,
            'teacher_id' => $this->teacherProfile->id,
            'tatap_muka' => $tatapMuka,
            'tanggal' => $date,
            'ummi_jilid' => $jilid,
            'ummi_halaman' => $halaman,
        ]);
    }

    /** Murid di halaqoh guru lain ("Ustadzah Lain"). */
    private function studentInOtherHalaqoh(): Student
    {
        $teacher = TeacherProfile::create(['user_id' => User::factory()->create(['name' => 'Ustadzah Lain'])->id]);

        return Student::create([
            'class_room_id' => $this->student->class_room_id,
            'teacher_id' => $teacher->id,
            'name' => 'Murid Halaqoh Lain',
            'student_number' => 'TEST-FIX-002',
            'gender' => 'female',
            'birth_date' => '2010-05-10',
            'status' => 'active',
            'tahfizh_level' => 'ummi',
        ]);
    }

    private function completedTarget(Student $student): HafalanTarget
    {
        return HafalanTarget::create([
            'student_id' => $student->id,
            'teacher_id' => $this->teacherProfile->id,
            'target_date' => '2027-01-31',
            'ummi_jilid' => 'Jilid 2',
            'halaman_buku' => '40',
            'status' => 'completed',
            'book_status' => 'completed',
            'completed_at' => '2026-09-20 23:59:59',
        ]);
    }

    #[Test]
    public function invalid_jilid_follows_the_previous_meeting(): void
    {
        $firstOfMonth = $this->ummiSession('2026-08-03', 'Jilid 4');   // belum ada pertemuan sebelumnya -> sesudahnya
        $this->ummiSession('2026-08-04', 'Jilid 2');
        $afterJilid2 = $this->ummiSession('2026-08-05', 'Jilid 4');
        $consecutive = $this->ummiSession('2026-08-06', 'Jilid 5');   // pertemuan sebelumnya juga salah -> tetap Jilid 2
        $this->ummiSession('2026-08-07', 'Jilid 3');
        $typo = $this->ummiSession('2026-08-10', 'jilid 3');
        $ghoroib = $this->ummiSession('2026-08-11', 'Ghoroib');
        $quran = $this->ummiSession('2026-08-12', "Al-Qur'an");

        // Target yang tercapai sejak setoran berjilid salah tidak diubah, tapi ditampilkan untuk dicek ulang.
        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'target_date' => '2026-08-31',
            'ummi_jilid' => 'Jilid 3',
            'halaman_buku' => '5',
            'status' => 'completed',
            'completed_at' => '2026-08-05 23:59:59',
        ]);

        // Uji coba tidak mengubah apa pun.
        $this->artisan('tad:perbaiki-jilid-ummi', ['--dry-run' => true])
            ->expectsOutputToContain('Target Ummi berstatus tercapai yang perlu dicek ulang')
            ->assertSuccessful();
        $this->assertSame('Jilid 4', $afterJilid2->fresh()->ummi_jilid);

        $this->artisan('tad:perbaiki-jilid-ummi', ['--force' => true])->assertSuccessful();

        $this->assertSame('Jilid 2', $firstOfMonth->fresh()->ummi_jilid);
        $this->assertSame('Jilid 2', $afterJilid2->fresh()->ummi_jilid);
        $this->assertSame('Jilid 2', $consecutive->fresh()->ummi_jilid);
        $this->assertSame('Jilid 3', $typo->fresh()->ummi_jilid);
        $this->assertSame('Gharib', $ghoroib->fresh()->ummi_jilid);
        $this->assertSame("Al-Qur'an", $quran->fresh()->ummi_jilid, 'bukan nomor jilid -> dikoreksi manual');
        $this->assertSame('10', $afterJilid2->fresh()->ummi_halaman, 'halaman tidak diubah');

        $log = AuditLog::query()->where('auditable_type', UmmiRecord::class)->where('auditable_id', $afterJilid2->id)->firstOrFail();
        $this->assertSame(['ummi_jilid' => 'Jilid 4'], $log->old_values);
        $this->assertSame(['ummi_jilid' => 'Jilid 2'], $log->new_values);
    }

    #[Test]
    public function previous_meeting_may_be_in_an_earlier_month_but_next_meeting_must_be_in_the_same_month(): void
    {
        $firstEver = $this->ummiSession('2026-07-01', 'Jilid 9');  // tidak ada sebelumnya, sesudahnya beda bulan
        $this->ummiSession('2026-08-03', 'Jilid 1');
        $september = $this->ummiSession('2026-09-01', 'Jilid 4'); // sebelumnya = 3 Agustus

        $this->artisan('tad:perbaiki-jilid-ummi', ['--force' => true])->assertSuccessful();

        $this->assertSame('Jilid 9', $firstEver->fresh()->ummi_jilid, 'dikoreksi manual');
        $this->assertSame('Jilid 1', $september->fresh()->ummi_jilid);
    }

    #[Test]
    public function halaqoh_override_beats_the_previous_meeting_rule_only_for_that_halaqoh(): void
    {
        $other = $this->studentInOtherHalaqoh();
        $this->ummiSession('2026-09-09', 'Jilid 2', halaman: '20');
        $isti = $this->ummiSession('2026-09-17', 'Jilid 4', halaman: '5-7');
        $this->ummiSession('2026-09-09', 'Jilid 2', student: $other, halaman: '20');
        $otherJilid4 = $this->ummiSession('2026-09-17', 'Jilid 4', student: $other, halaman: '21');

        $this->artisan('tad:perbaiki-jilid-ummi', ['--halaqoh' => 'guru test', '--force' => true])->assertFailed();
        $this->artisan('tad:perbaiki-jilid-ummi', ['--halaqoh' => 'guru test', '--dari' => 'Jilid 4', '--jadi' => 'Jilid 9', '--force' => true])->assertFailed();

        $this->artisan('tad:perbaiki-jilid-ummi', ['--halaqoh' => 'guru test', '--dari' => 'Jilid 4', '--jadi' => 'Jilid 3', '--force' => true])
            ->assertSuccessful();

        $this->assertSame('Jilid 3', $isti->fresh()->ummi_jilid);
        $this->assertSame('Jilid 2', $otherJilid4->fresh()->ummi_jilid);
    }

    #[Test]
    public function recalculation_only_touches_targets_whose_book_position_actually_changed(): void
    {
        // Halaqoh ini: Jilid 4 -> Jilid 3. Dulu sudah terbaca Jilid 3, jadi targetnya sah & tidak disentuh.
        $this->ummiSession('2026-09-09', 'Jilid 2', halaman: '38');
        $this->ummiSession('2026-09-17', 'Jilid 4', halaman: '5-7');
        $legit = $this->completedTarget($this->student);

        // Halaqoh lain: Jilid 4 -> Jilid 2 hal. 21 (di bawah target Jilid 2 hal. 40) -> target aktif lagi.
        $other = $this->studentInOtherHalaqoh();
        $this->ummiSession('2026-09-09', 'Jilid 2', student: $other, halaman: '20');
        $this->ummiSession('2026-09-17', 'Jilid 4', student: $other, halaman: '21');
        $wrong = $this->completedTarget($other);

        $this->artisan('tad:perbaiki-jilid-ummi', [
            '--halaqoh' => 'guru test', '--dari' => 'Jilid 4', '--jadi' => 'Jilid 3',
            '--hitung-ulang-target' => true, '--force' => true,
        ])->assertSuccessful();

        $this->assertSame('completed', $legit->fresh()->status);
        $this->assertSame('2026-09-20', $legit->fresh()->completed_at->toDateString());
        $this->assertSame('active', $wrong->fresh()->status);
        $this->assertNull($wrong->fresh()->completed_at);
    }
}
