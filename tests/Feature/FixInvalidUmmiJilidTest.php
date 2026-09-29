<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\HafalanTarget;
use App\Models\UmmiRecord;
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

    private function ummiSession(string $date, ?string $jilid, int $tatapMuka = 1): UmmiRecord
    {
        return UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tatap_muka' => $tatapMuka,
            'tanggal' => $date,
            'ummi_jilid' => $jilid,
            'ummi_halaman' => '10',
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
}
