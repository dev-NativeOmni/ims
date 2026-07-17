<?php

namespace Tests\Feature;

use App\Models\HafalanRecord;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class HafalanRecordTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    // =========================================================================
    // AKSES HALAMAN (ADMIN)
    // =========================================================================

    #[Test]
    public function admin_can_view_hafalan_record_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('hafalan-records.index'));

        $response->assertStatus(200);
        $response->assertViewIs('hafalan-records.index');
    }

    #[Test]
    public function admin_can_view_create_hafalan_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('hafalan-records.create'));

        $response->assertStatus(200);
        $response->assertViewIs('hafalan-records.create');
    }

    #[Test]
    public function teacher_can_view_hafalan_record_index(): void
    {
        $response = $this->actingAs($this->teacherUser)->get(route('hafalan-records.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function guest_is_redirected_from_hafalan_index(): void
    {
        $response = $this->get(route('hafalan-records.index'));

        $response->assertRedirect('/login');
    }

    // =========================================================================
    // TAMBAH DATA HAFALAN (ADMIN)
    // =========================================================================

    #[Test]
    public function admin_can_store_hafalan_record(): void
    {
        $response = $this->actingAs($this->admin)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'score' => 90,
            'status' => 'passed',
            'notes' => 'Hafalan perdana Al-Fatihah.',
            'submitted_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('hafalan-records.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hafalan_records', [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'status' => 'passed',
        ]);
    }

    #[Test]
    public function teacher_can_store_hafalan_record_for_own_student(): void
    {
        $response = $this->actingAs($this->teacherUser)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'submission_type' => 'continuation',
            'score' => 80,
            'status' => 'passed',
            'submitted_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('hafalan-records.index'));
        $this->assertDatabaseHas('hafalan_records', [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
        ]);
    }

    // =========================================================================
    // VALIDASI INPUT
    // =========================================================================

    #[Test]
    public function store_fails_when_ayah_end_exceeds_total_ayah(): void
    {
        $response = $this->actingAs($this->admin)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id, // total_ayah = 7
            'ayah_start' => 1,
            'ayah_end' => 999, // melebihi batas
            'submission_type' => 'new',
            'status' => 'passed',
            'submitted_at' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('ayah_end');
        $this->assertDatabaseCount('hafalan_records', 0);
    }

    #[Test]
    public function store_fails_when_ayah_end_less_than_ayah_start(): void
    {
        $response = $this->actingAs($this->admin)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 5,
            'ayah_end' => 2, // lebih kecil dari start
            'submission_type' => 'new',
            'status' => 'passed',
            'submitted_at' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('ayah_end');
    }

    #[Test]
    public function store_fails_with_invalid_status(): void
    {
        $response = $this->actingAs($this->admin)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'submission_type' => 'new',
            'status' => 'invalid_status', // tidak valid
            'submitted_at' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('status');
    }

    #[Test]
    public function store_fails_when_required_fields_missing(): void
    {
        $response = $this->actingAs($this->admin)->post(route('hafalan-records.store'), []);

        $response->assertSessionHasErrors(['student_id', 'surah_id', 'ayah_start', 'ayah_end', 'status', 'submitted_at']);
    }

    // =========================================================================
    // LIHAT, EDIT, UPDATE, HAPUS
    // =========================================================================

    #[Test]
    public function admin_can_view_hafalan_record_detail(): void
    {
        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('hafalan-records.show', $record));

        $response->assertStatus(200);
        $response->assertViewIs('hafalan-records.show');
        $response->assertViewHas('hafalanRecord');
    }

    #[Test]
    public function admin_can_update_hafalan_record(): void
    {
        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'status' => 'repeat',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->put(route('hafalan-records.update', $record), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'revision',
            'score' => 95,
            'status' => 'passed', // diubah
            'submitted_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('hafalan-records.index'));
        $this->assertDatabaseHas('hafalan_records', [
            'id' => $record->id,
            'status' => 'passed',
        ]);
    }

    #[Test]
    public function admin_can_delete_hafalan_record(): void
    {
        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('hafalan-records.destroy', $record));

        $response->assertRedirect(route('hafalan-records.index'));
        $this->assertSoftDeleted('hafalan_records', ['id' => $record->id]);
    }

    // =========================================================================
    // OTORISASI GURU
    // =========================================================================

    #[Test]
    public function teacher_cannot_delete_other_teachers_record(): void
    {
        // Buat guru lain
        $otherTeacherUser = User::factory()->create([
            'role_id' => $this->teacherUser->role_id,
            'status' => 'active',
        ]);
        $otherTeacher = TeacherProfile::create([
            'user_id' => $otherTeacherUser->id,
            'employee_number' => 'GURU-999',
            'phone' => '089900009999',
        ]);

        // Record milik guru lain
        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $otherTeacher->id, // guru lain
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'submission_type' => 'new',
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->teacherUser)->delete(route('hafalan-records.destroy', $record));

        $response->assertStatus(403);
        $this->assertNotSoftDeleted('hafalan_records', ['id' => $record->id]);
    }

    // =========================================================================
    // FILTER / SEARCH
    // =========================================================================

    #[Test]
    public function index_can_be_filtered_by_status(): void
    {
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 3,
            'submission_type' => 'new',
            'status' => 'passed',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('hafalan-records.index', ['status' => 'passed']));

        $response->assertStatus(200);
        $response->assertViewHas('hafalanRecords', fn ($records) => $records->total() >= 1);
    }

    #[Test]
    public function admin_can_store_multiple_hafalan_records_at_once(): void
    {
        $surah2 = Surah::create([
            'number' => 2,
            'name_arabic' => 'البقرة',
            'name_latin' => 'Al-Baqarah',
            'total_ayah' => 286,
            'revelation_type' => 'medinan',
        ]);

        $response = $this->actingAs($this->admin)->post(route('hafalan-records.store'), [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_ids' => [$this->surah->id, $surah2->id],
            'ayah_starts' => [1, 5],
            'ayah_ends' => [7, 10],
            'submission_types' => ['new', 'continuation'],
            'scores' => [95, 85],
            'statuses' => ['passed', 'repeat'],
            'notes' => 'Multi setoran.',
            'submitted_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('hafalan-records.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hafalan_records', [
            'student_id' => $this->student->id,
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 7,
            'submission_type' => 'new',
            'score' => 95.00,
            'status' => 'passed',
        ]);

        $this->assertDatabaseHas('hafalan_records', [
            'student_id' => $this->student->id,
            'surah_id' => $surah2->id,
            'ayah_start' => 5,
            'ayah_end' => 10,
            'submission_type' => 'continuation',
            'score' => 85.00,
            'status' => 'repeat',
        ]);
    }
}
