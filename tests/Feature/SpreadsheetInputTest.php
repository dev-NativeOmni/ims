<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\HafalanRecord;
use App\Models\UmmiRecord;
use App\Models\Surah;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\TeacherProfile;
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
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), $payload);

        $response->assertRedirect();
        
        // Assert Attendance was saved
        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'tanggal' => $date . ' 00:00:00',
            'status' => 'hadir',
        ]);

        // Assert HafalanRecord was saved
        $this->assertDatabaseHas('hafalan_records', [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'score' => 95,
            'status' => 'passed',
            'submitted_at' => $date . ' 00:00:00',
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
                            'ummi_jilid' => 'Jilid 4',
                            'ummi_halaman' => '25',
                            'materi' => 'Ghoroib',
                            'nilai' => 'A',
                            'hafalans' => [
                                [
                                    'id' => null,
                                    'surah_id' => $this->surah->id,
                                    'ayah' => '1-5',
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), $payload);

        $response->assertRedirect();

        // Assert Attendance was saved
        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'tanggal' => $date . ' 00:00:00',
            'status' => 'hadir',
        ]);

        // Assert UmmiRecord was saved
        $this->assertDatabaseHas('ummi_records', [
            'student_id' => $this->student->id,
            'tatap_muka' => 3,
            'tanggal' => $date . ' 00:00:00',
            'ummi_jilid' => 'Jilid 4',
            'ummi_halaman' => '25',
            'materi' => 'Ghoroib',
            'nilai' => 'A',
            'hafalan_surah_id' => $this->surah->id,
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
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), $payload);

        $response->assertRedirect();

        // Assert Attendance was saved as 'sakit'
        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'tanggal' => $date . ' 00:00:00',
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
}
