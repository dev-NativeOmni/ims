<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicCalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $teacherUser;

    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
        ]);

        $this->adminUser = User::where('username', 'admin')->first();
        $this->teacherUser = User::where('username', 'guru')->first();

        $program = Program::create([
            'name' => 'Program Regular',
            'meeting_frequency' => 'setiap hari',
            'status' => 'active',
        ]);

        $this->classRoom = ClassRoom::create([
            'name' => 'Kelas X Coba',
            'program_id' => $program->id,
            'status' => 'active',
        ]);

        Student::create([
            'class_room_id' => $this->classRoom->id,
            'name' => 'Santri Coba',
            'nis' => '12345',
            'status' => 'active',
        ]);
    }

    public function test_only_admin_and_superadmin_can_access_academic_calendar(): void
    {
        // Guest is redirected
        $this->get(route('academic-calendar.index'))->assertRedirect('/login');

        // Teacher is forbidden (403)
        $this->actingAs($this->teacherUser)->get(route('academic-calendar.index'))->assertStatus(403);

        // Admin can access
        $response = $this->actingAs($this->adminUser)->get(route('academic-calendar.index'));
        $response->assertStatus(200);
        $response->assertViewHas('gridDates');
        $response->assertViewHas('globalDays');
        $response->assertViewHas('locks');
    }

    public function test_admin_can_toggle_and_save_holidays_with_month_merging(): void
    {
        $year = (int) date('Y');

        $this->markHoliday("{$year}-12-25");
        $this->markClassHoliday("{$year}-12-24", $this->classRoom->id);

        $payload = [
            'year' => $year,
            'month' => 8,
            'holidays' => [
                "{$year}-08-17",
            ],
            'class_holidays' => [
                "{$year}-08-20" => [$this->classRoom->id],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('academic-calendar.update'), $payload);
        $response->assertRedirect(route('academic-calendar.index', ['year' => $year, 'month' => 8]));

        $holidays = Setting::getNationalHolidays($year);
        $this->assertContains("{$year}-08-17", $holidays);
        $this->assertContains("{$year}-12-25", $holidays);

        $classHolidays = Setting::getClassHolidays($year);
        $this->assertArrayHasKey("{$year}-08-20", $classHolidays);
        $this->assertContains($this->classRoom->id, $classHolidays["{$year}-08-20"]);
        $this->assertArrayHasKey("{$year}-12-24", $classHolidays, 'Libur bulan lain tidak boleh ikut terhapus.');

        $responseSpreadsheet = $this->actingAs($this->teacherUser)->get(route('spreadsheet-input.index', [
            'class_room_id' => $this->classRoom->id,
            'month' => "{$year}-08",
        ]));
        $responseSpreadsheet->assertStatus(200);
        $responseSpreadsheet->assertViewHas('dates');

        $dates = $responseSpreadsheet->viewData('dates');
        $this->assertNotContains("{$year}-08-17", $dates);
        $this->assertNotContains("{$year}-08-20", $dates);
    }

    /**
     * "Jadwal Kelas Bulan Ini" (bagian dari Kalender Akademik): jadwal per-pekan diedit dari sini,
     * pekan yang belum diubah tetap pakai jadwal default, dan ada penanda "Jadwal Khusus" di
     * tanggal-tanggal pekan yang sudah disesuaikan.
     */
    public function test_admin_can_see_and_edit_the_weekly_class_schedule_from_the_calendar_page(): void
    {
        // Oktober 2026 (bulan depan, belum ada pekan yang otomatis terkunci): pekan 5-11 diubah
        // jadi Senin & Rabu saja (khusus); pekan lain tetap default.
        $this->actingAs($this->adminUser)->post(route('class-schedules.week.update'), [
            'week' => '2026-10-05',
            'schedules' => [$this->classRoom->id => [1, 3]],
            'year' => 2026,
            'month' => 10,
        ])->assertRedirect(route('academic-calendar.index', ['year' => 2026, 'month' => 10]));

        $response = $this->actingAs($this->adminUser)->get(route('academic-calendar.index', ['year' => 2026, 'month' => 10]));
        $response->assertStatus(200);
        $response->assertViewHas('weeksOfMonth', function ($weeks) {
            $customWeek = collect($weeks)->first(fn ($w) => $w['start']->toDateString() === '2026-10-05');

            return $customWeek !== null && $customWeek['custom_count'] === 1;
        });

        // Tanggal di pekan yang diubah (5 Oktober) dapat penanda; tanggal di pekan lain (1
        // Oktober, pekan default) tidak.
        $response->assertViewHas('gridDates', function ($gridDates) {
            $byDate = collect($gridDates)->keyBy(fn ($d) => $d['date']->toDateString());

            return $byDate['2026-10-05']['hasCustomSchedule'] === true
                && $byDate['2026-10-01']['hasCustomSchedule'] === false;
        });
        $response->assertSee('Jadwal Khusus');
    }

    public function test_supervisor_does_not_see_the_weekly_class_schedule_section(): void
    {
        $roleSupervisor = Role::firstOrCreate(['name' => 'supervisor'], ['display_name' => 'Koordinator Adab']);
        $supervisor = User::factory()->create(['role_id' => $roleSupervisor->id, 'status' => 'active']);

        $response = $this->actingAs($supervisor)->get(route('academic-calendar.index'));
        $response->assertStatus(200);
        $response->assertViewHas('weeksOfMonth', fn ($weeks) => $weeks === []);
        // "Jadwal Kelas Bulan Ini" juga muncul sebagai komentar HTML statis; cek teks isinya
        // yang cuma dirender kalau bagiannya benar-benar tampil.
        $response->assertDontSee('menyesuaikan satu pekan');
    }
}
