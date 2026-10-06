<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Impor Excel Dapodik: format seperti file sekolah ("ROMBEL DAPODIK ALL JENJANG": beberapa sheet berisi
 * daftar yang sama, judul di baris 1, baris 2 kosong).
 */
class DapodikImportTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private Student $byNumber;

    private Student $manual;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        // Dicocokkan lewat nama + tanggal lahir (ejaan huruf besar/kecil berbeda).
        $this->student->update(['name' => 'Abbas Surya Permana', 'birth_date' => '2010-09-19', 'student_number' => 'APP-01']);
        // Dicocokkan lewat nomor induk aplikasi = NIPD tanpa awalan "4407-".
        $this->byNumber = Student::create(['class_room_id' => $this->student->class_room_id, 'name' => 'Abrori', 'student_number' => '262737', 'status' => 'active']);
        // Nama beda jauh: harus dipasangkan manual.
        $this->manual = Student::create(['class_room_id' => $this->student->class_room_id, 'name' => 'Yan Fata', 'student_number' => 'APP-03', 'status' => 'active']);
    }

    private function dapodikFile(): UploadedFile
    {
        $rows = [
            ['No', 'Nama', 'NIPD', 'JK', 'NISN', 'Tempat Lahir', 'Tanggal Lahir', 'NIK', 'Rombel Saat Ini'],
            [null, null, null, null, null, null, null, null, null],
            [1, 'ABBAS SURYA PERMANA', '4407-262711', 'L', '3102030087', 'Sukoharjo', '2010-09-19', '3311000000000001', '10. E1'],
            [2, 'ABRORI AL MUAMMAR', '4407-262737', 'L', '0119761070', 'SUKOHARJO', '2011-05-31', '3311000000000002', '10. E2'],
            [3, 'ABYAN FATA DIARMA', '4407-262712', 'L', 108295063, 'JEPARA', '2010-09-21', '3311000000000003', '10. E1'],
            [4, 'MURID LAIN', '4407-262799', 'P', '0100000001', 'KLATEN', '2010-01-01', '3311000000000004', '11. F1'],
        ];
        $book = new Spreadsheet;
        foreach (['ROMBEL 10.E1', 'ROMBEL 10.E2'] as $i => $title) {
            $sheet = $i === 0 ? $book->getActiveSheet() : $book->createSheet();
            $sheet->setTitle($title)->fromArray($rows);
        }
        $path = tempnam(sys_get_temp_dir(), 'dapodik').'.xlsx';
        (new Xlsx($book))->save($path);

        return new UploadedFile($path, 'ROMBEL DAPODIK.xlsx', null, null, true);
    }

    #[Test]
    public function preview_matches_students_without_saving_anything(): void
    {
        $this->actingAs($this->admin)->post(route('students.dapodik.preview'), ['file' => $this->dapodikFile()])
            ->assertRedirect(route('students.dapodik.index'));

        $preview = $this->actingAs($this->admin)->get(route('students.dapodik.index'))->assertOk()->viewData('preview');

        $methods = collect($preview['matched'])->mapWithKeys(fn ($m) => [$m['student_id'] => $m['method']]);
        $this->assertSame('Nama & tgl lahir', $methods[$this->student->id]);
        $this->assertSame('NIPD', $methods[$this->byNumber->id]);
        $this->assertSame(['ABYAN FATA DIARMA', 'MURID LAIN'], collect($preview['unmatched'])->pluck('name')->all(), 'Baris dari dua sheet dihitung sekali.');
        $this->assertSame('0108295063', $preview['unmatched'][0]['nisn'], 'NISN angka dilengkapi nol di depan.');
        $this->assertNull($this->student->fresh()->dapodik_nisn, 'Pratinjau belum menyimpan.');
    }

    #[Test]
    public function apply_saves_dapodik_identity_and_birth_data_with_manual_and_skipped_rows(): void
    {
        $this->actingAs($this->admin)->post(route('students.dapodik.preview'), ['file' => $this->dapodikFile()]);
        $preview = $this->actingAs($this->admin)->get(route('students.dapodik.index'))->viewData('preview');
        $skipIndex = collect($preview['matched'])->firstWhere('student_id', $this->byNumber->id)['index'];
        $manualIndex = $preview['unmatched'][0]['index'];

        $this->actingAs($this->admin)->post(route('students.dapodik.apply'), [
            'skip' => [$skipIndex],
            'manual' => [$manualIndex => $this->manual->id],
        ])->assertRedirect(route('students.dapodik.index'))->assertSessionHas('success');

        $student = $this->student->fresh();
        $this->assertSame(['4407-262711', '3102030087', '10. E1', 'Sukoharjo', '2010-09-19'], [
            $student->dapodik_nis, $student->dapodik_nisn, $student->dapodik_rombel, $student->birth_place, $student->birth_date->toDateString(),
        ]);
        $this->assertSame('Abbas Surya Permana', $student->name, 'Nama aplikasi tidak diubah.');
        $this->assertSame('4407-262711 / 3102030087', $student->nisNisn());
        $this->assertNotNull($student->dapodik_synced_at);

        $this->assertNull($this->byNumber->fresh()->dapodik_nisn, 'Baris yang centangnya dihilangkan tidak disimpan.');
        $manual = $this->manual->fresh();
        $this->assertSame(['0108295063', 'JEPARA', '2010-09-21'], [$manual->dapodik_nisn, $manual->birth_place, $manual->birth_date->toDateString()]);

        // Web menampilkan NIS/NISN Dapodik, tapi tetap kelas pembelajaran (rombel hanya untuk rapor cetak).
        $this->actingAs($this->admin)->get(route('students.show', $student))
            ->assertOk()->assertSee('3102030087')->assertSee('Sukoharjo');
        $this->actingAs($this->admin)->get(route('digital-reports.show', $student))
            ->assertOk()->assertSee('4407-262711 / 3102030087')->assertSee($student->classRoom->name);
        $this->actingAs($this->admin)->get(route('digital-reports.print', $student))
            ->assertOk()->assertSee('<td>10. E1</td>', false);
    }

    #[Test]
    public function only_admins_can_import_and_files_must_be_excel(): void
    {
        $this->actingAs($this->teacherUser)->get(route('students.dapodik.index'))->assertForbidden();

        $this->actingAs($this->admin)->post(route('students.dapodik.preview'), [
            'file' => UploadedFile::fake()->create('data.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('file');
    }
}
