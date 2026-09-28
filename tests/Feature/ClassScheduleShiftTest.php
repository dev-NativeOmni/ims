<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanRecordSurah;
use App\Models\MurajaahRecord;
use App\Models\Program;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\UmmiRecord;
use App\Models\User;
use App\Services\SchoolCalendar;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassScheduleShiftTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $teacherUser;

    private ClassRoom $classRoom;

    private Student $student;

    private TeacherProfile $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
        ]);

        $this->adminUser = User::where('username', 'admin')->first();
        $this->teacherUser = User::where('username', 'guru')->first();
        $this->teacher = $this->teacherUser->teacherProfile ?? TeacherProfile::firstOrCreate(['user_id' => $this->teacherUser->id]);

        $program = Program::create([
            'name' => 'Program Reguler',
            'meeting_frequency' => 'setiap hari',
            'status' => 'active',
        ]);

        $this->classRoom = ClassRoom::create([
            'name' => 'Kelas X Coba',
            'program_id' => $program->id,
            'status' => 'active',
            'tahfizh_days' => [4], // Hanya hari Kamis (4)
        ]);

        $this->student = Student::create([
            'class_room_id' => $this->classRoom->id,
            'name' => 'Santri Coba',
            'nis' => '12345',
            'status' => 'active',
            'teacher_id' => $this->teacher->id,
        ]);

        // Pastikan ada surah untuk relasi setoran
        Surah::firstOrCreate(
            ['number' => 1],
            ['name_latin' => 'Al-Fatihah', 'name_ar' => 'الفاتحة', 'total_ayah' => 7]
        );
    }

    public function test_admin_can_lock_and_unlock_schedule_month(): void
    {
        $calendar = app(SchoolCalendar::class);

        // Kunci jadwal kelas bulan September 2026
        $response = $this->actingAs($this->adminUser)->post(route('class-schedules.month.lock'), [
            'year' => 2026,
            'month' => 9,
            'action' => 'lock',
        ]);
        $response->assertRedirect();
        $this->assertTrue($calendar->isMonthLocked(2026, 9, SchoolCalendar::SCOPE_TAHFIZH));

        // Mencoba update jadwal pekan di bulan September 2026 yang terkunci -> ditolak dengan pesan error
        $response = $this->actingAs($this->adminUser)->post(route('class-schedules.week.update'), [
            'week' => '2026-09-07',
            'schedules' => [
                $this->classRoom->id => [1, 2],
            ],
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Buka kunci jadwal kelas bulan September 2026
        $response = $this->actingAs($this->adminUser)->post(route('class-schedules.month.lock'), [
            'year' => 2026,
            'month' => 9,
            'action' => 'unlock',
        ]);
        $response->assertRedirect();
        $this->assertFalse($calendar->isMonthLocked(2026, 9, SchoolCalendar::SCOPE_TAHFIZH));

        // Setelah dibuka, update pekanan berhasil
        $response = $this->actingAs($this->adminUser)->post(route('class-schedules.week.update'), [
            'week' => '2026-09-07',
            'schedules' => [
                $this->classRoom->id => [1, 2],
            ],
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_preview_and_execute_record_shift_from_thursday_to_tuesday(): void
    {
        // 3 September 2026 adalah hari Kamis (day 4). Target pada pekan yang sama adalah 1 September 2026 (Selasa, day 2).
        $thursday = Carbon::create(2026, 9, 3, 8, 30, 0);

        // 1. Buat HafalanRecord di hari Kamis
        $hafalan = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'submitted_at' => $thursday,
            'score' => 90,
            'score_letter' => 'A',
            'notes' => 'Lancar',
        ]);
        HafalanRecordSurah::create([
            'hafalan_record_id' => $hafalan->id,
            'surah_id' => 1,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'status' => 'passed',
        ]);

        // 2. Buat UmmiRecord di hari Kamis
        $ummi = UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'tanggal' => '2026-09-03',
            'tatap_muka' => 1,
            'ummi_jilid' => 1,
            'ummi_halaman' => 10,
            'nilai' => 'A',
        ]);

        // 3. Buat MurajaahRecord di hari Kamis
        $murajaah = MurajaahRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'surah_id' => 1,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'reviewed_at' => '2026-09-03',
            'status' => 'passed',
        ]);

        // 4. Buat Attendance di hari Kamis
        $attendance = Attendance::create([
            'student_id' => $this->student->id,
            'class_room_id' => $this->classRoom->id,
            'teacher_id' => $this->teacher->id,
            'tanggal' => '2026-09-03',
            'status' => 'hadir',
        ]);

        // Tinjau preview pergeseran via GET
        $previewResponse = $this->actingAs($this->adminUser)->get(route('class-schedules.shift', [
            'class_room_id' => $this->classRoom->id,
            'year' => 2026,
            'month' => 9,
            'from_day' => 4,
            'to_day' => 2,
        ]));
        $previewResponse->assertStatus(200);
        $previewResponse->assertViewHas('previewData');
        $previewData = $previewResponse->viewData('previewData');
        $this->assertEquals(1, $previewData['total_hafalan']);
        $this->assertEquals(1, $previewData['total_ummi']);
        $this->assertEquals(1, $previewData['total_murajaah']);
        $this->assertEquals(1, $previewData['total_attendance']);
        $this->assertEquals(4, $previewData['total_records']);

        // Eksekusi pemindahan catatan dari Kamis (4) ke Selasa (2)
        $executeResponse = $this->actingAs($this->adminUser)->post(route('class-schedules.shift.execute'), [
            'class_room_id' => $this->classRoom->id,
            'year' => 2026,
            'month' => 9,
            'from_day' => 4,
            'to_day' => 2,
            'shift_types' => ['hafalan', 'ummi', 'murajaah', 'attendance'],
            'update_schedule' => 1,
        ]);

        $executeResponse->assertRedirect();
        $executeResponse->assertSessionHas('success');

        // Verifikasi catatan berpindah ke tanggal Selasa (2026-09-01)
        $hafalan->refresh();
        $this->assertEquals('2026-09-01', $hafalan->submitted_at->toDateString());

        $ummi->refresh();
        $this->assertEquals('2026-09-01', $ummi->tanggal->toDateString());

        $murajaah->refresh();
        $this->assertEquals('2026-09-01', $murajaah->reviewed_at->toDateString());

        $attendance->refresh();
        $this->assertEquals('2026-09-01', $attendance->tanggal->toDateString());

        // Verifikasi jadwal default kelas juga diperbarui menjadi hari Selasa (2)
        $this->classRoom->refresh();
        $this->assertEquals([2], $this->classRoom->tahfizh_days);
    }

    public function test_teacher_cannot_access_or_shift_schedules(): void
    {
        $this->actingAs($this->teacherUser)
            ->get(route('class-schedules.shift'))
            ->assertStatus(403);

        $this->actingAs($this->teacherUser)
            ->post(route('class-schedules.shift.execute'), [
                'class_room_id' => $this->classRoom->id,
                'year' => 2026,
                'month' => 9,
                'from_day' => 4,
                'to_day' => 2,
            ])
            ->assertStatus(403);
    }
}
