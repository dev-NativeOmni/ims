<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\UmmiRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class SpreadsheetInputTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function teacher_can_access_spreadsheet_input_page(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('spreadsheet-input.index'));

        $response->assertStatus(200);
        $response->assertViewIs('spreadsheet-input.index');
    }

    #[Test]
    public function guest_cannot_access_spreadsheet_input_page(): void
    {
        $response = $this->get(route('spreadsheet-input.index'));

        $response->assertRedirect('/login');
    }

    #[Test]
    public function teacher_can_save_bulk_hafalan_records_via_spreadsheet(): void
    {
        $classRoom = $this->student->classRoom;
        $date = '2026-08-03';

        $payload = [
            'class_room_id' => $classRoom->id,
            'month' => '2026-08',
            'type' => 'hafalan',
            'records' => [
                $this->student->id => [
                    'dates' => [
                        $date => [
                            'attendance' => 'hadir',
                            'hafalans' => [
                                [
                                    'id' => null,
                                    'surah_id' => $this->surah->id,
                                    'ayah_start' => 1,
                                    'ayah_end' => 5,
                                    'score' => '95',
                                    'status' => 'passed',
                                    'submission_type' => 'new',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), $payload);

        $response->assertRedirect();

        // Assert Attendance was saved
        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'tanggal' => $date.' 00:00:00',
            'status' => 'hadir',
        ]);

        // Assert HafalanRecord header + child line were saved
        $this->assertDatabaseHas('hafalan_records', [
            'student_id' => $this->student->id,
            'submitted_at' => $date.' 00:00:00',
        ]);

        $hafalanRecord = HafalanRecord::where('student_id', $this->student->id)
            ->whereDate('submitted_at', $date)
            ->firstOrFail();

        $this->assertDatabaseHas('hafalan_record_surahs', [
            'hafalan_record_id' => $hafalanRecord->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'score' => 95,
            'status' => 'passed',
        ]);
    }

    #[Test]
    public function teacher_can_save_bulk_ummi_records_via_spreadsheet(): void
    {
        $classRoom = $this->student->classRoom;
        $date = '2026-08-04';

        $payload = [
            'class_room_id' => $classRoom->id,
            'month' => '2026-08',
            'type' => 'ummi',
            'records' => [
                $this->student->id => [
                    'dates' => [
                        $date => [
                            'attendance' => 'hadir',
                            'tatap_muka' => 3,
                            'ummi_jilid' => 'Jilid 2',
                            'ummi_halaman' => '25',
                            'materi' => 'Ghoroib',
                            'nilai' => 'A',
                            'hafalans' => [
                                [
                                    'id' => null,
                                    'surah_id' => $this->surah->id,
                                    'ayah' => '1-5',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), $payload);

        $response->assertRedirect();

        // Assert Attendance was saved
        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'tanggal' => $date.' 00:00:00',
            'status' => 'hadir',
        ]);

        // Assert UmmiRecord header was saved
        // TM otomatis: satu-satunya pertemuan Ummi di triwulan ini = TM 1.
        $this->assertDatabaseHas('ummi_records', [
            'student_id' => $this->student->id,
            'tatap_muka' => 1,
            'tanggal' => $date.' 00:00:00',
            'ummi_jilid' => 'Jilid 2',
            'ummi_halaman' => '25',
            'materi' => 'Ghoroib',
            'nilai' => 'A',
        ]);

        $ummiRecord = UmmiRecord::where('student_id', $this->student->id)->whereDate('tanggal', $date)->firstOrFail();

        $this->assertDatabaseHas('ummi_record_surahs', [
            'ummi_record_id' => $ummiRecord->id,
            'surah_id' => $this->surah->id,
            'hafalan_ayah' => '1-5',
        ]);
    }

    #[Test]
    public function absent_students_records_are_not_saved(): void
    {
        $classRoom = $this->student->classRoom;
        $date = '2026-08-05';

        $payload = [
            'class_room_id' => $classRoom->id,
            'month' => '2026-08',
            'type' => 'hafalan',
            'records' => [
                $this->student->id => [
                    'dates' => [
                        $date => [
                            'attendance' => 'sakit',
                            'hafalans' => [
                                [
                                    'id' => null,
                                    'surah_id' => $this->surah->id,
                                    'ayah_start' => 1,
                                    'ayah_end' => 5,
                                    'score' => '95',
                                    'status' => 'passed',
                                    'submission_type' => 'new',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), $payload);

        $response->assertRedirect();

        // Assert Attendance was saved as 'sakit'
        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'tanggal' => $date.' 00:00:00',
            'status' => 'sakit',
        ]);

        // Assert NO HafalanRecord was saved
        $this->assertDatabaseMissing('hafalan_records', [
            'student_id' => $this->student->id,
            'submitted_at' => $date,
        ]);
    }

    #[Test]
    public function teacher_can_view_specific_week_of_spreadsheet(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('spreadsheet-input.index', [
            'class_room_id' => $this->student->class_room_id,
            'month' => '2026-08',
            'week' => '1',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('selectedWeek', '1');

        $dates = $response->viewData('dates');
        $this->assertCount(5, $dates);
        $this->assertEquals('2026-08-03', $dates[0]);
        $this->assertEquals('2026-08-07', $dates[4]);
    }

    #[Test]
    public function teacher_can_view_weekly_program_spreadsheet_columns_grouped_by_week(): void
    {
        // 1. Create weekly program
        $weeklyProgram = Program::create([
            'name' => 'Program Reguler Mingguan',
            'meeting_frequency' => 'seminggu sekali',
            'status' => 'active',
        ]);

        // 2. Assign classroom to weekly program
        $classRoom = $this->student->classRoom;
        $classRoom->update(['program_id' => $weeklyProgram->id]);

        // 3. Request spreadsheet input page
        $response = $this->actingAs($this->teacherUser)->get(route('spreadsheet-input.index', [
            'class_room_id' => $classRoom->id,
            'month' => '2026-08',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('isWeekly', true);

        $dates = $response->viewData('dates');
        // August 2026 has 5 weeks with working days
        $this->assertCount(5, $dates);
        // Mondays of August 2026: 03, 10, 17, 24, 31
        $this->assertEquals('2026-08-03', $dates[0]);
        $this->assertEquals('2026-08-10', $dates[1]);
        $this->assertEquals('2026-08-31', $dates[4]);

        $columns = $response->viewData('columns');
        $this->assertCount(5, $columns);
        $this->assertEquals('Pekan 1', $columns[0]['label']);
        $this->assertEquals('03/08 - 07/08', $columns[0]['sub_label']);

        // Toggle "Tanggal Aktif" harus tetap tampil untuk program mingguan (Reguler),
        // supaya guru bisa pilih pekan mana yang mau diisi -- sebelumnya sengaja
        // disembunyikan untuk isWeekly, membuat guru terjebak di tanggal default saja.
        $response->assertSee('Tanggal Aktif', false);
    }

    #[Test]
    public function july_entries_for_many_dates_are_saved_from_a_single_json_payload(): void
    {
        $classRoom = $this->student->classRoom;

        // Kasus HP: isian di beberapa tanggal sekaligus, dikirim sebagai satu records_json
        // (bukan ribuan field form yang bisa terpotong max_input_vars).
        $dates = ['2026-07-01', '2026-07-02', '2026-07-03', '2026-07-06', '2026-07-07'];
        $cells = [];
        foreach ($dates as $i => $d) {
            $cells[$d] = [
                'attendance' => 'hadir',
                'hafalans' => [[
                    'id' => null, 'surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 3 + $i,
                    'score' => '90', 'status' => 'passed', 'submission_type' => 'new',
                ]],
            ];
        }

        $response = $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), [
            'class_room_id' => $classRoom->id,
            'month' => '2026-07',
            'type' => 'hafalan',
            'records_json' => json_encode([$this->student->id => ['dates' => $cells]]),
        ]);

        $response->assertRedirect(route('spreadsheet-input.index', [
            'class_room_id' => $classRoom->id, 'month' => '2026-07', 'week' => 'all',
        ]));

        foreach ($dates as $d) {
            $this->assertDatabaseHas('attendances', ['student_id' => $this->student->id, 'status' => 'hadir', 'tanggal' => $d.' 00:00:00']);
            $this->assertDatabaseHas('hafalan_records', ['student_id' => $this->student->id, 'submitted_at' => $d.' 00:00:00']);
        }
        $this->assertSame(5, HafalanRecord::where('student_id', $this->student->id)->count());
    }

    #[Test]
    public function spreadsheet_page_serializes_all_dates_and_keeps_the_draft_until_save_succeeds(): void
    {
        $classRoom = $this->student->classRoom;
        $html = $this->actingAs($this->teacherUser)
            ->get(route('spreadsheet-input.index', ['class_room_id' => $classRoom->id, 'month' => '2026-07']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('buildRecordsPayload()', $html);
        $this->assertStringContainsString('records_json', $html);
        // Draf tidak boleh dihapus di submitForm(); hanya setelah flag savedOk (flash sukses dari server).
        $submit = substr($html, strpos($html, 'submitForm() {'), 1600);
        $this->assertStringNotContainsString('localStorage.removeItem(this.draftKey)', $submit);
    }

    #[Test]
    public function teacher_saving_multiple_times_does_not_duplicate_records(): void
    {
        $classRoom = $this->student->classRoom;
        $date = '2026-08-03';

        // 1. First Save (new record)
        $payload1 = [
            'class_room_id' => $classRoom->id,
            'month' => '2026-08',
            'type' => 'hafalan',
            'records' => [
                $this->student->id => [
                    'dates' => [
                        $date => [
                            'attendance' => 'hadir',
                            'hafalans' => [
                                [
                                    'id' => null,
                                    'surah_id' => $this->surah->id,
                                    'ayah_start' => 1,
                                    'ayah_end' => 5,
                                    'score' => '95',
                                    'status' => 'passed',
                                    'submission_type' => 'new',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), $payload1);

        $this->assertEquals(1, HafalanRecord::where('student_id', $this->student->id)->where('submitted_at', $date.' 00:00:00')->count());
        $record = HafalanRecord::where('student_id', $this->student->id)->where('submitted_at', $date.' 00:00:00')->first();
        $surahLine = $record->surahs()->first();

        // 2. Second Save (submit with the created surah line ID)
        $payload2 = [
            'class_room_id' => $classRoom->id,
            'month' => '2026-08',
            'type' => 'hafalan',
            'records' => [
                $this->student->id => [
                    'dates' => [
                        $date => [
                            'attendance' => 'hadir',
                            'hafalans' => [
                                [
                                    'id' => $surahLine->id,
                                    'surah_id' => $this->surah->id,
                                    'ayah_start' => 1,
                                    'ayah_end' => 5,
                                    'score' => '95',
                                    'status' => 'passed',
                                    'submission_type' => 'new',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), $payload2);

        // Assert record count is still 1 (no duplicates!)
        $this->assertEquals(1, HafalanRecord::where('student_id', $this->student->id)->where('submitted_at', $date.' 00:00:00')->count());
    }

    #[Test]
    public function spreadsheet_ummi_saves_halaman_range_and_rejects_invalid_jilid(): void
    {
        $classRoom = $this->student->classRoom;
        $date = '2026-08-04';
        $save = fn (array $cell) => $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), [
            'class_room_id' => $classRoom->id,
            'month' => '2026-08',
            'type' => 'ummi',
            'records' => [$this->student->id => ['dates' => [$date => ['attendance' => 'hadir', 'tatap_muka' => 1] + $cell]]],
        ]);

        $save(['ummi_jilid' => 'Tajwid', 'ummi_halaman_awal' => '5', 'ummi_halaman_akhir' => '8']);
        $this->assertDatabaseHas('ummi_records', ['student_id' => $this->student->id, 'ummi_jilid' => 'Tajwid', 'ummi_halaman' => '5-8']);

        // Jilid di luar daftar ditolak; data sebelumnya tetap utuh.
        $save(['ummi_jilid' => 'Jilid 4', 'ummi_halaman_awal' => '1', 'ummi_halaman_akhir' => '2'])
            ->assertSessionHas('error');
        $this->assertDatabaseHas('ummi_records', ['student_id' => $this->student->id, 'ummi_jilid' => 'Tajwid', 'ummi_halaman' => '5-8']);
    }

    #[Test]
    public function kelas_10_tahfizh_ummi_presensi_follows_the_ummi_form_and_is_not_overwritten(): void
    {
        $program = Program::create(['name' => 'Program Tahfizh', 'status' => 'active']);
        $classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'X E1 Presensi', 'level' => 'X', 'tahfizh_days' => [1, 2, 3, 4, 5]]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'ummi', 'teacher_id' => $this->teacherProfile->id]);

        // Setoran mandiri tanpa presensi di form Ummi: presensi tetap kosong (tidak otomatis hadir).
        HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => '2026-09-14'])
            ->surahs()->create(['surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 3, 'submission_type' => 'new', 'status' => 'passed']);
        // Izin diisi di form Input Ummi (Tabel Presensi Kelas).
        $this->actingAs($this->teacherUser)->postJson('/attendances/save', [
            'student_id' => $this->student->id, 'class_room_id' => $classRoom->id, 'tanggal' => '2026-09-15', 'status' => 'izin',
        ])->assertOk();

        $page = $this->actingAs($this->teacherUser)->get(route('spreadsheet-input.index', ['class_room_id' => $classRoom->id, 'month' => '2026-09']));
        $map = $page->viewData('attendancesMap');
        $this->assertTrue($page->viewData('isTahfizhProgram'));
        $this->assertArrayNotHasKey('2026-09-14', $map[$this->student->id] ?? []);
        $this->assertSame('izin', $map[$this->student->id]['2026-09-15']);

        // Halaman spreadsheet yang dibuka sebelum izin diisi masih menampilkan "hadir" + setoran:
        // disimpan tanpa diubah guru -> izin dari form Ummi tetap, setoran tidak disimpan.
        $save = fn (array $cell) => $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), [
            'class_room_id' => $classRoom->id,
            'month' => '2026-09',
            'type' => 'hafalan',
            'records' => [$this->student->id => ['dates' => ['2026-09-15' => $cell]]],
        ]);
        $stale = ['attendance' => 'hadir', 'attendance_original' => 'hadir', 'hafalans' => [
            ['surah_id' => $this->surah->id, 'ayah_start' => 4, 'ayah_end' => 5, 'status' => 'passed', 'submission_type' => 'new'],
        ]];
        $save($stale);
        $this->assertSame('izin', Attendance::where('student_id', $this->student->id)->whereDate('tanggal', '2026-09-15')->value('status'));
        $this->assertSame(0, HafalanRecord::where('student_id', $this->student->id)->whereDate('submitted_at', '2026-09-15')->count());

        // Guru sendiri mengubah presensi di spreadsheet -> tetap tersimpan.
        $save(['attendance' => 'hadir', 'attendance_original' => 'izin'] + ['hafalans' => $stale['hafalans']]);
        $this->assertSame('hadir', Attendance::where('student_id', $this->student->id)->whereDate('tanggal', '2026-09-15')->value('status'));
    }

    #[Test]
    public function admin_can_filter_spreadsheet_by_teacher(): void
    {
        $otherTeacher = TeacherProfile::create([
            'user_id' => User::factory()->create(['role_id' => $this->teacherUser->role_id, 'name' => 'Guru Kedua', 'status' => 'active'])->id,
            'employee_number' => 'TEST-GURU-002',
        ]);
        $otherClass = ClassRoom::create(['program_id' => $this->student->classRoom->program_id, 'name' => 'Kelas B Test', 'level' => 'Pemula']);
        Student::create(['class_room_id' => $this->student->class_room_id, 'teacher_id' => $otherTeacher->id, 'name' => 'Santri Guru Kedua Sekelas', 'status' => 'active']);
        Student::create(['class_room_id' => $otherClass->id, 'teacher_id' => $otherTeacher->id, 'name' => 'Santri Guru Kedua Kelas B', 'status' => 'active']);

        $all = $this->actingAs($this->admin)->get(route('spreadsheet-input.index', ['class_room_id' => $this->student->class_room_id]));
        $all->assertOk()->assertSee('Guru Pengampu')->assertSee('Guru Kedua');
        $this->assertCount(2, $all->viewData('students'), 'Tanpa filter: semua murid kelas.');

        $filtered = $this->actingAs($this->admin)->get(route('spreadsheet-input.index', ['class_room_id' => $this->student->class_room_id, 'teacher_id' => $this->teacherProfile->id]));
        $this->assertSame(['Santri Test'], $filtered->viewData('students')->pluck('name')->all());
        $this->assertSame(['Kelas A Test'], $filtered->viewData('classRooms')->pluck('name')->all(), 'Kelas ikut dibatasi ke kelas guru itu.');

        // Ganti guru: tetap di kelas yang sama bila guru itu punya murid di sana.
        $switched = $this->actingAs($this->admin)->get(route('spreadsheet-input.index', ['class_room_id' => $this->student->class_room_id, 'teacher_id' => $otherTeacher->id]));
        $this->assertSame(['Santri Guru Kedua Sekelas'], $switched->viewData('students')->pluck('name')->all());

        // Kelas tidak termasuk kelas guru terpilih -> pindah ke kelas pertama guru itu.
        $fallback = $this->actingAs($this->admin)->get(route('spreadsheet-input.index', ['class_room_id' => $otherClass->id, 'teacher_id' => $this->teacherProfile->id]));
        $this->assertSame((string) $this->student->class_room_id, (string) $fallback->viewData('selectedClassId'));
        $this->assertSame(['Santri Test'], $fallback->viewData('students')->pluck('name')->all());

        // Guru biasa tidak melihat filter.
        $this->actingAs($this->teacherUser)->get(route('spreadsheet-input.index'))->assertOk()->assertDontSee('Guru Pengampu');
    }

    #[Test]
    public function spreadsheet_ummi_rejects_ayah_beyond_surah_length(): void
    {
        $total = (int) $this->surah->total_ayah;
        $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), [
            'class_room_id' => $this->student->class_room_id,
            'month' => '2026-08',
            'type' => 'ummi',
            'records' => [$this->student->id => ['dates' => ['2026-08-04' => [
                'attendance' => 'hadir', 'tatap_muka' => 1, 'ummi_jilid' => 'Jilid 1', 'ummi_halaman_awal' => '1', 'ummi_halaman_akhir' => '2',
                'hafalans' => [['surah_id' => $this->surah->id, 'ayah' => '1-'.($total + 2)]],
            ]]]],
        ])->assertSessionHas('error', fn ($msg) => str_contains($msg, 'melebihi jumlah ayat surah'));

        $this->assertDatabaseMissing('ummi_records', ['student_id' => $this->student->id]);
    }
}
