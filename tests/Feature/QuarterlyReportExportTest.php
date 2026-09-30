<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\TeacherProfile;
use App\Models\UmmiRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Download Laporan Triwulan per kelas ke file .xlsx nyata (bukan sekadar CSV
 * berlabel xlsx) dengan satu sheet per tab yang tampil di layar: Term-Indeks,
 * Presensi, Jurnal, Setoran -- isinya harus sama persis dengan data yang dipakai
 * untuk merender halaman (lihat QuarterlyReportController::buildReportData()).
 */
class QuarterlyReportExportTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function downloadAndLoad(array $query): Spreadsheet
    {
        $response = $this->actingAs($this->admin)->get(route('reports.quarterly.export', $query));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $tmpPath = tempnam(sys_get_temp_dir(), 'qre').'.xlsx';
        file_put_contents($tmpPath, $response->streamedContent());

        $spreadsheet = IOFactory::load($tmpPath);
        unlink($tmpPath);

        return $spreadsheet;
    }

    /**
     * Sama seperti downloadAndLoad(), tapi baca ulang dengan chart diaktifkan --
     * reader PhpSpreadsheet defaultnya melewati chart demi performa.
     */
    private function downloadAndLoadWithCharts(array $query): Spreadsheet
    {
        $response = $this->actingAs($this->admin)->get(route('reports.quarterly.export', $query));
        $response->assertStatus(200);

        $tmpPath = tempnam(sys_get_temp_dir(), 'qrec').'.xlsx';
        file_put_contents($tmpPath, $response->streamedContent());

        $reader = new Xlsx;
        $reader->setIncludeCharts(true);
        $spreadsheet = $reader->load($tmpPath);
        unlink($tmpPath);

        return $spreadsheet;
    }

    #[Test]
    public function export_produces_a_real_xlsx_with_one_sheet_per_tab_and_only_the_selected_class(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas XII F4 Export',
            'level' => 'XII',
            'tahfizh_days' => [1, 2, 3, 4, 5],
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'reguler']);

        $otherClass = ClassRoom::create(['program_id' => $program->id, 'name' => 'Kelas Lain', 'level' => 'XII']);
        $otherStudent = Student::create([
            'class_room_id' => $otherClass->id,
            'teacher_id' => $this->teacherProfile->id,
            'name' => 'Murid Kelas Lain',
            'student_number' => 'TEST-SNT-901',
            'gender' => 'male',
            'birth_date' => '2009-01-01',
            'status' => 'active',
        ]);

        Attendance::create([
            'student_id' => $this->student->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => '2026-07-06',
            'status' => 'hadir',
        ]);
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-07-06',
        ])->surahs()->create([
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'submission_type' => 'new',
            'status' => 'passed',
            'score' => 90,
        ]);
        StudentPoint::create([
            'student_id' => $this->student->id,
            'type' => 'violation',
            'points' => 5,
            'title' => 'Terlambat',
            'date' => '2026-07-06',
            'logged_by' => $this->teacherUser->id,
        ]);

        $spreadsheet = $this->downloadAndLoad([
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ]);

        $sheetTitles = array_map(fn ($s) => $s->getTitle(), $spreadsheet->getAllSheets());
        $this->assertSame(['Term-Indeks', 'Presensi', 'Jurnal', 'Setoran', 'Grafik Akhir Bulan'], $sheetTitles);

        // Term-Indeks: dikelompokkan per tingkat kelas ("KELAS XII"), lalu per kelas/halaqoh
        // ("Kelas: ... | Musyrif: ..."), diikuti baris judul kolom, lalu satu baris per murid.
        $termSheet = $spreadsheet->getSheetByName('Term-Indeks');
        $rows = $termSheet->toArray();
        $this->assertSame('KELAS XII', $rows[0][0]);
        $this->assertSame('Kelas: Kelas XII F4 Export  |  Musyrif: Guru Test', $rows[1][0]);
        $this->assertSame(
            ['No', 'Nama Murid', 'Level', 'Awal Triwulan', 'Target Surah', 'Target Ayat', 'Capaian Surah', 'Capaian Ayat', 'Capaian Baris', 'Target Baris', 'Ketercapaian', 'Alpa', 'Izin', 'Sakit'],
            $rows[2]
        );
        $studentRow = collect($rows)->firstWhere(1, $this->student->name);
        $this->assertNotNull($studentRow);
        // Kolom Pelanggaran sudah dihapus: walau murid punya pelanggaran, kolom setelah Sakit kosong.
        $this->assertEmpty($studentRow[14] ?? null);

        $allTermCells = $this->flatten($rows);
        $this->assertNotContains($otherStudent->name, $allTermCells);

        $presensiSheet = $spreadsheet->getSheetByName('Presensi');
        $presensiRows = $presensiSheet->toArray();
        $this->assertGreaterThan(1, count($presensiRows));

        $setoranSheet = $spreadsheet->getSheetByName('Setoran');
        $setoranCells = $this->flatten($setoranSheet->toArray());
        $this->assertTrue(collect($setoranCells)->contains(fn ($v) => str_contains((string) $v, 'Al-Fatihah')));

        $grafikSheet = $spreadsheet->getSheetByName('Grafik Akhir Bulan');
        $grafikCells = $this->flatten($grafikSheet->toArray());
        $this->assertContains($this->student->name, $grafikCells);
    }

    private function flatten(array $rows): array
    {
        return collect($rows)->flatten()->all();
    }

    #[Test]
    public function headmaster_and_teacher_signatures_are_placed_side_by_side_and_jurnal_paraf_shows_the_teacher_signature(): void
    {
        Storage::fake('local');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        Storage::disk('local')->put('signatures/officials/headmaster-test.png', $png);
        Storage::disk('local')->put('signatures/teachers/musyrif-test.png', $png);
        Setting::set('signature_headmaster', 'signatures/officials/headmaster-test.png');
        $this->teacherUser->update(['signature_path' => 'signatures/teachers/musyrif-test.png']);

        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas TTD Export',
            'level' => 'XII',
            'tahfizh_days' => [1, 2, 3, 4, 5],
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'reguler']);

        Attendance::create([
            'student_id' => $this->student->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => '2026-07-06',
            'status' => 'hadir',
        ]);
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-07-06',
        ])->surahs()->create([
            'surah_id' => $this->surah->id,
            'ayah_start' => 1,
            'ayah_end' => 5,
            'submission_type' => 'new',
            'status' => 'passed',
            'score' => 90,
        ]);

        $spreadsheet = $this->downloadAndLoad([
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ]);

        foreach (['Jurnal', 'Setoran'] as $title) {
            $sheet = $spreadsheet->getSheetByName($title);
            $leftRows = [];
            $rightRows = [];
            foreach (array_keys($sheet->getMergeCells()) as $range) {
                if (preg_match('/^B(\d+):C\d+$/', $range, $m)) {
                    $leftRows[] = (int) $m[1];
                }
                if (preg_match('/^D(\d+):E\d+$/', $range, $m)) {
                    $rightRows[] = (int) $m[1];
                }
            }
            $this->assertNotEmpty($leftRows, "{$title}: blok tanda tangan kiri (Kepala Sekolah) tidak ditemukan");
            sort($leftRows);
            sort($rightRows);
            $this->assertSame($leftRows, $rightRows, "{$title}: tanda tangan Kepala Sekolah & Guru Pengampu harus berdampingan (B:C dan D:E di baris yang sama)");

            // Baris merge B:C dicatat untuk tiap baris blok ttd (7 baris), tapi gambarnya
            // sendiri cuma ditaruh di satu baris ("ruang gambar") -- cek gambar kiri & kanan
            // ada di baris yang SAMA (berdampingan), bukan di baris merge mana pun.
            $drawingCells = collect($sheet->getDrawingCollection())->map(fn ($d) => $d->getCoordinates())->all();
            $sameRowPair = collect($leftRows)->first(fn ($r) => in_array("B{$r}", $drawingCells, true) && in_array("D{$r}", $drawingCells, true));
            $this->assertNotNull($sameRowPair, "{$title}: gambar ttd Kepala Sekolah & Guru Pengampu tidak ditemukan berdampingan di baris yang sama");
        }

        // Jurnal: pertemuan yang terlaksana ('✓') diganti gambar ttd guru, bukan teks centang.
        $jurnalSheet = $spreadsheet->getSheetByName('Jurnal');
        $this->assertNotContains('✓', $this->flatten($jurnalSheet->toArray()));
        $jurnalDrawingCells = collect($jurnalSheet->getDrawingCollection())->map(fn ($d) => $d->getCoordinates())->all();
        $this->assertTrue(
            collect($jurnalDrawingCells)->contains(fn ($c) => preg_match('/^E\d+$/', $c) === 1),
            'Kolom Paraf harus berisi gambar ttd guru di kolom E'
        );
    }

    #[Test]
    public function pekan_column_headers_show_the_real_meeting_day_and_date_not_just_a_generic_label(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas Tanggal Pertemuan',
            'level' => 'XII',
            'tahfizh_days' => [4], // kelas cuma tatap muka tiap Kamis.
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'reguler']);

        $spreadsheet = $this->downloadAndLoad([
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ]);

        $setoranHeader = $this->flatten($spreadsheet->getSheetByName('Setoran')->toArray());
        $pekanCell = collect($setoranHeader)->first(fn ($v) => is_string($v) && str_starts_with($v, 'PEKAN 1 ('));
        $this->assertNotNull($pekanCell, 'Header PEKAN 1 tidak ditemukan di sheet Setoran.');
        $this->assertMatchesRegularExpression('/Kamis, \d{1,2} Jul/', $pekanCell);

        $presensiHeader = $this->flatten($spreadsheet->getSheetByName('Presensi')->toArray());
        // Presensi: label dua baris supaya kolom sempit ("PEKAN 1\n(Kamis, 2 Jul)").
        $presensiPekanCell = collect($presensiHeader)->first(fn ($v) => is_string($v) && str_starts_with($v, "PEKAN 1\n("));
        $this->assertNotNull($presensiPekanCell, 'Header PEKAN 1 tidak ditemukan di sheet Presensi.');
        $this->assertMatchesRegularExpression('/Kamis, \d{1,2} Jul/', $presensiPekanCell);
    }

    #[Test]
    public function export_can_be_narrowed_down_to_a_single_halaqoh(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas XII F5 Export',
            'level' => 'XII',
            'tahfizh_days' => [1, 2, 3, 4, 5],
        ]);
        $this->student->update([
            'class_room_id' => $classRoom->id,
            'tahfizh_level' => 'reguler',
            'name' => 'Murid Halaqoh Satu',
        ]);

        $secondTeacherRole = Role::where('name', 'teacher')->firstOrFail();
        $secondTeacherUser = User::factory()->create(['role_id' => $secondTeacherRole->id, 'name' => 'Ust. Halaqoh Dua', 'status' => 'active']);
        $secondTeacherProfile = TeacherProfile::create(['user_id' => $secondTeacherUser->id, 'employee_number' => 'TEST-GURU-002']);
        $secondStudent = Student::create([
            'class_room_id' => $classRoom->id,
            'teacher_id' => $secondTeacherProfile->id,
            'name' => 'Murid Halaqoh Dua',
            'student_number' => 'TEST-SNT-902',
            'gender' => 'male',
            'birth_date' => '2009-01-01',
            'status' => 'active',
        ]);

        $query = [
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ];

        // Pastikan dua halaqoh benar-benar terbentuk sebelum diuji filternya.
        $fullSpreadsheet = $this->downloadAndLoad($query);
        $fullNames = $this->flatten($fullSpreadsheet->getSheetByName('Term-Indeks')->toArray());
        $this->assertContains('Murid Halaqoh Satu', $fullNames);
        $this->assertContains('Murid Halaqoh Dua', $fullNames);

        $filtered = $this->downloadAndLoad($query + ['musyrif' => $this->teacherUser->name]);
        $filteredNames = $this->flatten($filtered->getSheetByName('Term-Indeks')->toArray());
        $this->assertContains('Murid Halaqoh Satu', $filteredNames);
        $this->assertNotContains('Murid Halaqoh Dua', $filteredNames);
    }

    #[Test]
    public function only_admin_super_admin_and_teacher_can_download_the_export(): void
    {
        // Guru boleh, tapi datanya dibatasi ke murid yang dia ampu (QuarterlyReportTeacherPreviewTest).
        $this->get(route('reports.quarterly.export'))->assertRedirect(route('login'));
        $this->actingAs($this->parentUser)->get(route('reports.quarterly.export'))->assertStatus(403);
        $this->actingAs($this->studentUser)->get(route('reports.quarterly.export'))->assertStatus(403);
    }

    #[Test]
    public function grafik_akhir_bulan_includes_a_pie_and_a_bar_line_chart_per_month_with_the_tuntas_percentage(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas Donut Export',
            'level' => 'XII',
            'tahfizh_days' => [1, 2, 3, 4, 5],
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'reguler']);

        $spreadsheet = $this->downloadAndLoadWithCharts([
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ]);

        $sheet = $spreadsheet->getSheetByName('Grafik Akhir Bulan');
        // Dua chart per bulan (Juli, Agustus, September) di dalam term ini: batang+garis capaian, dan pie ketuntasan.
        $this->assertSame(6, $sheet->getChartCount());

        $titles = collect($sheet->getChartCollection())->map(fn ($c) => $c->getTitle()->getCaptionText());
        $this->assertTrue($titles->contains(fn ($t) => str_contains($t, 'Ketuntasan')));
        $this->assertTrue($titles->contains(fn ($t) => str_contains($t, 'Grafik Capaian')));

        // Chart pie ketuntasan pakai DataSeries bertipe pieChart sungguhan, dengan warna
        // per-irisan teal/rose (sama seperti donat "Ketuntasan" di Rapor Periodik).
        $pieChart = collect($sheet->getChartCollection())->first(fn ($c) => str_contains($c->getTitle()->getCaptionText(), 'Ketuntasan'));
        $pieSeries = $pieChart->getPlotArea()->getPlotGroup()[0];
        $this->assertSame('pieChart', $pieSeries->getPlotType());
        $this->assertSame(['0D9488', 'F43F5E'], $pieSeries->getPlotValues()[0]->getFillColor());

        // Chart batang+garis benar-benar dua tipe series berbeda (kombinasi), bukan cuma satu,
        // dengan warna biru langit untuk batang (sama seperti chart "Capaian" di Rapor Periodik).
        $comboChart = collect($sheet->getChartCollection())->first(fn ($c) => str_contains($c->getTitle()->getCaptionText(), 'Grafik Capaian'));
        $comboSeries = collect($comboChart->getPlotArea()->getPlotGroup());
        $plotTypes = $comboSeries->map(fn ($s) => $s->getPlotType());
        $this->assertContains('barChart', $plotTypes);
        $this->assertContains('lineChart', $plotTypes);
        $barSeries = $comboSeries->first(fn ($s) => $s->getPlotType() === 'barChart');
        $this->assertSame('0EA5E9', $barSeries->getPlotValues()[0]->getFillColor());

        // Data mentah donat (label berisi persentase) ada di kolom G/H, dibaca langsung oleh chart.
        $cells = $this->flatten($sheet->toArray());
        $this->assertTrue(collect($cells)->contains(fn ($v) => is_string($v) && str_contains($v, 'TUNTAS (') && str_contains($v, '%)')));
    }

    #[Test]
    public function export_skips_holiday_pekan_columns_and_keeps_the_no_column_compact(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas XI F3 Export',
            'level' => 'XI',
            'tahfizh_days' => [2], // Selasa Juli 2026: 7 (pekan 1), 14 (pekan 2), 21, 28; pekan 5 tanpa Selasa
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'reguler']);
        $this->markHoliday('2026-07-14'); // pekan 2 libur

        $spreadsheet = $this->downloadAndLoad([
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ]);

        foreach (['Presensi' => "\n", 'Setoran' => ' '] as $name => $sep) {
            $sheet = $spreadsheet->getSheetByName($name);
            $cells = $this->flatten($sheet->toArray());
            $this->assertEmpty(array_filter($cells, fn ($c) => str_contains((string) $c, '(Libur)')), "{$name}: pekan libur tidak dijadikan kolom");
            $this->assertContains("PEKAN 1{$sep}(Selasa, 7 Jul)", $cells);
            $this->assertContains("PEKAN 3{$sep}(Selasa, 21 Jul)", $cells);
            $this->assertContains("PEKAN 4{$sep}(Selasa, 28 Jul)", $cells);
            $this->assertEquals(5, $sheet->getColumnDimension('A')->getWidth(), "{$name}: kolom No ringkas");
            // Judul "BULAN ..." di-merge selebar sheet supaya tidak terpotong di kolom No yang sempit.
            $this->assertStringStartsWith('A1:', (string) $sheet->getCell('A1')->getMergeRange());
        }

        // Presensi Juli: No, Nama, 3 pekan aktif, lalu Rekap Hadir/Izin/Sakit/Alpa.
        $presensiSheet = $spreadsheet->getSheetByName('Presensi');
        $presensi = $presensiSheet->toArray();
        $subIdx = collect($presensi)->search(fn ($r) => ($r[2] ?? null) === "PEKAN 1\n(Selasa, 7 Jul)");
        $this->assertSame(['Hadir', 'Izin', 'Sakit', 'Alpa'], array_slice($presensi[$subIdx], 5, 4));

        // Isi tabel rata tengah kecuali Nama Murid.
        $studentRow = $subIdx + 2; // baris Excel (1-indexed) murid pertama
        $this->assertSame('center', $presensiSheet->getStyle("C{$studentRow}")->getAlignment()->getHorizontal());
        $this->assertSame('left', $presensiSheet->getStyle("B{$studentRow}")->getAlignment()->getHorizontal());

        // Jurnal: kolom Paraf lebar & baris isi tinggi supaya gambar paraf jelas.
        $jurnal = $spreadsheet->getSheetByName('Jurnal');
        $this->assertEquals(18, $jurnal->getColumnDimension('E')->getWidth());
    }

    #[Test]
    public function tahfizh_ummi_setoran_is_split_into_ummi_and_mandiri_columns(): void
    {
        $program = Program::create(['name' => 'Program Tahfizh', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X E1 Export',
            'level' => 'X',
            'tahfizh_days' => [1, 2], // Senin 13 & Selasa 14 Juli 2026 = pekan 2
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'ummi']);

        $ummi = UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tatap_muka' => 1,
            'tanggal' => '2026-07-13',
            'ummi_jilid' => 'Jilid 3',
            'ummi_halaman' => '20-22',
            'nilai' => 'A',
        ]);
        $ummi->surahs()->create(['surah_id' => $this->surah->id, 'hafalan_ayah' => '1-4', 'sort_order' => 1]);
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-07-13',
        ])->surahs()->create([
            'surah_id' => $this->surah->id, 'ayah_start' => 5, 'ayah_end' => 7, 'submission_type' => 'new', 'status' => 'passed',
        ]);
        Attendance::create([
            'student_id' => $this->student->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => '2026-07-14',
            'status' => 'izin',
        ]);

        $sheet = $this->downloadAndLoad([
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ])->getSheetByName('Setoran');
        $rows = $sheet->toArray();

        // Tabel pekan 2: baris hari "Senin, 13 Jul", lalu baris judul Ummi/Mandiri/Nilai.
        $dayIdx = collect($rows)->search(fn ($r) => ($r[3] ?? null) === 'Senin, 13 Jul');
        $this->assertNotFalse($dayIdx, 'Tabel pekan 2 ada.');
        $groupIdx = $dayIdx + 1;
        $this->assertSame(['Ummi', null, null, null, 'Mandiri', null, 'Nilai'], array_slice($rows[$groupIdx], 3, 7));
        $this->assertSame(['Jilid', 'Halaman', 'Surah', 'Ayat', 'Surah', 'Ayat'], array_slice($rows[$groupIdx + 1], 3, 6));

        $studentRow = $rows[$groupIdx + 2];
        $this->assertSame($this->student->name, $studentRow[1]);
        // Senin: Ummi (Jilid 3, hal. 20-22, Al-Fatihah 1-4), Mandiri (Al-Fatihah 5-7), Nilai A.
        $this->assertSame(['Jilid 3', '20-22', 'Al-Fatihah', '1-4', 'Al-Fatihah', '5-7', 'A'], array_map(fn ($v) => (string) $v, array_slice($studentRow, 3, 7)));
        // Selasa: izin, ditulis sekali & digabung selebar hari itu.
        $this->assertSame('Izin', $studentRow[10]);
        $this->assertSame('K'.($groupIdx + 3).':Q'.($groupIdx + 3), $sheet->getCell('K'.($groupIdx + 3))->getMergeRange());
    }

    #[Test]
    public function grafik_sheet_for_ummi_shows_target_and_capaian_jilid_halaman_surah_ayat_like_the_web(): void
    {
        $program = Program::create(['name' => 'Program Tahfizh', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X E1 Grafik',
            'level' => 'X',
            'tahfizh_days' => [1, 2],
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'ummi']);
        UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tatap_muka' => 1,
            'tanggal' => '2026-07-13',
            'ummi_jilid' => 'Jilid 3',
            'ummi_halaman' => '20-22',
        ])->surahs()->create(['surah_id' => $this->surah->id, 'hafalan_ayah' => '1-4', 'sort_order' => 1]);

        $sheet = $this->downloadAndLoadWithCharts([
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ])->getSheetByName('Grafik Akhir Bulan');
        $rows = $sheet->toArray();

        // Juli: header dua baris (grup Target/Capaian, lalu Jilid|Halaman|Surah|Ayat), lalu murid.
        $top = collect($rows)->search(fn ($r) => ($r[3] ?? null) === 'Target');
        $this->assertNotFalse($top);
        $this->assertSame(['No', 'Nama Murid', 'Level', 'Target'], array_slice($rows[$top], 0, 4));
        $this->assertSame('Capaian', $rows[$top][7]);
        $this->assertSame('Ketuntasan', $rows[$top][11]);
        $this->assertSame(['Jilid', 'Halaman', 'Surah', 'Ayat', 'Jilid', 'Halaman', 'Surah', 'Ayat'], array_slice($rows[$top + 1], 3, 8));

        $student = $rows[$top + 2];
        $this->assertSame($this->student->name, $student[1]);
        // Capaian = setoran Ummi terakhir: Jilid 3 hal. 22, Al-Fatihah ayat 4 (sama dengan web).
        $this->assertSame(['Jilid 3', '22', 'Al-Fatihah', '4'], array_map(fn ($v) => (string) $v, array_slice($student, 7, 4)));

        // Hanya donat ketuntasan (Ummi tidak punya grafik baris), langsung di kanan tabel & data donat (N/O).
        $charts = collect($sheet->getChartCollection());
        $this->assertCount(3, $charts);
        $this->assertTrue($charts->every(fn ($c) => str_starts_with($c->getTitle()->getCaptionText(), 'Ketuntasan')));
        $this->assertSame('Q', preg_replace('/\d+/', '', $charts->first()->getTopLeftPosition()['cell']));
    }

    #[Test]
    public function reguler_ummi_setoran_is_split_into_ummi_columns_without_mandiri(): void
    {
        $program = Program::create(['name' => 'Program Reguler Test', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X E2 Export',
            'level' => 'X',
            'tahfizh_days' => [3], // Rabu Juli 2026: 1, 8, 15, 22, 29
        ]);
        $this->student->update(['class_room_id' => $classRoom->id, 'tahfizh_level' => 'ummi']);

        $ummi = UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tatap_muka' => 1,
            'tanggal' => '2026-07-08',
            'ummi_jilid' => 'Jilid 1',
            'ummi_halaman' => '1-5',
            'nilai' => 'A',
        ]);
        $ummi->surahs()->create(['surah_id' => $this->surah->id, 'hafalan_ayah' => '1-7', 'sort_order' => 1]);
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-07-08',
        ])->surahs()->create([
            'surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 3, 'submission_type' => 'new', 'status' => 'passed',
        ]);
        Attendance::create([
            'student_id' => $this->student->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => '2026-07-15',
            'status' => 'izin',
        ]);

        $sheet = $this->downloadAndLoad([
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ])->getSheetByName('Setoran');
        $rows = $sheet->toArray();

        // Juli: PEKAN 1 (Rabu, 1 Jul) di kolom D-I, PEKAN 2 (Rabu, 8 Jul) di J-O, PEKAN 3 di P-U.
        $top = collect($rows)->search(fn ($r) => ($r[3] ?? null) === 'PEKAN 1 (Rabu, 1 Jul)');
        $this->assertNotFalse($top);
        $this->assertSame('PEKAN 2 (Rabu, 8 Jul)', $rows[$top][9]);
        $this->assertSame(['Ummi', null, null, null, 'Nilai', 'Kehadiran'], array_slice($rows[$top + 1], 9, 6));
        $this->assertSame(['Jilid', 'Halaman', 'Surah', 'Ayat'], array_slice($rows[$top + 2], 9, 4));
        $this->assertNotContains('Mandiri', $this->flatten($rows), 'Program Reguler tidak punya kolom Mandiri.');
        // 5 Rabu di Juli = 5 pekan x 6 kolom; Rekap Kehadiran sesudahnya (tanpa Capaian Baris).
        $this->assertSame('Rekap Kehadiran', $rows[$top][3 + 5 * 6]);

        $student = $rows[$top + 3];
        $this->assertSame($this->student->name, $student[1]);
        // Pekan 2: Ummi (Jilid 1, hal. 1-5, Al-Fatihah 1-7), Nilai A, Hadir.
        $this->assertSame(['Jilid 1', '1-5', 'Al-Fatihah', '1-7', 'A', 'Hadir'], array_map(fn ($v) => (string) $v, array_slice($student, 9, 6)));
        // Pekan 3: izin, digabung selebar kolom setoran (P-T) & tertulis di Kehadiran (U).
        $this->assertSame('Izin', $student[15]);
        $this->assertSame('Izin', $student[20]);
        $this->assertSame('P'.($top + 4).':T'.($top + 4), $sheet->getCell('P'.($top + 4))->getMergeRange());
    }
}
