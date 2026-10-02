# Riwayat Kelas Santri

Dokumen ini menjelaskan bagaimana aplikasi menjaga laporan tetap benar ketika santri **pindah kelas**
(naik kelas, pindah halaqoh, salah input kelas, dsb.). Bacalah sebelum membuat atau mengubah laporan
yang menampilkan santri **per kelas** untuk suatu **periode** (bulan, triwulan, semester).

## Masalah yang diselesaikan

Semua catatan santri (setoran hafalan, murajaah, Ummi, target, ujian, adab, poin Tanse, presensi,
catatan rapor) disimpan per `student_id`, jadi tidak pernah hilang saat santri pindah kelas.

Namun kolom `students.class_room_id` hanya berisi **kelas saat ini**. Tanpa riwayat, laporan kelas
untuk periode lalu akan bergeser: santri yang pindah hilang dari laporan kelas lamanya, seluruh
riwayatnya muncul di kelas baru, dan target baris (yang bergantung jadwal kelas) ikut dihitung ulang
dengan jadwal kelas baru.

## Data

Tabel `student_class_histories` (model `App\Models\StudentClassHistory`):

| kolom | arti |
|---|---|
| `student_id` | santri |
| `class_room_id` | kelas |
| `start_date` | mulai di kelas ini |
| `end_date` | terakhir di kelas ini; `null` = masih di kelas ini |

Isi awal (migration `2026_10_02_000001`): kelas setiap santri saat tabel dibuat, berlaku sejak
1 Juli 2026 (tahun ajaran pertama aplikasi, `AcademicYear::FIRST`).

## Aturan pencatatan (otomatis)

Dicatat oleh event model `Student` (`booted()` di `app/Models/Student.php`) setiap `class_room_id`
berubah, lewat form edit santri, impor Excel, atau kode lain yang memakai model Eloquent:

- Periode lama ditutup **kemarin**, periode baru dibuka **hari ini**.
- Santri baru: dianggap di kelasnya sejak **awal tahun ajaran berjalan** (1 Juli), supaya setoran yang
  diinput belakangan untuk bulan-bulan sebelumnya tetap masuk laporan kelasnya.
- Perubahan pada **hari yang sama** dengan pembukaan riwayat dianggap **koreksi** (salah pilih kelas),
  bukan pindah kelas: baris riwayat itu diubah, tidak menambah baris baru.

> Jangan mengubah `students.class_room_id` dengan query builder (`DB::table('students')->update(...)`
> atau `Student::query()->update(...)`): event model tidak berjalan dan riwayat tidak tercatat.
> Pakai `$student->update([...])`.

## Aturan pemakaian di laporan

**Kelas santri untuk suatu periode = kelas pada tanggal acuan periode itu**, yaitu akhir periode,
atau hari ini bila periode masih berjalan (`StudentClassHistory::referenceDate($akhirPeriode)`).

Santri yang pindah di tengah triwulan masuk laporan kelas **barunya** untuk triwulan itu (satu santri
selalu tepat satu kelas per periode, tidak dobel).

Alat yang tersedia:

```php
// Santri di kelas X pada tanggal acuan periode
Student::query()->inClassOn($classRoomId, StudentClassHistory::referenceDate($akhirPeriode));

// Kelas satu santri pada suatu tanggal (tanpa riwayat = kelas saat ini)
$student->classRoomOn($tanggal);

// Riwayat yang berlaku pada tanggal itu
StudentClassHistory::query()->activeOn($tanggal);
```

Setelah memilih santri per kelas, pasang kelas periode itu ke relasinya supaya hitungan target
(pertemuan aktif × baris) memakai jadwal kelas yang benar:

```php
->get()->each(fn (Student $s) => $s->setRelation('classRoom', $kelasPeriodeItu));
```

Sudah dipakai di:

- Laporan Triwulan (per kelas & per guru): `QuarterlyReportController::buildReportData()` dan
  `buildTeacherReportData()`
- Rapor (per santri, cetak per kelas, kunci): `StudentReportController::getReportData()`,
  `printClass()`, `lockClass()`
- Grafik Tahfizh & Laporan Harian WhatsApp: `ReportController::getPeriodicProgressData()`, `whatsappDaily()`
- Target Triwulan: `HafalanTargetController::termClassRooms()`, `termStudents()`
- Input Spreadsheet: `SpreadsheetInputController::index()` (kelas pada akhir bulan lembar kerja)

Rapor yang sudah **dikunci** tidak bergantung pada riwayat ini: isinya, termasuk nama kelas, dibekukan
saat dikunci.

## Belum dicakup (pertimbangkan bila perlu)

- **Guru pengampu** (`students.teacher_id`) belum punya riwayat. Pengelompokan per musyrif di laporan
  periode lalu memakai guru pengampu saat ini.
- **Penomoran ulang Tatap Muka Ummi** (`UmmiTatapMukaService`) mengelompokkan halaqoh menurut kelas &
  guru saat ini. Ini hanya berpengaruh bila catatan Ummi triwulan lama diedit setelah santri pindah.
- Halaman operasional harian (input Adab, dashboard Wali Kelas, daftar santri) memakai kelas saat ini.
  Itu memang disengaja karena dipakai untuk pekerjaan hari ini, bukan laporan periode lalu.

## Tes

`tests/Feature/StudentClassHistoryTest.php` memuat skenario santri pindah kelas pada 5 Oktober 2026
dan memeriksa pencatatan, Laporan Triwulan, rapor, Input Spreadsheet, Target Triwulan, dan Grafik
Tahfizh. Tambahkan pemeriksaan di sana bila membuat laporan per kelas baru.
